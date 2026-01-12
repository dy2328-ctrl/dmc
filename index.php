<?php
require 'db.php';
if(!isset($_SESSION['uid'])) { header("Location: login.php"); exit; }

// === معالجة الطلبات (Backend Logic) ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. إضافة عقار
    if (isset($_POST['add_prop'])) {
        $pdo->prepare("INSERT INTO properties (name, type, address, manager_name, manager_phone) VALUES (?,?,?,?,?)")
            ->execute([$_POST['name'], $_POST['type'], $_POST['address'], $_POST['manager'], $_POST['phone']]);
        header("Location: ?p=properties"); exit;
    }

    // 2. إضافة وحدة (تم إصلاح القائمة)
    if (isset($_POST['add_unit'])) {
        $pdo->prepare("INSERT INTO units (property_id, unit_name, type, yearly_price, elec_meter_no, water_meter_no, status, notes) VALUES (?,?,?,?,?,?,?,?)")
            ->execute([$_POST['pid'], $_POST['name'], $_POST['type'], $_POST['price'], $_POST['elec'], $_POST['water'], 'available', $_POST['notes']]);
        header("Location: ?p=units"); exit;
    }

    // 3. إضافة عقد (مع التوقيع الإلكتروني)
    if (isset($_POST['add_contract'])) {
        $pdo->prepare("INSERT INTO contracts (tenant_id, unit_id, start_date, end_date, total_amount, payment_cycle, signature_img) VALUES (?,?,?,?,?,?,?)")
            ->execute([$_POST['tid'], $_POST['uid'], $_POST['start'], $_POST['end'], $_POST['total'], $_POST['cycle'], $_POST['sig']]);
        $pdo->prepare("UPDATE units SET status='rented' WHERE id=?")->execute([$_POST['uid']]);
        header("Location: ?p=contracts"); exit;
    }

    // 4. إضافة مستأجر
    if (isset($_POST['add_tenant'])) {
        $pdo->prepare("INSERT INTO tenants (full_name, phone, id_number, id_type, email) VALUES (?,?,?,?,?)")
            ->execute([$_POST['name'], $_POST['phone'], $_POST['nid'], $_POST['id_type'], $_POST['email']]);
        header("Location: ?p=tenants"); exit;
    }

    // 5. الموظفين والإعدادات والنسخ
    if (isset($_POST['add_user'])) {
        $pdo->prepare("INSERT INTO users (full_name, username, password, role, phone) VALUES (?,?,?,?,?)")
            ->execute([$_POST['name'], $_POST['user'], password_hash($_POST['pass'], PASSWORD_DEFAULT), 'staff', $_POST['phone']]);
        header("Location: ?p=users"); exit;
    }
    if (isset($_POST['update_profile'])) {
        $sql = empty($_POST['pass']) ? "UPDATE users SET full_name=?, username=?, phone=? WHERE id=?" : "UPDATE users SET full_name=?, username=?, phone=?, password=? WHERE id=?";
        $params = empty($_POST['pass']) ? [$_POST['name'], $_POST['user'], $_POST['phone'], $_SESSION['uid']] : [$_POST['name'], $_POST['user'], $_POST['phone'], password_hash($_POST['pass'], PASSWORD_DEFAULT), $_SESSION['uid']];
        $pdo->prepare($sql)->execute($params);
        header("Location: ?p=profile&success=1"); exit;
    }
    if (isset($_POST['save_settings'])) {
        foreach($_POST['set'] as $k=>$v) saveSet($k,$v);
        header("Location: ?p=settings"); exit;
    }
    if (isset($_POST['backup'])) {
        // منطق مبسط للنسخ الاحتياطي
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        $sqlScript = "-- BACKUP " . date('Y-m-d') . "\n\n";
        foreach ($tables as $table) {
            $create = $pdo->query("SHOW CREATE TABLE $table")->fetch(PDO::FETCH_NUM);
            $sqlScript .= $create[1] . ";\n\n";
            $rows = $pdo->query("SELECT * FROM $table")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $row) {
                $sqlScript .= "INSERT INTO $table VALUES('" . implode("','", array_map('addslashes', array_values($row))) . "');\n";
            }
        }
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename=backup.sql');
        echo $sqlScript; exit;
    }
}

