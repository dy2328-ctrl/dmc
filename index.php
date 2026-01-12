<?php
require 'db.php';
if(!isset($_SESSION['uid'])) { header("Location: login.php"); exit; }

// --- BACKEND ACTIONS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. إضافة وحدة (مع العدادات والأنواع)
    if (isset($_POST['add_unit'])) {
        $pdo->prepare("INSERT INTO units (property_id, unit_name, type, yearly_price, elec_meter_no, water_meter_no, status) VALUES (?,?,?,?,?,?,?)")
            ->execute([$_POST['pid'], $_POST['name'], $_POST['type'], $_POST['price'], $_POST['elec'], $_POST['water'], 'available']);
        header("Location: ?p=units"); exit;
    }

    // 2. عقد جديد (مع التوقيع الإلكتروني)
    if (isset($_POST['add_contract'])) {
        $pdo->prepare("INSERT INTO contracts (tenant_id, unit_id, start_date, end_date, total_amount, payment_cycle, signature_img) VALUES (?,?,?,?,?,?,?)")
            ->execute([$_POST['tid'], $_POST['uid'], $_POST['start'], $_POST['end'], $_POST['total'], $_POST['cycle'], $_POST['sig']]);
        $pdo->prepare("UPDATE units SET status='rented' WHERE id=?")->execute([$_POST['uid']]);
        header("Location: ?p=contracts"); exit;
    }

    // 3. إضافة موظف جديد
    if (isset($_POST['add_user'])) {
        $pdo->prepare("INSERT INTO users (full_name, username, password, role, phone) VALUES (?,?,?,?,?)")
            ->execute([$_POST['name'], $_POST['user'], password_hash($_POST['pass'], PASSWORD_DEFAULT), 'staff', $_POST['phone']]);
        header("Location: ?p=users"); exit;
    }

    // 4. تحديث الملف الشخصي للأدمن
    if (isset($_POST['update_profile'])) {
        $sql = "UPDATE users SET full_name=?, username=?, phone=? WHERE id=?";
        $params = [$_POST['name'], $_POST['user'], $_POST['phone'], $_SESSION['uid']];
        if(!empty($_POST['pass'])){
            $sql = "UPDATE users SET full_name=?, username=?, phone=?, password=? WHERE id=?";
            $params = [$_POST['name'], $_POST['user'], $_POST['phone'], password_hash($_POST['pass'], PASSWORD_DEFAULT), $_SESSION['uid']];
        }
        $pdo->prepare($sql)->execute($params);
        header("Location: ?p=profile&success=1"); exit;
    }

    // 5. تحميل نسخة احتياطية (Backup)
    if (isset($_POST['backup_db'])) {
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        $sqlScript = "-- DATABASE BACKUP\n\n";
        foreach ($tables as $table) {
            $row2 = $pdo->query("SHOW CREATE TABLE $table")->fetch(PDO::FETCH_NUM);
            $sqlScript .= "\n\n" . $row2[1] . ";\n\n";
            $rows = $pdo->query("SELECT * FROM $table")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $row) {
                $sqlScript .= "INSERT INTO $table VALUES(";
                $vals = array_map(function($v){ return "'" . addslashes($v) . "'"; }, array_values($row));
                $sqlScript .= implode(", ", $vals) . ");\n";
            }
        }
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename=backup_' . date('Y-m-d') . '.sql');
        echo $sqlScript; exit;
    }
    
    // حفظ الإعدادات
    if(isset($_POST['save_settings'])){
        foreach($_POST['set'] as $k=>$v) saveSet($k,$v);
        header("Location: ?p=settings"); exit;
    }
    
    // معالجة إضافة العقارات والمستأجرين (مختصرة)
    if(isset($_POST['add_prop'])){ $pdo->prepare("INSERT INTO properties (name,type) VALUES (?,?)")->execute([$_POST['name'],$_POST['type']]); header("Location: ?p=props"); }
    if(isset($_POST['add_tenant'])){ $pdo->prepare("INSERT INTO tenants (full_name,phone,id_number) VALUES (?,?,?)")->execute([$_POST['name'],$_POST['phone'],$_POST['nid']]); header("Location: ?p=tenants"); }
}

