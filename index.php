<?php
require 'db.php';
if(!isset($_SESSION['uid'])) { header("Location: login.php"); exit; }

// دالة رفع الصور
function upload($f) {
    if(isset($f) && $f['error']==0) {
        $n=uniqid().'.'.pathinfo($f['name'],PATHINFO_EXTENSION);
        move_uploaded_file($f['tmp_name'], 'uploads/'.$n); return 'uploads/'.$n;
    } return '';
}

// === Backend Logic ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_prop'])) {
        $img = upload($_FILES['photo']);
        $pdo->prepare("INSERT INTO properties (name, type, address, manager_name, manager_phone, photo) VALUES (?,?,?,?,?,?)")
            ->execute([$_POST['name'], $_POST['type'], $_POST['address'], $_POST['manager'], $_POST['phone'], $img]);
        header("Location: ?p=properties"); exit;
    }
    if (isset($_POST['add_unit'])) {
        $img = upload($_FILES['photo']);
        $pdo->prepare("INSERT INTO units (property_id, unit_name, type, yearly_price, elec_meter, water_meter, status, notes, photo) VALUES (?,?,?,?,?,?,?,?,?)")
            ->execute([$_POST['pid'], $_POST['name'], $_POST['type'], $_POST['price'], $_POST['elec'], $_POST['water'], 'available', $_POST['notes'], $img]);
        header("Location: ?p=units"); exit;
    }
    if (isset($_POST['add_contract'])) {
        $pdo->prepare("INSERT INTO contracts (tenant_id, unit_id, start_date, end_date, total_amount, payment_cycle, signature_img) VALUES (?,?,?,?,?,?,?)")
            ->execute([$_POST['tid'], $_POST['uid'], $_POST['start'], $_POST['end'], $_POST['total'], $_POST['cycle'], $_POST['sig']]);
        $pdo->prepare("UPDATE units SET status='rented' WHERE id=?")->execute([$_POST['uid']]);
        header("Location: ?p=contracts"); exit;
    }
    if (isset($_POST['add_tenant'])) {
        $id_img = upload($_FILES['id_photo']); $p_img = upload($_FILES['personal_photo']);
        $pdo->prepare("INSERT INTO tenants (full_name, phone, id_number, id_type, email, address, id_photo, personal_photo) VALUES (?,?,?,?,?,?,?,?)")
            ->execute([$_POST['name'], $_POST['phone'], $_POST['nid'], $_POST['id_type'], $_POST['email'], $_POST['address'], $id_img, $p_img]);
        header("Location: ?p=tenants"); exit;
    }
    if (isset($_POST['add_user'])) {
        $pdo->prepare("INSERT INTO users (full_name, username, password, role, phone) VALUES (?,?,?,?,?)")
            ->execute([$_POST['name'], $_POST['user'], password_hash($_POST['pass'], PASSWORD_DEFAULT), $_POST['role'], $_POST['phone']]);
        header("Location: ?p=users"); exit;
    }
    if (isset($_POST['save_settings'])) {
        foreach($_POST['set'] as $k=>$v) saveSet($k,$v);
        if(!empty($_FILES['logo']['name'])) saveSet('logo', upload($_FILES['logo']));
        header("Location: ?p=settings"); exit;
    }
}

// جلب البيانات مسبقاً للقوائم المنسدلة (لإصلاح مشكلة الاختفاء)
$all_props = $pdo->query("SELECT * FROM properties")->fetchAll();
$all_tenants = $pdo->query("SELECT * FROM tenants")->fetchAll();
$avail_units = $pdo->query("SELECT * FROM units WHERE status='available'")->fetchAll();