$p = $_GET['p'] ?? 'dashboard';
$me = $pdo->query("SELECT * FROM users WHERE id=".$_SESSION['uid'])->fetch();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>دار الميار - النظام الذكي</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* === GEMINI DESIGN SYSTEM === */
        :root {
            --bg: #020617; --sidebar: #0f172a; --card: #1e293b; 
            --text-main: #f8fafc; --text-muted: #94a3b8;
            --primary: #6366f1; --accent: #8b5cf6; --highlight: #a855f7;
            --border: #334155;
            --glass: rgba(30, 41, 59, 0.8);
        }
        
        body { margin:0; height:100vh; font-family:'Tajawal'; background:var(--bg); color:var(--text-main); display:flex; overflow:hidden; }
        
        /* SIDEBAR */
        .sidebar { width:280px; background:var(--sidebar); border-left:1px solid var(--border); display:flex; flex-direction:column; padding:20px; z-index:10; }
        .logo-area { 
            text-align:center; padding:20px 0; margin-bottom:30px; border-bottom:1px solid var(--border);
        }
        .logo-box {
            width:120px; height:120px; background:white; border-radius:50%; margin:0 auto 15px; 
            display:flex; align-items:center; justify-content:center;
            box-shadow: 0 0 40px rgba(99, 102, 241, 0.4); /* Glow Effect */
            border: 4px solid var(--primary);
        }
        .logo-box img { max-width:80%; }
        .brand-name { font-size:20px; font-weight:800; background: linear-gradient(to right, #fff, #a5b4fc); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        
        .nav-link { display:flex; align-items:center; gap:12px; padding:15px; border-radius:12px; color:var(--text-muted); text-decoration:none; margin-bottom:8px; font-weight:500; transition:0.3s; font-size:16px; }
        .nav-link:hover, .nav-link.active { background: linear-gradient(90deg, var(--primary), var(--accent)); color:white; box-shadow:0 5px 15px rgba(99, 102, 241, 0.3); transform:translateX(-5px); }
        
        /* MAIN CONTENT */
        .main { flex:1; padding:40px; overflow-y:auto; background-image: radial-gradient(at top right, #1e1b4b 0%, transparent 40%); }
        .page-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:40px; }
        .page-title { font-size:28px; font-weight:800; color:white; text-shadow:0 0 20px rgba(99,102,241,0.5); }
        
        /* CARDS & TABLES */
        .card { background:var(--card); border-radius:20px; border:1px solid var(--border); padding:25px; margin-bottom:25px; box-shadow:0 10px 30px rgba(0,0,0,0.2); }
        table { width:100%; border-collapse:collapse; }
        th { text-align:right; padding:18px; color:var(--text-muted); border-bottom:1px solid var(--border); font-size:15px; }
        td { padding:18px; border-bottom:1px solid var(--border); font-size:16px; font-weight:500; }
        tr:hover td { background:rgba(255,255,255,0.03); }
        
        /* BUTTONS */
        .btn { padding:12px 25px; border-radius:12px; border:none; background: linear-gradient(135deg, var(--primary), var(--accent)); color:white; font-weight:bold; cursor:pointer; font-size:15px; display:inline-flex; align-items:center; gap:8px; text-decoration:none; transition:0.3s; }
        .btn:hover { transform:translateY(-2px); box-shadow:0 10px 20px rgba(99, 102, 241, 0.4); }
        .btn-sec { background: #334155; }
        
        /* MODALS (Fixed) */
        .modal { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); backdrop-filter:blur(8px); z-index:1000; justify-content:center; align-items:center; opacity:0; transition:opacity 0.3s; }
        .modal.active { opacity:1; }
        .modal-content { background:#1e293b; width:700px; padding:40px; border-radius:24px; border:1px solid #475569; box-shadow:0 25px 60px rgba(0,0,0,0.6); transform:scale(0.9); transition:transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); max-height:90vh; overflow-y:auto; }
        .modal.active .modal-content { transform:scale(1); }
        
        /* INPUTS */
        .inp-grid { display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:20px; }
        .inp-group { margin-bottom:15px; }
        .inp-group label { display:block; color:var(--text-muted); margin-bottom:8px; font-size:14px; }
        .inp { width:100%; padding:14px; background:#0f172a; border:1px solid #334155; border-radius:12px; color:white; font-size:16px; outline:none; transition:0.3s; box-sizing:border-box; font-family:'Tajawal'; }
        .inp:focus { border-color:var(--primary); box-shadow:0 0 0 3px rgba(99, 102, 241, 0.2); }
        .full { grid-column:span 2; }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="logo-area">
            <div class="logo-box"><img src="logo.png" onerror="this.src='https://via.placeholder.com/100'"></div>
            <div class="brand-name">دار الميار للمقاولات</div>
        </div>
        <a href="?p=dashboard" class="nav-link <?= $p=='dashboard'?'active':'' ?>"><i class="fa-solid fa-chart-pie"></i> لوحة التحكم</a>
        <a href="?p=properties" class="nav-link <?= $p=='properties'?'active':'' ?>"><i class="fa-solid fa-city"></i> إدارة العقارات</a>
        <a href="?p=units" class="nav-link <?= $p=='units'?'active':'' ?>"><i class="fa-solid fa-door-open"></i> الوحدات السكنية</a>
        <a href="?p=contracts" class="nav-link <?= $p=='contracts'?'active':'' ?>"><i class="fa-solid fa-file-contract"></i> العقود الإيجارية</a>
        <a href="?p=tenants" class="nav-link <?= $p=='tenants'?'active':'' ?>"><i class="fa-solid fa-users"></i> المستأجرين</a>
        <?php if($me['role']=='admin'): ?>
        <a href="?p=users" class="nav-link <?= $p=='users'?'active':'' ?>"><i class="fa-solid fa-user-shield"></i> إدارة الموظفين</a>
        <a href="?p=settings" class="nav-link <?= $p=='settings'?'active':'' ?>"><i class="fa-solid fa-gear"></i> الإعدادات والنسخ</a>
        <?php endif; ?>
        <a href="logout.php" class="nav-link" style="color:#f87171; margin-top:auto"><i class="fa-solid fa-power-off"></i> تسجيل الخروج</a>
    </div>

    <div class="main">
        <div class="page-header">
            <div class="page-title">
                <?php 
                $titles = ['dashboard'=>'لوحة التحكم الذكية', 'properties'=>'إدارة العقارات', 'units'=>'الوحدات', 'contracts'=>'العقود', 'users'=>'إدارة الموظفين', 'settings'=>'الإعدادات'];
                echo $titles[$p] ?? 'النظام';
                ?>
            </div>
            <a href="?p=profile" class="btn btn-sec"><i class="fa-solid fa-user"></i> <?= $me['full_name'] ?></a>
        </div>

        <?php if($p == 'dashboard'): ?>
            <div class="inp-grid" style="grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));">
                <div class="card" style="border-right:4px solid var(--primary)">
                    <h3 style="margin:0; color:var(--text-muted)">الإيرادات</h3>
                    <div style="font-size:32px; font-weight:800; margin-top:10px"><?= number_format($pdo->query("SELECT SUM(total_amount) FROM contracts")->fetchColumn()) ?> <small style="font-size:14px">ر.س</small></div>
                </div>
                <div class="card" style="border-right:4px solid var(--highlight)">
                    <h3 style="margin:0; color:var(--text-muted)">الوحدات</h3>
                    <div style="font-size:32px; font-weight:800; margin-top:10px"><?= $pdo->query("SELECT count(*) FROM units")->fetchColumn() ?> <small style="font-size:14px">وحدة</small></div>
                </div>
                <div class="card" style="border-right:4px solid #10b981">
                    <h3 style="margin:0; color:var(--text-muted)">العقود النشطة</h3>
                    <div style="font-size:32px; font-weight:800; margin-top:10px"><?= $pdo->query("SELECT count(*) FROM contracts WHERE status='active'")->fetchColumn() ?> <small style="font-size:14px">عقد</small></div>
                </div>
            </div>
            
            <div class="card">
                <h3><i class="fa-solid fa-clock-rotate-left"></i> آخر العمليات</h3>
                <table>
                    <thead><tr><th>العملية</th><th>المستأجر</th><th>القيمة</th><th>الحالة</th></tr></thead>
                    <tbody>
                        <?php $q=$pdo->query("SELECT c.*, t.full_name FROM contracts c JOIN tenants t ON c.tenant_id=t.id ORDER BY c.id DESC LIMIT 5"); while($r=$q->fetch()): ?>
                        <tr>
                            <td>إصدار عقد جديد #<?= $r['id'] ?></td>
                            <td><?= $r['full_name'] ?></td>
                            <td><?= number_format($r['total_amount']) ?></td>
                            <td><span style="background:rgba(16,185,129,0.2); color:#34d399; padding:4px 10px; border-radius:20px; font-size:12px">ناجح</span></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <?php if($p == 'properties'): ?>
            <button onclick="openM('propM')" class="btn" style="margin-bottom:20px"><i class="fa-solid fa-plus"></i> إضافة عقار جديد</button>
            <div class="card">
                <table>
                    <thead><tr><th>اسم العقار</th><th>النوع</th><th>المسؤول</th><th>عدد الوحدات</th></tr></thead>
                    <tbody>
                        <?php $q=$pdo->query("SELECT p.*, (SELECT count(*) FROM units WHERE property_id=p.id) as ucount FROM properties p"); while($r=$q->fetch()): ?>
                        <tr>
                            <td><i class="fa-solid fa-building" style="color:var(--primary); margin-left:10px"></i> <?= $r['name'] ?></td>
                            <td><?= $r['type'] ?></td>
                            <td><?= $r['manager_name'] ?> <br> <small style="color:#64748b"><?= $r['manager_phone'] ?></small></td>
                            <td><span style="background:#334155; padding:5px 10px; border-radius:8px"><?= $r['ucount'] ?> وحدة</span></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <?php if($p == 'units'): ?>
            <button onclick="openM('unitM')" class="btn" style="margin-bottom:20px"><i class="fa-solid fa-plus"></i> إضافة وحدة جديدة</button>
            <div class="card">
                <table>
                    <thead><tr><th>الوحدة</th><th>النوع</th><th>العدادات (ك/م)</th><th>السعر</th><th>الحالة</th></tr></thead>
                    <tbody>
                        <?php $q=$pdo->query("SELECT u.*, p.name as pname FROM units u JOIN properties p ON u.property_id=p.id"); while($r=$q->fetch()): ?>
                        <tr>
                            <td><b><?= $r['unit_name'] ?></b> <br> <small><?= $r['pname'] ?></small></td>
                            <td>
                                <?php 
                                $types = ['shop'=>'🛒 محل تجاري', 'villa'=>'🏡 فيلا', 'apartment'=>'🏢 شقة', 'land'=>'🌍 أرض', 'warehouse'=>'🏭 مستودع'];
                                echo $types[$r['type']] ?? $r['type'];
                                ?>
                            </td>
                            <td><span style="color:#eab308">⚡<?= $r['elec_meter_no'] ?></span> | <span style="color:#3b82f6">💧<?= $r['water_meter_no'] ?></span></td>
                            <td><?= number_format($r['yearly_price']) ?></td>
                            <td>
                                <span style="background:<?= $r['status']=='rented'?'rgba(239,68,68,0.2)':'rgba(16,185,129,0.2)' ?>; color:<?= $r['status']=='rented'?'#f87171':'#34d399' ?>; padding:5px 10px; border-radius:8px">
                                    <?= $r['status']=='rented'?'مؤجر':'متاح' ?>
                                </span>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <?php if($p == 'contracts'): ?>
            <button onclick="openM('conM')" class="btn" style="margin-bottom:20px"><i class="fa-solid fa-file-signature"></i> إنشاء عقد جديد</button>
            <div class="card">
                <table>
                    <thead><tr><th>رقم العقد</th><th>المستأجر</th><th>الوحدة</th><th>المدة</th><th>طباعة</th></tr></thead>
                    <tbody>
                        <?php $q=$pdo->query("SELECT c.*, t.full_name, u.unit_name FROM contracts c JOIN tenants t ON c.tenant_id=t.id JOIN units u ON c.unit_id=u.id ORDER BY c.id DESC"); 
                        while($r=$q->fetch()): ?>
                        <tr>
                            <td>#<?= $r['id'] ?></td>
                            <td><?= $r['full_name'] ?></td>
                            <td><?= $r['unit_name'] ?></td>
                            <td><?= $r['start_date'] ?> <i class="fa-solid fa-arrow-left"></i> <?= $r['end_date'] ?></td>
                            <td><a href="invoice_print.php?cid=<?= $r['id'] ?>" target="_blank" class="btn btn-sec" style="padding:5px 15px"><i class="fa-solid fa-print"></i></a></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        
        <?php if($p == 'tenants'): ?>
            <button onclick="openM('tenM')" class="btn" style="margin-bottom:20px"><i class="fa-solid fa-user-plus"></i> مستأجر جديد</button>
            <div class="card">
                <table>
                    <thead><tr><th>الاسم</th><th>الهوية</th><th>الجوال</th><th>البريد</th></tr></thead>
                    <tbody>
                        <?php $q=$pdo->query("SELECT * FROM tenants"); while($r=$q->fetch()): ?>
                        <tr><td><?= $r['full_name'] ?></td><td><?= $r['id_number'] ?> (<?= $r['id_type'] ?>)</td><td><?= $r['phone'] ?></td><td><?= $r['email'] ?></td></tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <?php if($p == 'settings'): ?>
            <div class="inp-grid">
                <form method="POST" class="card">
                    <h3>⚙️ إعدادات النظام</h3>
                    <input type="hidden" name="save_settings" value="1">
                    <div class="inp-group"><label>اسم الشركة</label><input type="text" name="set[company_name]" value="<?= getSet('company_name') ?>" class="inp"></div>
                    <div class="inp-group"><label>الرقم الضريبي</label><input type="text" name="set[vat_no]" value="<?= getSet('vat_no') ?>" class="inp"></div>
                    <button class="btn">حفظ الإعدادات</button>
                </form>
                <div class="card">
                    <h3>💾 النسخ الاحتياطي</h3>
                    <p style="color:#94a3b8; margin-bottom:20px">تحميل قاعدة البيانات بالكامل للحفاظ عليها من الضياع.</p>
                    <form method="POST"><button name="backup" class="btn" style="width:100%; justify-content:center"><i class="fa-solid fa-cloud-arrow-down"></i> تحميل النسخة (SQL)</button></form>
                </div>
            </div>
        <?php endif; ?>

        <?php if($p == 'users'): ?>
            <button onclick="openM('userM')" class="btn" style="margin-bottom:20px">موظف جديد</button>
            <div class="card">
                <table>
                    <thead><tr><th>الاسم</th><th>اسم المستخدم</th><th>الصلاحية</th><th>الجوال</th></tr></thead>
                    <tbody>
                        <?php $q=$pdo->query("SELECT * FROM users"); while($r=$q->fetch()): ?>
                        <tr><td><?= $r['full_name'] ?></td><td><?= $r['username'] ?></td><td><?= $r['role'] ?></td><td><?= $r['phone'] ?></td></tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

    </div>

    <div id="propM" class="modal"><div class="modal-content">
        <h2 style="margin-top:0">🏢 عقار جديد</h2>
        <form method="POST">
            <input type="hidden" name="add_prop" value="1">
            <div class="inp-grid">
                <div class="full inp-group"><label>اسم العقار</label><input type="text" name="name" class="inp" placeholder="مثال: عمارة النخيل" required></div>
                <div class="inp-group"><label>نوع العقار</label><select name="type" class="inp"><option>عمارة سكنية</option><option>مجمع تجاري</option><option>أرض خام</option></select></div>
                <div class="inp-group"><label>العنوان</label><input type="text" name="address" class="inp"></div>
                <div class="inp-group"><label>مدير العقار</label><input type="text" name="manager" class="inp"></div>
                <div class="inp-group"><label>جوال المدير</label><input type="text" name="phone" class="inp"></div>
            </div>
            <div style="display:flex; gap:15px">
                <button class="btn" style="flex:1; justify-content:center">حفظ العقار</button>
                <button type="button" onclick="closeM('propM')" class="btn btn-sec">إلغاء</button>
            </div>
        </form>
    </div></div>

    <div id="unitM" class="modal"><div class="modal-content">
        <h2 style="margin-top:0">🏠 وحدة جديدة</h2>
        <form method="POST">
            <input type="hidden" name="add_unit" value="1">
            <div class="inp-grid">
                <div class="full inp-group">
                    <label>تابع للعقار</label>
                    <select name="pid" class="inp"><?php $q=$pdo->query("SELECT * FROM properties"); while($r=$q->fetch()) echo "<option value='{$r['id']}'>{$r['name']}</option>"; ?></select>
                </div>
                <div class="inp-group"><label>اسم الوحدة</label><input type="text" name="name" class="inp" placeholder="شقة 101 / محل 5" required></div>
                <div class="inp-group">
                    <label>نوع الوحدة</label>
                    <select name="type" class="inp">
                        <option value="apartment">شقة سكنية</option>
                        <option value="shop">محل تجاري</option>
                        <option value="villa">فيلا</option>
                        <option value="land">أرض</option>
                        <option value="warehouse">مستودع</option>
                        <option value="office">مكتب</option>
                    </select>
                </div>
                <div class="inp-group"><label>السعر السنوي</label><input type="number" name="price" class="inp"></div>
                <div class="inp-group"><label>رقم عداد الكهرباء</label><input type="text" name="elec" class="inp"></div>
                <div class="inp-group"><label>رقم عداد المياه</label><input type="text" name="water" class="inp"></div>
                <div class="full inp-group"><label>ملاحظات</label><input type="text" name="notes" class="inp"></div>
            </div>
            <div style="display:flex; gap:15px">
                <button class="btn" style="flex:1; justify-content:center">حفظ الوحدة</button>
                <button type="button" onclick="closeM('unitM')" class="btn btn-sec">إلغاء</button>
            </div>
        </form>
    </div></div>

    <div id="conM" class="modal"><div class="modal-content">
        <h2 style="margin-top:0">📝 عقد جديد</h2>
        <form method="POST" onsubmit="saveSig()">
            <input type="hidden" name="add_contract" value="1">
            <input type="hidden" name="sig" id="sigField">
            <div class="inp-grid">
                <div class="inp-group"><label>المستأجر</label><select name="tid" class="inp"><?php $q=$pdo->query("SELECT * FROM tenants"); while($r=$q->fetch()) echo "<option value='{$r['id']}'>{$r['full_name']}</option>"; ?></select></div>
                <div class="inp-group"><label>الوحدة</label><select name="uid" class="inp"><?php $q=$pdo->query("SELECT * FROM units WHERE status='available'"); while($r=$q->fetch()) echo "<option value='{$r['id']}'>{$r['unit_name']} ({$r['type']})</option>"; ?></select></div>
                <div class="inp-group"><label>تاريخ البدء</label><input type="date" name="start" class="inp"></div>
                <div class="inp-group"><label>تاريخ الانتهاء</label><input type="date" name="end" class="inp"></div>
                <div class="inp-group"><label>القيمة الإجمالية</label><input type="number" name="total" class="inp"></div>
                <div class="inp-group"><label>الدفعات</label><select name="cycle" class="inp"><option value="yearly">دفعة واحدة (سنوي)</option><option value="monthly">شهري</option></select></div>
            </div>
            
            <label style="color:#94a3b8; display:block; margin-bottom:5px">توقيع المستأجر (Touch Pad)</label>
            <div style="background:white; border-radius:12px; overflow:hidden; border:2px dashed #64748b">
                <canvas id="sigCanvas" width="600" height="200" style="width:100%; touch-action:none;"></canvas>
            </div>
            <button type="button" onclick="clearSig()" style="background:#ef4444; color:white; border:none; padding:5px 10px; border-radius:5px; margin-top:5px; cursor:pointer">مسح التوقيع</button>
            
            <div style="display:flex; gap:15px; margin-top:20px">
                <button class="btn" style="flex:1; justify-content:center">إصدار العقد</button>
                <button type="button" onclick="closeM('conM')" class="btn btn-sec">إلغاء</button>
            </div>
        </form>
    </div></div>

    <div id="tenM" class="modal"><div class="modal-content">
        <h2 style="margin-top:0">👤 مستأجر جديد</h2>
        <form method="POST">
            <input type="hidden" name="add_tenant" value="1">
            <div class="inp-grid">
                <div class="full inp-group"><label>الاسم الكامل</label><input type="text" name="name" class="inp" required></div>
                <div class="inp-group"><label>نوع الهوية</label><select name="id_type" class="inp"><option value="national">هوية وطنية</option><option value="iqama">إقامة</option><option value="commercial">سجل تجاري</option></select></div>
                <div class="inp-group"><label>رقم الهوية/السجل</label><input type="text" name="nid" class="inp"></div>
                <div class="inp-group"><label>رقم الجوال</label><input type="text" name="phone" class="inp"></div>
                <div class="inp-group"><label>البريد الإلكتروني</label><input type="email" name="email" class="inp"></div>
            </div>
            <button class="btn" style="width:100%; justify-content:center">حفظ البيانات</button>
            <button type="button" onclick="closeM('tenM')" class="btn btn-sec" style="width:100%; margin-top:10px; justify-content:center">إلغاء</button>
        </form>
    </div></div>

    <div id="userM" class="modal"><div class="modal-content">
        <h2>موظف جديد</h2>
        <form method="POST">
            <input type="hidden" name="add_user" value="1">
            <div class="inp-grid">
                <div class="full inp-group"><label>الاسم</label><input type="text" name="name" class="inp"></div>
                <div class="inp-group"><label>اسم المستخدم</label><input type="text" name="user" class="inp"></div>
                <div class="inp-group"><label>كلمة المرور</label><input type="password" name="pass" class="inp"></div>
                <div class="inp-group"><label>الجوال</label><input type="text" name="phone" class="inp"></div>
            </div>
            <button class="btn" style="width:100%">حفظ</button>
            <button type="button" onclick="closeM('userM')" class="btn btn-sec" style="width:100%; margin-top:10px">إلغاء</button>
        </form>
    </div></div>

    <script>
        function openM(id) { 
            let m = document.getElementById(id);
            m.style.display='flex'; 
            setTimeout(()=>m.classList.add('active'),10); 
        }
        function closeM(id) { 
            let m = document.getElementById(id);
            m.classList.remove('active'); 
            setTimeout(()=>m.style.display='none',300); 
        }

        // منطق التوقيع الإلكتروني
        const canvas = document.getElementById('sigCanvas');
        const ctx = canvas.getContext('2d');
        let writing = false;

        function start(e) { writing=true; ctx.beginPath(); var p=getPos(e); ctx.moveTo(p.x, p.y); }
        function end() { writing=false; }
        function move(e) { 
            if(!writing) return; 
            e.preventDefault(); 
            var p=getPos(e); 
            ctx.lineWidth=3; ctx.lineCap='round'; ctx.lineTo(p.x, p.y); ctx.stroke(); 
        }
        function getPos(e) {
            var rect = canvas.getBoundingClientRect();
            var x = (e.clientX || e.touches[0].clientX) - rect.left;
            var y = (e.clientY || e.touches[0].clientY) - rect.top;
            return {x:x, y:y};
        }
        
        canvas.addEventListener('mousedown', start); canvas.addEventListener('mouseup', end); canvas.addEventListener('mousemove', move);
        canvas.addEventListener('touchstart', start); canvas.addEventListener('touchend', end); canvas.addEventListener('touchmove', move);

        function clearSig() { ctx.clearRect(0,0,canvas.width,canvas.height); }
        function saveSig() { document.getElementById('sigField').value = canvas.toDataURL(); }
    </script>
</body>
</html>