$p = $_GET['p'] ?? 'dashboard';
$me = $pdo->query("SELECT * FROM users WHERE id=".$_SESSION['uid'])->fetch();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>دار الميار للمقاولات</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --bg:#0f172a; --card:#1e293b; --primary:#6366f1; --text:#f8fafc; }
        body { background:var(--bg); color:var(--text); font-family:'Tajawal'; margin:0; display:flex; height:100vh; overflow:hidden; }
        
        /* Sidebar */
        .sidebar { width:260px; background:var(--card); padding:20px; display:flex; flex-direction:column; border-left:1px solid #334155; }
        .brand { text-align:center; margin-bottom:30px; }
        .brand img { width:80px; margin-bottom:10px; }
        .nav-link { display:flex; align-items:center; padding:12px; color:#94a3b8; text-decoration:none; margin-bottom:5px; border-radius:10px; transition:0.3s; }
        .nav-link:hover, .nav-link.active { background:var(--primary); color:white; }
        .nav-link i { width:25px; }

        /* Main */
        .main { flex:1; padding:30px; overflow-y:auto; }
        .header { display:flex; justify-content:space-between; align-items:center; margin-bottom:30px; }
        .card { background:var(--card); padding:25px; border-radius:15px; border:1px solid #334155; margin-bottom:20px; }
        
        /* Stats Grid */
        .stats { display:grid; grid-template-columns:repeat(auto-fit, minmax(220px,1fr)); gap:20px; margin-bottom:30px; }
        .stat-box { background:var(--card); padding:20px; border-radius:15px; border:1px solid #334155; position:relative; overflow:hidden; }
        .stat-val { font-size:28px; font-weight:bold; margin-top:10px; }

        /* Tables */
        table { width:100%; border-collapse:collapse; }
        th { text-align:right; padding:15px; color:#94a3b8; border-bottom:1px solid #334155; }
        td { padding:15px; border-bottom:1px solid #334155; }
        
        /* Inputs & Buttons */
        .btn { padding:10px 20px; background:var(--primary); color:white; border:none; border-radius:8px; cursor:pointer; text-decoration:none; display:inline-block; }
        .inp { width:100%; padding:12px; background:#0f172a; border:1px solid #334155; color:white; border-radius:8px; margin-bottom:15px; box-sizing:border-box; font-family:inherit; }
        
        /* Canvas for Signature */
        canvas { background:white; border-radius:8px; cursor:crosshair; }
        
        /* Modal */
        .modal { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.7); justify-content:center; align-items:center; z-index:999; backdrop-filter:blur(5px); }
        .modal-content { background:var(--card); padding:30px; border-radius:20px; width:550px; border:1px solid #475569; max-height:90vh; overflow-y:auto; }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="brand">
        <img src="<?= getSet('logo') ?>" onerror="this.src='logo.png'">
        <h3>دار الميار للمقاولات</h3>
    </div>
    <a href="?p=dashboard" class="nav-link <?= $p=='dashboard'?'active':'' ?>"><i class="fa-solid fa-chart-pie"></i> لوحة التحكم</a>
    <a href="?p=props" class="nav-link <?= $p=='props'?'active':'' ?>"><i class="fa-solid fa-city"></i> العقارات</a>
    <a href="?p=units" class="nav-link <?= $p=='units'?'active':'' ?>"><i class="fa-solid fa-door-open"></i> الوحدات</a>
    <a href="?p=contracts" class="nav-link <?= $p=='contracts'?'active':'' ?>"><i class="fa-solid fa-file-contract"></i> العقود</a>
    <?php if($me['role']=='admin'): ?>
    <a href="?p=users" class="nav-link <?= $p=='users'?'active':'' ?>"><i class="fa-solid fa-users-gear"></i> إدارة الموظفين</a>
    <a href="?p=settings" class="nav-link <?= $p=='settings'?'active':'' ?>"><i class="fa-solid fa-gear"></i> الإعدادات والنسخ</a>
    <?php endif; ?>
    <a href="?p=profile" class="nav-link <?= $p=='profile'?'active':'' ?>"><i class="fa-solid fa-user-circle"></i> ملفي الشخصي</a>
    <a href="logout.php" class="nav-link" style="color:#ef4444; margin-top:auto"><i class="fa-solid fa-right-from-bracket"></i> خروج</a>
</div>

<div class="main">
    <div class="header">
        <h2><?= $p=='dashboard'?'لوحة القيادة':($p=='units'?'إدارة الوحدات':'النظام') ?></h2>
        <div style="display:flex; align-items:center; gap:10px">
            <span>مرحباً، <?= $me['full_name'] ?></span>
            <img src="logo.png" width="40" style="border-radius:50%; background:white; padding:2px">
        </div>
    </div>

    <?php if($p == 'dashboard'): ?>
        <div class="stats">
            <div class="stat-box">
                <i class="fa-solid fa-money-bill" style="color:#10b981"></i> الإيرادات
                <div class="stat-val"><?= number_format($pdo->query("SELECT SUM(total_amount) FROM contracts")->fetchColumn()) ?></div>
            </div>
            <div class="stat-box">
                <i class="fa-solid fa-building" style="color:#6366f1"></i> عدد الوحدات
                <div class="stat-val"><?= $pdo->query("SELECT count(*) FROM units")->fetchColumn() ?></div>
            </div>
            <div class="stat-box">
                <i class="fa-solid fa-check-circle" style="color:#f59e0b"></i> العقود النشطة
                <div class="stat-val"><?= $pdo->query("SELECT count(*) FROM contracts WHERE status='active'")->fetchColumn() ?></div>
            </div>
        </div>
        <div class="card">
            <h3>آخر العقود المضافة</h3>
            <table>
                <thead><tr><th>#</th><th>المستأجر</th><th>الوحدة</th><th>القيمة</th></tr></thead>
                <tbody>
                    <?php $q=$pdo->query("SELECT c.*, t.full_name, u.unit_name FROM contracts c JOIN tenants t ON c.tenant_id=t.id JOIN units u ON c.unit_id=u.id ORDER BY c.id DESC LIMIT 5");
                    while($r=$q->fetch()): ?>
                    <tr><td><?= $r['id'] ?></td><td><?= $r['full_name'] ?></td><td><?= $r['unit_name'] ?></td><td><?= number_format($r['total_amount']) ?></td></tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <?php if($p == 'units'): ?>
        <button onclick="show('addUnitModal')" class="btn" style="margin-bottom:20px"><i class="fa-solid fa-plus"></i> إضافة وحدة</button>
        <div class="card">
            <table>
                <thead><tr><th>الوحدة</th><th>النوع</th><th>العدادات (كهرباء/ماء)</th><th>السعر</th><th>الحالة</th></tr></thead>
                <tbody>
                    <?php $q=$pdo->query("SELECT * FROM units"); while($r=$q->fetch()): ?>
                    <tr>
                        <td><?= $r['unit_name'] ?></td>
                        <td>
                            <?php 
                                $types = ['shop'=>'محل تجاري', 'villa'=>'فيلا', 'apartment'=>'شقة', 'land'=>'أرض'];
                                echo $types[$r['type']] ?? $r['type'];
                            ?>
                        </td>
                        <td>⚡ <?= $r['elec_meter_no'] ?> | 💧 <?= $r['water_meter_no'] ?></td>
                        <td><?= number_format($r['yearly_price']) ?></td>
                        <td><span style="color:<?= $r['status']=='rented'?'red':'green' ?>"><?= $r['status'] ?></span></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <?php if($p == 'contracts'): ?>
        <button onclick="show('addContractModal')" class="btn" style="margin-bottom:20px">إنشاء عقد جديد</button>
        <div class="card">
            <table>
                <thead><tr><th>رقم العقد</th><th>المستأجر</th><th>الوحدة</th><th>التاريخ</th><th>طباعة</th></tr></thead>
                <tbody>
                    <?php $q=$pdo->query("SELECT c.*, t.full_name, u.unit_name FROM contracts c JOIN tenants t ON c.tenant_id=t.id JOIN units u ON c.unit_id=u.id"); while($r=$q->fetch()): ?>
                    <tr>
                        <td><?= $r['id'] ?></td>
                        <td><?= $r['full_name'] ?></td>
                        <td><?= $r['unit_name'] ?></td>
                        <td><?= $r['start_date'] ?></td>
                        <td><a href="invoice_print.php?cid=<?= $r['id'] ?>" target="_blank" class="btn" style="padding:5px 10px; font-size:12px"><i class="fa-solid fa-print"></i> طباعة</a></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <?php if($p == 'users' && $me['role']=='admin'): ?>
        <button onclick="show('addUserModal')" class="btn" style="margin-bottom:20px">إضافة موظف</button>
        <div class="card">
            <table>
                <thead><tr><th>الاسم</th><th>المستخدم</th><th>الصلاحية</th><th>الجوال</th></tr></thead>
                <tbody>
                    <?php $q=$pdo->query("SELECT * FROM users"); while($r=$q->fetch()): ?>
                    <tr>
                        <td><?= $r['full_name'] ?></td>
                        <td><?= $r['username'] ?></td>
                        <td><?= $r['role']=='admin'?'مدير':'موظف' ?></td>
                        <td><?= $r['phone'] ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <?php if($p == 'settings'): ?>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px">
            <form method="POST" class="card">
                <h3>⚙️ إعدادات النظام</h3>
                <input type="hidden" name="save_settings" value="1">
                <label>اسم الشركة</label><input type="text" name="set[company_name]" value="<?= getSet('company_name') ?>" class="inp">
                <label>الرقم الضريبي</label><input type="text" name="set[vat_no]" value="<?= getSet('vat_no') ?>" class="inp">
                <button class="btn">حفظ التغييرات</button>
            </form>
            <div class="card">
                <h3>💾 النسخ الاحتياطي</h3>
                <p style="color:#94a3b8">قم بتحميل نسخة كاملة من قاعدة البيانات للحفاظ على بياناتك.</p>
                <form method="POST">
                    <button name="backup_db" class="btn" style="background:#10b981; width:100%"><i class="fa-solid fa-download"></i> تحميل قاعدة البيانات (SQL)</button>
                </form>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if($p == 'profile'): ?>
        <div class="card" style="max-width:500px; margin:auto">
            <h3>تعديل بياناتي</h3>
            <form method="POST">
                <input type="hidden" name="update_profile" value="1">
                <label>الاسم الكامل</label><input type="text" name="name" value="<?= $me['full_name'] ?>" class="inp">
                <label>اسم المستخدم</label><input type="text" name="user" value="<?= $me['username'] ?>" class="inp">
                <label>رقم الجوال</label><input type="text" name="phone" value="<?= $me['phone'] ?>" class="inp">
                <label>كلمة المرور الجديدة (اتركه فارغاً إذا لا تريد التغيير)</label><input type="password" name="pass" class="inp">
                <button class="btn">حفظ بياناتي</button>
            </form>
        </div>
    <?php endif; ?>

</div>

<div id="addUnitModal" class="modal"><div class="modal-content">
    <h3>إضافة وحدة جديدة</h3>
    <form method="POST">
        <input type="hidden" name="add_unit" value="1">
        <select name="pid" class="inp"><?php $q=$pdo->query("SELECT * FROM properties"); while($r=$q->fetch()) echo "<option value='{$r['id']}'>{$r['name']}</option>"; ?></select>
        <div style="display:flex; gap:10px">
            <input type="text" name="name" class="inp" placeholder="اسم الوحدة">
            <select name="type" class="inp">
                <option value="shop">محل تجاري</option>
                <option value="apartment">شقة سكنية</option>
                <option value="villa">فيلا</option>
                <option value="land">أرض</option>
                <option value="warehouse">مستودع</option>
            </select>
        </div>
        <input type="number" name="price" class="inp" placeholder="السعر السنوي">
        <div style="display:flex; gap:10px">
            <input type="text" name="elec" class="inp" placeholder="رقم عداد الكهرباء">
            <input type="text" name="water" class="inp" placeholder="رقم عداد المياه">
        </div>
        <button class="btn">حفظ</button> <button type="button" onclick="hide('addUnitModal')" class="btn" style="background:#334155">إلغاء</button>
    </form>
</div></div>

<div id="addContractModal" class="modal"><div class="modal-content">
    <h3>عقد إيجار جديد</h3>
    <form method="POST" onsubmit="saveSignature()">
        <input type="hidden" name="add_contract" value="1">
        <input type="hidden" name="sig" id="sigField">
        <label>المستأجر</label><select name="tid" class="inp"><?php $q=$pdo->query("SELECT * FROM tenants"); while($r=$q->fetch()) echo "<option value='{$r['id']}'>{$r['full_name']}</option>"; ?></select>
        <label>الوحدة</label><select name="uid" class="inp"><?php $q=$pdo->query("SELECT * FROM units WHERE status='available'"); while($r=$q->fetch()) echo "<option value='{$r['id']}'>{$r['unit_name']} - {$r['type']}</option>"; ?></select>
        <div style="display:flex; gap:10px">
            <input type="date" name="start" class="inp">
            <input type="date" name="end" class="inp">
        </div>
        <input type="number" name="total" class="inp" placeholder="القيمة الإجمالية">
        <select name="cycle" class="inp"><option value="yearly">سنوي</option><option value="monthly">شهري</option></select>
        
        <label>توقيع المستلم (Touch Pad)</label>
        <div style="border:1px solid #334155; border-radius:8px; overflow:hidden">
            <canvas id="sigCanvas" width="500" height="150"></canvas>
        </div>
        <button type="button" onclick="clearSig()" style="background:red; border:none; color:white; padding:5px; border-radius:5px; margin-top:5px; cursor:pointer">مسح التوقيع</button>
        <br><br>
        <button class="btn">إصدار العقد</button> <button type="button" onclick="hide('addContractModal')" class="btn" style="background:#334155">إلغاء</button>
    </form>
</div></div>

<div id="addUserModal" class="modal"><div class="modal-content">
    <h3>موظف جديد</h3>
    <form method="POST">
        <input type="hidden" name="add_user" value="1">
        <input type="text" name="name" class="inp" placeholder="الاسم الكامل">
        <input type="text" name="user" class="inp" placeholder="اسم المستخدم للدخول">
        <input type="password" name="pass" class="inp" placeholder="كلمة المرور">
        <input type="text" name="phone" class="inp" placeholder="الجوال">
        <button class="btn">حفظ</button> <button type="button" onclick="hide('addUserModal')" class="btn" style="background:#334155">إلغاء</button>
    </form>
</div></div>

<script>
    function show(id){document.getElementById(id).style.display='flex'}
    function hide(id){document.getElementById(id).style.display='none'}
    
    // Signature Pad Logic
    var canvas = document.getElementById("sigCanvas");
    var ctx = canvas.getContext("2d");
    var drawing = false;
    
    canvas.addEventListener("mousedown", startDraw);
    canvas.addEventListener("mouseup", stopDraw);
    canvas.addEventListener("mousemove", draw);
    // Touch support
    canvas.addEventListener("touchstart", function(e){e.preventDefault(); startDraw(e.touches[0])});
    canvas.addEventListener("touchend", stopDraw);
    canvas.addEventListener("touchmove", function(e){e.preventDefault(); draw(e.touches[0])});

    function startDraw(e) { drawing = true; ctx.beginPath(); ctx.moveTo(getX(e), getY(e)); }
    function stopDraw() { drawing = false; }
    function draw(e) { if(!drawing) return; ctx.lineWidth = 2; ctx.lineCap = "round"; ctx.lineTo(getX(e), getY(e)); ctx.stroke(); }
    function getX(e) { return e.clientX - canvas.getBoundingClientRect().left; }
    function getY(e) { return e.clientY - canvas.getBoundingClientRect().top; }
    function clearSig() { ctx.clearRect(0, 0, canvas.width, canvas.height); }
    function saveSignature() { document.getElementById('sigField').value = canvas.toDataURL(); }
</script>

</body>
</html>