$p = $_GET['p'] ?? 'dashboard';
$me = $pdo->query("SELECT * FROM users WHERE id=".$_SESSION['uid'])->fetch();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>دار الميار للمقاولات - النظام الذكي</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root { 
            --bg: #0f172a; --sidebar: #1e293b; --card: #1e293b; 
            --primary: #3b82f6; --accent: #6366f1; --success: #10b981; --danger: #ef4444;
            --text: #f1f5f9; --text-muted: #94a3b8;
        }
        
        body { margin:0; font-family:'Tajawal'; background:var(--bg); color:var(--text); display:flex; height:100vh; overflow:hidden; }
        
        /* Sidebar */
        .sidebar { width:280px; background:var(--sidebar); display:flex; flex-direction:column; padding:20px; border-left:1px solid #334155; }
        .brand { text-align:center; margin-bottom:40px; }
        .brand img { width:90px; height:90px; border-radius:50%; border:3px solid var(--primary); padding:3px; background:white; object-fit:contain; box-shadow:0 0 20px rgba(59,130,246,0.5); }
        .brand h2 { font-size:18px; margin:15px 0 0; background:linear-gradient(to right, #fff, #93c5fd); -webkit-background-clip:text; -webkit-text-fill-color:transparent; }
        
        .nav-item { display:flex; align-items:center; gap:15px; padding:16px; color:var(--text-muted); text-decoration:none; border-radius:12px; margin-bottom:8px; transition:0.3s; font-size:16px; font-weight:500; }
        .nav-item:hover, .nav-item.active { background:linear-gradient(90deg, var(--primary), var(--accent)); color:white; transform:translateX(-5px); box-shadow:0 5px 15px rgba(59,130,246,0.3); }
        
        /* Main */
        .main { flex:1; padding:40px; overflow-y:auto; background:radial-gradient(circle at top right, #172554, transparent 40%); }
        .header { display:flex; justify-content:space-between; align-items:center; margin-bottom:30px; }
        .search-bar { background:#334155; padding:10px 20px; border-radius:30px; border:1px solid #475569; width:300px; color:white; display:flex; align-items:center; gap:10px; }
        .search-bar input { background:transparent; border:none; color:white; outline:none; font-family:inherit; width:100%; }
        
        /* Cards */
        .grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(260px, 1fr)); gap:25px; margin-bottom:30px; }
        .card { background:var(--card); padding:25px; border-radius:20px; border:1px solid #334155; box-shadow:0 10px 30px rgba(0,0,0,0.2); transition:0.3s; }
        .card:hover { transform:translateY(-5px); border-color:var(--primary); }
        
        /* Visual Unit Map (Innovation) */
        .unit-map { display:grid; grid-template-columns:repeat(auto-fill, minmax(120px, 1fr)); gap:15px; }
        .unit-box { background:#334155; padding:15px; border-radius:12px; text-align:center; border:2px solid transparent; cursor:pointer; position:relative; overflow:hidden; }
        .unit-box.free { border-color:var(--success); background:rgba(16,185,129,0.1); }
        .unit-box.busy { border-color:var(--danger); background:rgba(239,68,68,0.1); }
        .unit-box h4 { margin:0; font-size:18px; }
        .unit-box span { font-size:12px; opacity:0.7; }
        .status-dot { width:10px; height:10px; border-radius:50%; position:absolute; top:10px; left:10px; }
        
        /* Buttons */
        .btn { padding:14px 28px; border-radius:12px; border:none; background:linear-gradient(135deg, var(--primary), var(--accent)); color:white; cursor:pointer; font-weight:bold; font-family:inherit; font-size:15px; display:inline-flex; align-items:center; gap:10px; text-decoration:none; transition:0.3s; box-shadow:0 5px 15px rgba(59,130,246,0.3); }
        .btn:hover { transform:translateY(-2px); filter:brightness(1.1); }
        .btn-outline { background:transparent; border:2px solid #475569; box-shadow:none; }
        
        /* Tables */
        table { width:100%; border-collapse:collapse; }
        th { text-align:right; padding:18px; color:var(--text-muted); border-bottom:2px solid #334155; }
        td { padding:18px; border-bottom:1px solid #334155; vertical-align:middle; font-size:15px; }
        
        /* === IMPROVED MODALS & INPUTS === */
        .modal { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); backdrop-filter:blur(8px); z-index:9999; justify-content:center; align-items:center; }
        .modal-content { background:#1e293b; width:700px; padding:40px; border-radius:24px; border:1px solid #475569; box-shadow:0 25px 50px rgba(0,0,0,0.5); max-height:95vh; overflow-y:auto; position:relative; }
        
        /* Big Inputs Style */
        .form-grid { display:grid; grid-template-columns:1fr 1fr; gap:25px; }
        .form-group { margin-bottom:5px; }
        .form-group label { display:block; margin-bottom:10px; color:#cbd5e1; font-weight:500; font-size:15px; }
        .inp { width:100%; padding:16px; background:#0f172a; border:2px solid #334155; border-radius:14px; color:white; font-size:16px; font-family:'Tajawal'; outline:none; transition:0.3s; box-sizing:border-box; height:56px; }
        .inp:focus { border-color:var(--primary); box-shadow:0 0 0 4px rgba(59,130,246,0.1); background:#1e293b; }
        select.inp { appearance:none; background-image:url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e"); background-repeat:no-repeat; background-position:left 15px center; background-size:16px; cursor:pointer; }
        .full { grid-column:span 2; }
        
        .modal-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:30px; padding-bottom:20px; border-bottom:1px solid #334155; }
        .modal-header h3 { margin:0; font-size:24px; color:var(--primary); }
        .close-btn { background:none; border:none; color:#ef4444; font-size:24px; cursor:pointer; }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="brand">
        <img src="<?= getSet('logo') ?>" onerror="this.src='logo.png'">
        <h2>دار الميار للمقاولات</h2>
        <p style="font-size:12px; color:#64748b; margin-top:5px">Enterprise Edition</p>
    </div>
    <a href="?p=dashboard" class="nav-item <?= $p=='dashboard'?'active':'' ?>"><i class="fa-solid fa-chart-pie"></i> لوحة القيادة</a>
    <a href="?p=properties" class="nav-item <?= $p=='properties'?'active':'' ?>"><i class="fa-solid fa-city"></i> إدارة العقارات</a>
    <a href="?p=units" class="nav-item <?= $p=='units'?'active':'' ?>"><i class="fa-solid fa-door-open"></i> الوحدات السكنية</a>
    <a href="?p=contracts" class="nav-item <?= $p=='contracts'?'active':'' ?>"><i class="fa-solid fa-file-contract"></i> العقود والإيجارات</a>
    <a href="?p=tenants" class="nav-item <?= $p=='tenants'?'active':'' ?>"><i class="fa-solid fa-users"></i> المستأجرين</a>
    <?php if($me['role']=='admin'): ?>
    <a href="?p=users" class="nav-item <?= $p=='users'?'active':'' ?>"><i class="fa-solid fa-user-shield"></i> الموظفين</a>
    <a href="?p=settings" class="nav-item <?= $p=='settings'?'active':'' ?>"><i class="fa-solid fa-gear"></i> الإعدادات</a>
    <?php endif; ?>
    <a href="logout.php" class="nav-item" style="color:#ef4444; margin-top:auto"><i class="fa-solid fa-right-from-bracket"></i> تسجيل الخروج</a>
</div>

<div class="main">
    <div class="header">
        <div>
            <h1 style="margin:0; font-size:28px"><?= $p=='dashboard' ? 'نظرة عامة' : ($p=='contracts'?'إدارة العقود':$p) ?></h1>
            <p style="color:#94a3b8; margin:5px 0 0">مرحباً <?= $me['full_name'] ?>، نتمنى لك يوماً سعيداً 🌟</p>
        </div>
        <div class="search-bar">
            <i class="fa-solid fa-search"></i>
            <input type="text" placeholder="بحث ذكي في النظام..." onkeyup="filterTable(this.value)">
        </div>
    </div>

    <?php if($p == 'dashboard'): ?>
        <div class="grid">
            <div class="card" style="border-right:5px solid var(--success)">
                <div style="font-size:14px; color:#94a3b8">الإيرادات المحصلة</div>
                <div style="font-size:32px; font-weight:bold; margin-top:10px"><?= number_format($pdo->query("SELECT SUM(total_amount) FROM contracts")->fetchColumn()) ?> <small>SAR</small></div>
            </div>
            <div class="card" style="border-right:5px solid var(--primary)">
                <div style="font-size:14px; color:#94a3b8">عدد الوحدات</div>
                <div style="font-size:32px; font-weight:bold; margin-top:10px"><?= count($avail_units) + $pdo->query("SELECT count(*) FROM units WHERE status='rented'")->fetchColumn() ?></div>
            </div>
            <div class="card" style="border-right:5px solid var(--accent)">
                <div style="font-size:14px; color:#94a3b8">عقود سارية</div>
                <div style="font-size:32px; font-weight:bold; margin-top:10px"><?= $pdo->query("SELECT count(*) FROM contracts WHERE status='active'")->fetchColumn() ?></div>
            </div>
        </div>

        <div class="card">
            <h3 style="margin-top:0"><i class="fa-solid fa-map"></i> خريطة الوحدات الحية</h3>
            <div class="unit-map">
                <?php $q=$pdo->query("SELECT * FROM units"); while($r=$q->fetch()): ?>
                <div class="unit-box <?= $r['status']=='available'?'free':'busy' ?>">
                    <div class="status-dot" style="background:<?= $r['status']=='available'?'#10b981':'#ef4444' ?>"></div>
                    <h4><?= $r['unit_name'] ?></h4>
                    <span><?= $r['type'] ?></span>
                    <div style="margin-top:5px; font-weight:bold"><?= number_format($r['yearly_price']) ?></div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if($p == 'properties'): ?>
        <button onclick="openM('propModal')" class="btn" style="margin-bottom:25px"><i class="fa-solid fa-plus"></i> إضافة عقار جديد</button>
        <div class="card">
            <table>
                <thead><tr><th>الصورة</th><th>اسم العقار</th><th>النوع</th><th>المسؤول</th><th>الوحدات</th></tr></thead>
                <tbody id="dataTable">
                    <?php $q=$pdo->query("SELECT p.*, (SELECT count(*) FROM units WHERE property_id=p.id) as cnt FROM properties p"); while($r=$q->fetch()): ?>
                    <tr>
                        <td><img src="<?= $r['photo']?:'logo.png' ?>" style="width:50px; height:50px; border-radius:10px; object-fit:cover"></td>
                        <td><b><?= $r['name'] ?></b><br><small><?= $r['address'] ?></small></td>
                        <td><?= $r['type'] ?></td>
                        <td><?= $r['manager_name'] ?></td>
                        <td><span style="background:#334155; padding:5px 12px; border-radius:10px"><?= $r['cnt'] ?> وحدة</span></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <?php if($p == 'units'): ?>
        <button onclick="openM('unitModal')" class="btn" style="margin-bottom:25px"><i class="fa-solid fa-plus"></i> إضافة وحدة</button>
        <div class="card">
            <table>
                <thead><tr><th>اسم الوحدة</th><th>العقار</th><th>النوع</th><th>السعر</th><th>الحالة</th></tr></thead>
                <tbody id="dataTable">
                    <?php $q=$pdo->query("SELECT u.*, p.name as pname FROM units u JOIN properties p ON u.property_id=p.id"); while($r=$q->fetch()): ?>
                    <tr>
                        <td><b><?= $r['unit_name'] ?></b></td>
                        <td><?= $r['pname'] ?></td>
                        <td><?= $r['type'] ?></td>
                        <td><?= number_format($r['yearly_price']) ?></td>
                        <td><span style="padding:6px 15px; border-radius:20px; background:<?= $r['status']=='available'?'rgba(16,185,129,0.2)':'rgba(239,68,68,0.2)' ?>; color:<?= $r['status']=='available'?'#34d399':'#f87171' ?>"><?= $r['status']=='available'?'متاح للتأجير':'مؤجر' ?></span></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <?php if($p == 'contracts'): ?>
        <button onclick="openM('contractModal')" class="btn" style="margin-bottom:25px"><i class="fa-solid fa-file-signature"></i> إنشاء عقد جديد</button>
        <div class="card">
            <table>
                <thead><tr><th>رقم العقد</th><th>المستأجر</th><th>الوحدة</th><th>التاريخ</th><th>طباعة</th></tr></thead>
                <tbody id="dataTable">
                    <?php $q=$pdo->query("SELECT c.*, t.full_name, u.unit_name FROM contracts c JOIN tenants t ON c.tenant_id=t.id JOIN units u ON c.unit_id=u.id ORDER BY c.id DESC"); 
                    while($r=$q->fetch()): ?>
                    <tr>
                        <td>#<?= $r['id'] ?></td>
                        <td><?= $r['full_name'] ?></td>
                        <td><?= $r['unit_name'] ?></td>
                        <td><?= $r['start_date'] ?></td>
                        <td><a href="invoice_print.php?cid=<?= $r['id'] ?>" target="_blank" class="btn btn-outline" style="padding:8px 15px"><i class="fa-solid fa-print"></i></a></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <?php if($p == 'tenants'): ?>
        <button onclick="openM('tenantModal')" class="btn" style="margin-bottom:25px"><i class="fa-solid fa-user-plus"></i> مستأجر جديد</button>
        <div class="card">
            <table>
                <thead><tr><th>الصورة</th><th>الاسم</th><th>الهوية</th><th>الجوال</th></tr></thead>
                <tbody id="dataTable">
                    <?php $q=$pdo->query("SELECT * FROM tenants"); while($r=$q->fetch()): ?>
                    <tr>
                        <td><img src="<?= $r['personal_photo']?:'logo.png' ?>" style="width:40px; height:40px; border-radius:50%"></td>
                        <td><?= $r['full_name'] ?></td>
                        <td><?= $r['id_number'] ?></td>
                        <td><?= $r['phone'] ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

     <?php if($p == 'users' && $me['role']=='admin'): ?>
        <button onclick="openM('userModal')" class="btn" style="margin-bottom:25px">موظف جديد</button>
        <div class="card">
            <table><thead><tr><th>الاسم</th><th>المستخدم</th><th>الدور</th></tr></thead><tbody><?php $q=$pdo->query("SELECT * FROM users"); while($r=$q->fetch()): ?><tr><td><?= $r['full_name'] ?></td><td><?= $r['username'] ?></td><td><?= $r['role'] ?></td></tr><?php endwhile; ?></tbody></table>
        </div>
    <?php endif; ?>

    <?php if($p == 'settings'): ?>
        <div class="card" style="max-width:600px">
            <h3 style="margin-top:0">إعدادات النظام</h3>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="save_settings" value="1">
                <div class="form-group"><label>اسم الشركة</label><input type="text" name="set[company_name]" value="<?= getSet('company_name') ?>" class="inp"></div>
                <div class="form-group"><label>الرقم الضريبي</label><input type="text" name="set[vat_no]" value="<?= getSet('vat_no') ?>" class="inp"></div>
                <div class="form-group"><label>تغيير الشعار</label><input type="file" name="logo" class="inp" style="padding:12px"></div>
                <button class="btn" style="width:100%; margin-top:20px; justify-content:center">حفظ التغييرات</button>
            </form>
        </div>
    <?php endif; ?>

</div>

<div id="propModal" class="modal"><div class="modal-content">
    <div class="modal-header"><h3>🏢 إضافة عقار جديد</h3><button onclick="closeM('propModal')" class="close-btn">&times;</button></div>
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="add_prop" value="1">
        <div class="form-grid">
            <div class="form-group"><label>اسم العقار</label><input type="text" name="name" class="inp" placeholder="مثال: برج اليمامة" required></div>
            <div class="form-group"><label>نوع العقار</label><select name="type" class="inp"><option>عمارة سكنية</option><option>مجمع تجاري</option><option>أرض</option></select></div>
            <div class="form-group full"><label>العنوان التفصيلي</label><input type="text" name="address" class="inp"></div>
            <div class="form-group"><label>اسم المدير</label><input type="text" name="manager" class="inp"></div>
            <div class="form-group"><label>جوال المدير</label><input type="text" name="phone" class="inp"></div>
            <div class="form-group full"><label>صورة العقار</label><input type="file" name="photo" class="inp" style="padding:12px"></div>
        </div>
        <div style="margin-top:30px; display:flex; gap:15px">
            <button class="btn" style="flex:1; justify-content:center">حفظ البيانات</button>
        </div>
    </form>
</div></div>

<div id="unitModal" class="modal"><div class="modal-content">
    <div class="modal-header"><h3>🏠 إضافة وحدة جديدة</h3><button onclick="closeM('unitModal')" class="close-btn">&times;</button></div>
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="add_unit" value="1">
        <div class="form-grid">
            <div class="form-group full">
                <label>اختر العقار التابع له</label>
                <select name="pid" class="inp">
                    <?php foreach($all_props as $pr): ?>
                        <option value="<?= $pr['id'] ?>"><?= $pr['name'] ?> - <?= $pr['type'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group"><label>اسم الوحدة</label><input type="text" name="name" class="inp" placeholder="شقة 10 / محل 5"></div>
            <div class="form-group"><label>النوع</label><select name="type" class="inp"><option value="apartment">شقة</option><option value="shop">محل</option><option value="villa">فيلا</option><option value="office">مكتب</option></select></div>
            <div class="form-group"><label>السعر السنوي</label><input type="number" name="price" class="inp"></div>
            <div class="form-group"><label>ملاحظات</label><input type="text" name="notes" class="inp"></div>
            <div class="form-group"><label>عداد كهرباء</label><input type="text" name="elec" class="inp"></div>
            <div class="form-group"><label>عداد مياه</label><input type="text" name="water" class="inp"></div>
            <div class="form-group full"><label>صورة الوحدة</label><input type="file" name="photo" class="inp" style="padding:12px"></div>
        </div>
        <button class="btn" style="width:100%; margin-top:30px; justify-content:center">حفظ الوحدة</button>
    </form>
</div></div>

<div id="contractModal" class="modal"><div class="modal-content">
    <div class="modal-header"><h3>📝 صياغة عقد جديد</h3><button onclick="closeM('contractModal')" class="close-btn">&times;</button></div>
    <form method="POST" onsubmit="saveSig()">
        <input type="hidden" name="add_contract" value="1">
        <input type="hidden" name="sig" id="sigField">
        <div class="form-grid">
            <div class="form-group full">
                <label>المستأجر</label>
                <select name="tid" class="inp">
                    <?php foreach($all_tenants as $tn): ?>
                        <option value="<?= $tn['id'] ?>"><?= $tn['full_name'] ?> (<?= $tn['id_number'] ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group full">
                <label>الوحدة (المتاحة فقط)</label>
                <select name="uid" class="inp">
                    <?php foreach($avail_units as $un): ?>
                        <option value="<?= $un['id'] ?>"><?= $un['unit_name'] ?> (<?= number_format($un['yearly_price']) ?> SAR)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group"><label>تاريخ البدء</label><input type="date" name="start" class="inp"></div>
            <div class="form-group"><label>تاريخ الانتهاء</label><input type="date" name="end" class="inp"></div>
            <div class="form-group"><label>القيمة الإجمالية</label><input type="number" name="total" class="inp"></div>
            <div class="form-group"><label>نظام الدفع</label><select name="cycle" class="inp"><option value="yearly">دفعة واحدة</option><option value="semiannual">دفعتين</option><option value="quarterly">ربع سنوي</option></select></div>
        </div>
        
        <label style="display:block; margin:20px 0 10px; color:#cbd5e1">توقيع المستأجر (لوحة اللمس)</label>
        <div style="background:white; border-radius:15px; overflow:hidden; border:2px dashed #475569">
            <canvas id="sigCanvas" width="620" height="200" style="width:100%"></canvas>
        </div>
        <button type="button" onclick="clearSig()" style="margin-top:10px; background:#ef4444; color:white; border:none; padding:8px 15px; border-radius:8px">مسح التوقيع</button>
        
        <button class="btn" style="width:100%; margin-top:30px; justify-content:center">اعتماد العقد</button>
    </form>
</div></div>

<div id="tenantModal" class="modal"><div class="modal-content">
    <div class="modal-header"><h3>👤 بيانات مستأجر جديد</h3><button onclick="closeM('tenantModal')" class="close-btn">&times;</button></div>
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="add_tenant" value="1">
        <div class="form-grid">
            <div class="form-group full"><label>الاسم الرباعي</label><input type="text" name="name" class="inp" required></div>
            <div class="form-group"><label>نوع الهوية</label><select name="id_type" class="inp"><option value="national">هوية وطنية</option><option value="iqama">إقامة</option><option value="passport">جواز سفر</option></select></div>
            <div class="form-group"><label>رقم الهوية</label><input type="text" name="nid" class="inp"></div>
            <div class="form-group"><label>رقم الجوال</label><input type="text" name="phone" class="inp"></div>
            <div class="form-group"><label>البريد الإلكتروني</label><input type="email" name="email" class="inp"></div>
            <div class="form-group"><label>صورة الهوية</label><input type="file" name="id_photo" class="inp" style="padding:12px"></div>
            <div class="form-group"><label>صورة شخصية</label><input type="file" name="personal_photo" class="inp" style="padding:12px"></div>
            <div class="form-group full"><label>العنوان الوطني</label><input type="text" name="address" class="inp"></div>
        </div>
        <button class="btn" style="width:100%; margin-top:30px; justify-content:center">حفظ الملف</button>
    </form>
</div></div>

<div id="userModal" class="modal"><div class="modal-content">
    <div class="modal-header"><h3>موظف جديد</h3><button onclick="closeM('userModal')" class="close-btn">&times;</button></div>
    <form method="POST"><input type="hidden" name="add_user" value="1"><div class="form-grid"><div class="form-group"><label>الاسم</label><input type="text" name="name" class="inp"></div><div class="form-group"><label>اسم المستخدم</label><input type="text" name="user" class="inp"></div><div class="form-group"><label>كلمة المرور</label><input type="password" name="pass" class="inp"></div><div class="form-group"><label>الجوال</label><input type="text" name="phone" class="inp"></div><div class="form-group"><label>الدور</label><select name="role" class="inp"><option value="staff">موظف</option><option value="admin">مدير</option></select></div></div><button class="btn" style="width:100%; margin-top:20px">حفظ</button></form>
</div></div>

<script>
    // Modal Logic
    function openM(id){ document.getElementById(id).style.display='flex'; }
    function closeM(id){ document.getElementById(id).style.display='none'; }
    window.onclick = function(e){ if(e.target.classList.contains('modal')) e.target.style.display='none'; }

    // Live Search Logic
    function filterTable(val) {
        let filter = val.toUpperCase();
        let rows = document.getElementById("dataTable").getElementsByTagName("tr");
        for (let i = 0; i < rows.length; i++) {
            let txt = rows[i].textContent || rows[i].innerText;
            if (txt.toUpperCase().indexOf(filter) > -1) rows[i].style.display = ""; else rows[i].style.display = "none";
        }
    }

    // Signature Pad
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
