<?php
require 'db.php';
if(!isset($_SESSION['uid'])) { header("Location: login.php"); exit; }

// دالة رفع الصور
function uploadFile($file) {
    if(isset($file) && $file['error'] == 0) {
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $name = uniqid() . '.' . $ext;
        if(move_uploaded_file($file['tmp_name'], 'uploads/' . $name)) return 'uploads/' . $name;
    }
    return null;
}

// === BACKEND LOGIC ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. إضافة عقار (مع صورة)
    if (isset($_POST['add_prop'])) {
        $photo = uploadFile($_FILES['photo']);
        $pdo->prepare("INSERT INTO properties (name, type, address, manager_name, manager_phone, photo) VALUES (?,?,?,?,?,?)")
            ->execute([$_POST['name'], $_POST['type'], $_POST['address'], $_POST['manager'], $_POST['phone'], $photo]);
        header("Location: ?p=properties"); exit;
    }

    // 2. إضافة وحدة (مع صورة وملاحظات)
    if (isset($_POST['add_unit'])) {
        $photo = uploadFile($_FILES['photo']);
        $pdo->prepare("INSERT INTO units (property_id, unit_name, type, yearly_price, elec_meter_no, water_meter_no, status, notes, photo) VALUES (?,?,?,?,?,?,?,?,?)")
            ->execute([$_POST['pid'], $_POST['name'], $_POST['type'], $_POST['price'], $_POST['elec'], $_POST['water'], 'available', $_POST['notes'], $photo]);
        header("Location: ?p=units"); exit;
    }

    // 3. إضافة مستأجر (مع صور الهوية)
    if (isset($_POST['add_tenant'])) {
        $id_photo = uploadFile($_FILES['id_photo']);
        $personal_photo = uploadFile($_FILES['personal_photo']);
        $pdo->prepare("INSERT INTO tenants (full_name, phone, id_number, id_type, email, address, id_photo, personal_photo) VALUES (?,?,?,?,?,?,?,?)")
            ->execute([$_POST['name'], $_POST['phone'], $_POST['nid'], $_POST['id_type'], $_POST['email'], $_POST['address'], $id_photo, $personal_photo]);
        header("Location: ?p=tenants"); exit;
    }

    // 4. عقد جديد
    if (isset($_POST['add_contract'])) {
        $pdo->prepare("INSERT INTO contracts (tenant_id, unit_id, start_date, end_date, total_amount, payment_cycle, signature_img) VALUES (?,?,?,?,?,?,?)")
            ->execute([$_POST['tid'], $_POST['uid'], $_POST['start'], $_POST['end'], $_POST['total'], $_POST['cycle'], $_POST['sig']]);
        $pdo->prepare("UPDATE units SET status='rented' WHERE id=?")->execute([$_POST['uid']]);
        header("Location: ?p=contracts"); exit;
    }

    // 5. إدارة الموظفين (للأدمن فقط)
    if (isset($_POST['add_user'])) {
        $pdo->prepare("INSERT INTO users (full_name, username, password, role, phone) VALUES (?,?,?,?,?)")
            ->execute([$_POST['name'], $_POST['user'], password_hash($_POST['pass'], PASSWORD_DEFAULT), $_POST['role'], $_POST['phone']]);
        header("Location: ?p=users"); exit;
    }

    // 6. الإعدادات (للأدمن فقط)
    if (isset($_POST['save_settings'])) {
        foreach($_POST['set'] as $k=>$v) saveSet($k,$v);
        if(!empty($_FILES['logo']['name'])) {
            $logo = uploadFile($_FILES['logo']);
            saveSet('logo', $logo);
        }
        header("Location: ?p=settings"); exit;
    }
    
    // النسخ الاحتياطي
    if (isset($_POST['backup'])) {
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        $sql = "-- BACKUP " . date('Y-m-d') . "\n\n";
        foreach ($tables as $table) {
            $row2 = $pdo->query("SHOW CREATE TABLE $table")->fetch(PDO::FETCH_NUM);
            $sql .= $row2[1] . ";\n\n";
            $rows = $pdo->query("SELECT * FROM $table")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $row) {
                $sql .= "INSERT INTO $table VALUES('" . implode("','", array_map('addslashes', array_values($row))) . "');\n";
            }
        }
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename=backup.sql');
        echo $sql; exit;
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
    <title>دار الميار - النظام المتكامل</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root { --bg:#020617; --sidebar:#0f172a; --card:#1e293b; --primary:#6366f1; --accent:#8b5cf6; --text:#f8fafc; }
        body { margin:0; font-family:'Tajawal'; background:var(--bg); color:var(--text); display:flex; height:100vh; overflow:hidden; }
        
        .sidebar { width:280px; background:var(--sidebar); border-left:1px solid #334155; display:flex; flex-direction:column; padding:20px; z-index:10; }
        .logo-box { width:100px; height:100px; background:white; border-radius:50%; margin:0 auto 15px; display:flex; align-items:center; justify-content:center; border:4px solid var(--primary); box-shadow:0 0 30px rgba(99,102,241,0.4); }
        .nav-link { display:flex; align-items:center; gap:12px; padding:15px; border-radius:12px; color:#94a3b8; text-decoration:none; margin-bottom:8px; font-weight:500; transition:0.3s; }
        .nav-link:hover, .nav-link.active { background:linear-gradient(90deg,var(--primary),var(--accent)); color:white; transform:translateX(-5px); box-shadow:0 5px 15px rgba(99,102,241,0.3); }
        
        .main { flex:1; padding:40px; overflow-y:auto; background-image:radial-gradient(at top right,#1e1b4b 0%,transparent 40%); }
        .card { background:var(--card); border-radius:20px; border:1px solid #334155; padding:25px; margin-bottom:25px; box-shadow:0 10px 30px rgba(0,0,0,0.3); }
        table { width:100%; border-collapse:collapse; }
        th { text-align:right; padding:15px; color:#94a3b8; border-bottom:1px solid #334155; }
        td { padding:15px; border-bottom:1px solid #334155; vertical-align:middle; }
        .btn { padding:12px 25px; border-radius:12px; border:none; background:linear-gradient(135deg,var(--primary),var(--accent)); color:white; cursor:pointer; font-family:'Tajawal'; font-weight:bold; display:inline-flex; align-items:center; gap:8px; text-decoration:none; transition:0.3s; }
        .btn:hover { transform:translateY(-2px); box-shadow:0 10px 20px rgba(99,102,241,0.4); }
        .btn-sec { background:#334155; }
        .badge { padding:5px 10px; border-radius:15px; font-size:12px; font-weight:bold; }
        
        /* Modal */
        .modal { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); backdrop-filter:blur(8px); z-index:1000; justify-content:center; align-items:center; opacity:0; transition:0.3s; }
        .modal.active { opacity:1; }
        .modal-content { background:#1e293b; width:700px; padding:40px; border-radius:24px; border:1px solid #475569; max-height:90vh; overflow-y:auto; transform:scale(0.9); transition:0.3s; }
        .modal.active .modal-content { transform:scale(1); }
        
        .inp-grid { display:grid; grid-template-columns:1fr 1fr; gap:20px; }
        .inp-group label { display:block; color:#94a3b8; margin-bottom:8px; font-size:14px; }
        .inp { width:100%; padding:14px; background:#0f172a; border:1px solid #334155; border-radius:12px; color:white; outline:none; box-sizing:border-box; font-family:'Tajawal'; }
        .inp:focus { border-color:var(--primary); }
        .full { grid-column:span 2; }
        
        /* Thumbnail */
        .thumb { width:40px; height:40px; border-radius:8px; object-fit:cover; border:1px solid #475569; }
    </style>
</head>
<body>

<div class="sidebar">
    <div style="text-align:center; margin-bottom:30px">
        <div class="logo-box"><img src="<?= getSet('logo') ?>" onerror="this.src='logo.png'" style="max-width:80%; max-height:80%"></div>
        <h3 style="margin:0">دار الميار للمقاولات</h3>
    </div>
    <a href="?p=dashboard" class="nav-link <?= $p=='dashboard'?'active':'' ?>"><i class="fa-solid fa-chart-pie"></i> لوحة التحكم</a>
    <a href="?p=properties" class="nav-link <?= $p=='properties'?'active':'' ?>"><i class="fa-solid fa-city"></i> العقارات</a>
    <a href="?p=units" class="nav-link <?= $p=='units'?'active':'' ?>"><i class="fa-solid fa-door-open"></i> الوحدات</a>
    <a href="?p=contracts" class="nav-link <?= $p=='contracts'?'active':'' ?>"><i class="fa-solid fa-file-contract"></i> العقود</a>
    <a href="?p=tenants" class="nav-link <?= $p=='tenants'?'active':'' ?>"><i class="fa-solid fa-users"></i> المستأجرين</a>
    <?php if($me['role']=='admin'): ?>
    <a href="?p=users" class="nav-link <?= $p=='users'?'active':'' ?>"><i class="fa-solid fa-user-shield"></i> الموظفين والصلاحيات</a>
    <a href="?p=settings" class="nav-link <?= $p=='settings'?'active':'' ?>"><i class="fa-solid fa-gear"></i> الإعدادات</a>
    <?php endif; ?>
    <a href="logout.php" class="nav-link" style="color:#f87171; margin-top:auto"><i class="fa-solid fa-power-off"></i> خروج</a>
</div>

<div class="main">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:40px">
        <h1 style="margin:0; font-size:28px"><?= ucfirst($p) ?></h1>
        <div style="display:flex; align-items:center; gap:10px; background:#334155; padding:8px 15px; border-radius:30px">
            <i class="fa-solid fa-user"></i> <?= $me['full_name'] ?> (<?= $me['role']=='admin'?'مدير':'موظف' ?>)
        </div>
    </div>

    <?php if($p == 'dashboard'): ?>
    <div class="inp-grid" style="grid-template-columns:repeat(auto-fit, minmax(250px, 1fr)); margin-bottom:30px">
        <div class="card" style="border-right:4px solid #10b981">
            <h3 style="margin:0; color:#94a3b8">الإيرادات</h3>
            <div style="font-size:32px; font-weight:800; margin-top:10px"><?= number_format($pdo->query("SELECT SUM(total_amount) FROM contracts")->fetchColumn()) ?></div>
        </div>
        <div class="card" style="border-right:4px solid #6366f1">
            <h3 style="margin:0; color:#94a3b8">الوحدات</h3>
            <div style="font-size:32px; font-weight:800; margin-top:10px"><?= $pdo->query("SELECT count(*) FROM units")->fetchColumn() ?></div>
        </div>
        <div class="card" style="border-right:4px solid #f59e0b">
            <h3 style="margin:0; color:#94a3b8">المستأجرين</h3>
            <div style="font-size:32px; font-weight:800; margin-top:10px"><?= $pdo->query("SELECT count(*) FROM tenants")->fetchColumn() ?></div>
        </div>
    </div>
    <?php endif; ?>

    <?php if($p == 'properties'): ?>
    <?php if($me['role']=='admin'): ?><button onclick="openM('propM')" class="btn" style="margin-bottom:20px"><i class="fa-solid fa-plus"></i> إضافة عقار</button><?php endif; ?>
    <div class="card">
        <table>
            <thead><tr><th>صورة</th><th>الاسم</th><th>النوع</th><th>المسؤول</th><th>الموقع</th></tr></thead>
            <tbody>
                <?php $q=$pdo->query("SELECT * FROM properties"); while($r=$q->fetch()): ?>
                <tr>
                    <td><img src="<?= $r['photo'] ?: 'logo.png' ?>" class="thumb"></td>
                    <td><b><?= $r['name'] ?></b></td>
                    <td><?= $r['type'] ?></td>
                    <td><?= $r['manager_name'] ?></td>
                    <td><?= $r['address'] ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <?php if($p == 'units'): ?>
    <?php if($me['role']=='admin'): ?><button onclick="openM('unitM')" class="btn" style="margin-bottom:20px"><i class="fa-solid fa-plus"></i> إضافة وحدة</button><?php endif; ?>
    <div class="card">
        <table>
            <thead><tr><th>صورة</th><th>الوحدة</th><th>النوع</th><th>السعر</th><th>العدادات</th><th>الحالة</th></tr></thead>
            <tbody>
                <?php $q=$pdo->query("SELECT u.*, p.name as pname FROM units u JOIN properties p ON u.property_id=p.id"); while($r=$q->fetch()): ?>
                <tr>
                    <td><img src="<?= $r['photo'] ?: 'logo.png' ?>" class="thumb"></td>
                    <td><b><?= $r['unit_name'] ?></b><br><small><?= $r['pname'] ?></small></td>
                    <td><?= $r['type'] ?></td>
                    <td><?= number_format($r['yearly_price']) ?></td>
                    <td>⚡<?= $r['elec_meter_no'] ?> | 💧<?= $r['water_meter_no'] ?></td>
                    <td><span class="badge" style="background:<?= $r['status']=='rented'?'#ef4444':'#10b981' ?>"><?= $r['status'] ?></span></td>
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
            <thead><tr><th>صورة</th><th>الاسم</th><th>الهوية</th><th>الجوال</th><th>المرفقات</th></tr></thead>
            <tbody>
                <?php $q=$pdo->query("SELECT * FROM tenants"); while($r=$q->fetch()): ?>
                <tr>
                    <td><img src="<?= $r['personal_photo'] ?: 'logo.png' ?>" class="thumb" style="border-radius:50%"></td>
                    <td><?= $r['full_name'] ?></td>
                    <td><?= $r['id_number'] ?> <span style="font-size:10px; color:#94a3b8">(<?= $r['id_type'] ?>)</span></td>
                    <td><?= $r['phone'] ?></td>
                    <td>
                        <?php if($r['id_photo']): ?>
                        <a href="<?= $r['id_photo'] ?>" target="_blank" class="btn btn-sec" style="padding:5px 10px; font-size:12px"><i class="fa-solid fa-id-card"></i> صورة الهوية</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <?php if($p == 'contracts'): ?>
    <button onclick="openM('conM')" class="btn" style="margin-bottom:20px">عقد جديد</button>
    <div class="card">
        <table>
            <thead><tr><th>#</th><th>المستأجر</th><th>الوحدة</th><th>التاريخ</th><th>طباعة</th></tr></thead>
            <tbody>
                <?php $q=$pdo->query("SELECT c.*, t.full_name, u.unit_name FROM contracts c JOIN tenants t ON c.tenant_id=t.id JOIN units u ON c.unit_id=u.id ORDER BY c.id DESC"); 
                while($r=$q->fetch()): ?>
                <tr>
                    <td><?= $r['id'] ?></td>
                    <td><?= $r['full_name'] ?></td>
                    <td><?= $r['unit_name'] ?></td>
                    <td><?= $r['start_date'] ?> -> <?= $r['end_date'] ?></td>
                    <td><a href="invoice_print.php?cid=<?= $r['id'] ?>" target="_blank" class="btn btn-sec"><i class="fa-solid fa-print"></i></a></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <?php if($p == 'users' && $me['role']=='admin'): ?>
    <button onclick="openM('userM')" class="btn" style="margin-bottom:20px">إضافة موظف</button>
    <div class="card">
        <table>
            <thead><tr><th>الاسم</th><th>المستخدم</th><th>الصلاحية</th><th>الجوال</th></tr></thead>
            <tbody>
                <?php $q=$pdo->query("SELECT * FROM users"); while($r=$q->fetch()): ?>
                <tr>
                    <td><?= $r['full_name'] ?></td>
                    <td><?= $r['username'] ?></td>
                    <td><span class="badge" style="background:<?= $r['role']=='admin'?'#8b5cf6':'#64748b' ?>"><?= $r['role'] ?></span></td>
                    <td><?= $r['phone'] ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
    
    <?php if($p == 'settings' && $me['role']=='admin'): ?>
    <div class="inp-grid">
        <form method="POST" enctype="multipart/form-data" class="card">
            <h3>إعدادات عامة</h3>
            <input type="hidden" name="save_settings" value="1">
            <div class="inp-group"><label>اسم الشركة</label><input type="text" name="set[company_name]" value="<?= getSet('company_name') ?>" class="inp"></div>
            <div class="inp-group"><label>الرقم الضريبي</label><input type="text" name="set[vat_no]" value="<?= getSet('vat_no') ?>" class="inp"></div>
            <div class="inp-group"><label>شعار الشركة</label><input type="file" name="logo" class="inp"></div>
            <button class="btn">حفظ</button>
        </form>
        <div class="card">
            <h3>قاعدة البيانات</h3>
            <form method="POST"><button name="backup" class="btn" style="width:100%"><i class="fa-solid fa-download"></i> تحميل نسخة احتياطية</button></form>
        </div>
    </div>
    <?php endif; ?>

</div>

<div id="propM" class="modal"><div class="modal-content">
    <h3>إضافة عقار</h3>
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="add_prop" value="1">
        <div class="inp-grid">
            <div class="inp-group"><label>الاسم</label><input type="text" name="name" class="inp" required></div>
            <div class="inp-group"><label>النوع</label><select name="type" class="inp"><option>عمارة</option><option>مجمع</option><option>أرض</option></select></div>
            <div class="inp-group"><label>العنوان</label><input type="text" name="address" class="inp"></div>
            <div class="inp-group"><label>المدير</label><input type="text" name="manager" class="inp"></div>
            <div class="inp-group"><label>الجوال</label><input type="text" name="phone" class="inp"></div>
            <div class="inp-group"><label>صورة العقار</label><input type="file" name="photo" class="inp"></div>
        </div>
        <button class="btn">حفظ</button> <button type="button" onclick="closeM('propM')" class="btn btn-sec">إلغاء</button>
    </form>
</div></div>

<div id="unitM" class="modal"><div class="modal-content">
    <h3>إضافة وحدة</h3>
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="add_unit" value="1">
        <div class="inp-grid">
            <div class="inp-group"><label>العقار</label><select name="pid" class="inp"><?php $q=$pdo->query("SELECT * FROM properties"); while($r=$q->fetch()) echo "<option value='{$r['id']}'>{$r['name']}</option>"; ?></select></div>
            <div class="inp-group"><label>الاسم</label><input type="text" name="name" class="inp" required></div>
            <div class="inp-group"><label>النوع</label><select name="type" class="inp"><option value="apartment">شقة</option><option value="shop">محل تجاري</option><option value="villa">فيلا</option><option value="office">مكتب</option><option value="warehouse">مستودع</option><option value="land">أرض</option></select></div>
            <div class="inp-group"><label>السعر</label><input type="number" name="price" class="inp"></div>
            <div class="inp-group"><label>كهرباء</label><input type="text" name="elec" class="inp"></div>
            <div class="inp-group"><label>مياه</label><input type="text" name="water" class="inp"></div>
            <div class="full inp-group"><label>صورة الوحدة</label><input type="file" name="photo" class="inp"></div>
            <div class="full inp-group"><label>ملاحظات</label><input type="text" name="notes" class="inp"></div>
        </div>
        <button class="btn">حفظ</button> <button type="button" onclick="closeM('unitM')" class="btn btn-sec">إلغاء</button>
    </form>
</div></div>

<div id="tenM" class="modal"><div class="modal-content">
    <h3>مستأجر جديد</h3>
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="add_tenant" value="1">
        <div class="inp-grid">
            <div class="full inp-group"><label>الاسم</label><input type="text" name="name" class="inp" required></div>
            <div class="inp-group"><label>نوع الهوية</label><select name="id_type" class="inp"><option value="national">هوية وطنية</option><option value="iqama">إقامة</option><option value="commercial">سجل تجاري</option></select></div>
            <div class="inp-group"><label>رقم الهوية</label><input type="text" name="nid" class="inp"></div>
            <div class="inp-group"><label>جوال</label><input type="text" name="phone" class="inp"></div>
            <div class="inp-group"><label>ايميل</label><input type="email" name="email" class="inp"></div>
            <div class="inp-group"><label>صورة الهوية</label><input type="file" name="id_photo" class="inp"></div>
            <div class="inp-group"><label>صورة شخصية</label><input type="file" name="personal_photo" class="inp"></div>
            <div class="full inp-group"><label>العنوان</label><input type="text" name="address" class="inp"></div>
        </div>
        <button class="btn">حفظ</button> <button type="button" onclick="closeM('tenM')" class="btn btn-sec">إلغاء</button>
    </form>
</div></div>

<div id="conM" class="modal"><div class="modal-content">
    <h3>عقد جديد</h3>
    <form method="POST" onsubmit="saveSig()">
        <input type="hidden" name="add_contract" value="1">
        <input type="hidden" name="sig" id="sigField">
        <div class="inp-grid">
            <div class="inp-group"><label>المستأجر</label><select name="tid" class="inp"><?php $q=$pdo->query("SELECT * FROM tenants"); while($r=$q->fetch()) echo "<option value='{$r['id']}'>{$r['full_name']}</option>"; ?></select></div>
            <div class="inp-group"><label>الوحدة</label><select name="uid" class="inp"><?php $q=$pdo->query("SELECT * FROM units WHERE status='available'"); while($r=$q->fetch()) echo "<option value='{$r['id']}'>{$r['unit_name']}</option>"; ?></select></div>
            <div class="inp-group"><label>بداية</label><input type="date" name="start" class="inp"></div>
            <div class="inp-group"><label>نهاية</label><input type="date" name="end" class="inp"></div>
            <div class="inp-group"><label>المبلغ</label><input type="number" name="total" class="inp"></div>
            <div class="inp-group"><label>الدفع</label><select name="cycle" class="inp"><option value="yearly">سنوي</option><option value="monthly">شهري</option></select></div>
        </div>
        <label>توقيع المستلم (Touch)</label>
        <div style="border:2px dashed #64748b; background:white; border-radius:10px; overflow:hidden"><canvas id="sigCanvas" width="600" height="200" style="width:100%"></canvas></div>
        <button type="button" onclick="clearSig()" style="margin-top:5px; background:#ef4444; color:white; border:none; padding:5px 10px; border-radius:5px">مسح التوقيع</button>
        <br><br>
        <button class="btn">إصدار</button> <button type="button" onclick="closeM('conM')" class="btn btn-sec">إلغاء</button>
    </form>
</div></div>

<div id="userM" class="modal"><div class="modal-content">
    <h3>موظف جديد</h3>
    <form method="POST">
        <input type="hidden" name="add_user" value="1">
        <div class="inp-grid">
            <div class="full inp-group"><label>الاسم</label><input type="text" name="name" class="inp"></div>
            <div class="inp-group"><label>مستخدم</label><input type="text" name="user" class="inp"></div>
            <div class="inp-group"><label>باسورد</label><input type="password" name="pass" class="inp"></div>
            <div class="inp-group"><label>جوال</label><input type="text" name="phone" class="inp"></div>
            <div class="inp-group"><label>الصلاحية</label><select name="role" class="inp"><option value="staff">موظف</option><option value="admin">مدير</option></select></div>
        </div>
        <button class="btn">حفظ</button> <button type="button" onclick="closeM('userM')" class="btn btn-sec">إلغاء</button>
    </form>
</div></div>

<script>
    function openM(id){let m=document.getElementById(id);m.style.display='flex';setTimeout(()=>m.classList.add('active'),10);}
    function closeM(id){let m=document.getElementById(id);m.classList.remove('active');setTimeout(()=>m.style.display='none',300);}
    
    // Signature
    const cvs=document.getElementById('sigCanvas'), ctx=cvs.getContext('2d');
    let wrt=false;
    function start(e){wrt=true;ctx.beginPath();let p=pos(e);ctx.moveTo(p.x,p.y);}
    function end(){wrt=false;}
    function move(e){if(!wrt)return;e.preventDefault();let p=pos(e);ctx.lineWidth=3;ctx.lineCap='round';ctx.lineTo(p.x,p.y);ctx.stroke();}
    function pos(e){let r=cvs.getBoundingClientRect(), x=(e.clientX||e.touches[0].clientX)-r.left, y=(e.clientY||e.touches[0].clientY)-r.top;return{x,y};}
    cvs.addEventListener('mousedown',start);cvs.addEventListener('mouseup',end);cvs.addEventListener('mousemove',move);
    cvs.addEventListener('touchstart',start);cvs.addEventListener('touchend',end);cvs.addEventListener('touchmove',move);
    function clearSig(){ctx.clearRect(0,0,cvs.width,cvs.height);}
    function saveSig(){document.getElementById('sigField').value=cvs.toDataURL();}
</script>
</body>
</html>
