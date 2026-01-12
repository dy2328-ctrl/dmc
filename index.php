<?php
require 'db.php';
if(!isset($_SESSION['uid'])) { header("Location: login.php"); exit; }

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
    
    // إضافة عقار
    if (isset($_POST['add_prop'])) {
        $photo = uploadFile($_FILES['photo']);
        $pdo->prepare("INSERT INTO properties (name, type, address, manager_name, manager_phone, photo) VALUES (?,?,?,?,?,?)")
            ->execute([$_POST['name'], $_POST['type'], $_POST['address'], $_POST['manager'], $_POST['phone'], $photo]);
        header("Location: ?p=properties"); exit;
    }

    // إضافة وحدة (مع المميزات)
    if (isset($_POST['add_unit'])) {
        $photo = uploadFile($_FILES['photo']);
        $amenities = isset($_POST['amenities']) ? implode(',', $_POST['amenities']) : '';
        $pdo->prepare("INSERT INTO units (property_id, unit_name, type, yearly_price, elec_meter_no, water_meter_no, amenities, status, notes, photo) VALUES (?,?,?,?,?,?,?,?,?,?)")
            ->execute([$_POST['pid'], $_POST['name'], $_POST['type'], $_POST['price'], $_POST['elec'], $_POST['water'], $amenities, 'available', $_POST['notes'], $photo]);
        header("Location: ?p=units"); exit;
    }

    // إضافة عقد
    if (isset($_POST['add_contract'])) {
        $pdo->prepare("INSERT INTO contracts (tenant_id, unit_id, start_date, end_date, total_amount, payment_cycle, signature_img) VALUES (?,?,?,?,?,?,?)")
            ->execute([$_POST['tid'], $_POST['uid'], $_POST['start'], $_POST['end'], $_POST['total'], $_POST['cycle'], $_POST['sig']]);
        $pdo->prepare("UPDATE units SET status='rented' WHERE id=?")->execute([$_POST['uid']]);
        header("Location: ?p=contracts"); exit;
    }

    // إضافة مستأجر
    if (isset($_POST['add_tenant'])) {
        $id_p = uploadFile($_FILES['id_photo']);
        $per_p = uploadFile($_FILES['personal_photo']);
        $pdo->prepare("INSERT INTO tenants (full_name, phone, id_number, id_type, email, address, id_photo, personal_photo) VALUES (?,?,?,?,?,?,?,?)")
            ->execute([$_POST['name'], $_POST['phone'], $_POST['nid'], $_POST['id_type'], $_POST['email'], $_POST['address'], $id_p, $per_p]);
        header("Location: ?p=tenants"); exit;
    }

    // موظفين وإعدادات
    if (isset($_POST['add_user'])) {
        $pdo->prepare("INSERT INTO users (full_name, username, password, role, phone) VALUES (?,?,?,?,?)")->execute([$_POST['name'], $_POST['user'], password_hash($_POST['pass'], PASSWORD_DEFAULT), $_POST['role'], $_POST['phone']]); header("Location: ?p=users"); exit;
    }
    if (isset($_POST['save_settings'])) {
        foreach($_POST['set'] as $k=>$v) saveSet($k,$v);
        if(!empty($_FILES['logo']['name'])) saveSet('logo', uploadFile($_FILES['logo']));
        header("Location: ?p=settings"); exit;
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
        :root { --bg:#0f172a; --sidebar:#1e293b; --card:#1e293b; --primary:#6366f1; --accent:#8b5cf6; --text:#f1f5f9; --input-bg:#334155; }
        body { margin:0; font-family:'Tajawal'; background:var(--bg); color:var(--text); display:flex; height:100vh; overflow:hidden; }
        
        /* SIDEBAR */
        .sidebar { width:280px; background:var(--sidebar); display:flex; flex-direction:column; padding:25px; border-left:1px solid #334155; z-index:10; box-shadow:5px 0 30px rgba(0,0,0,0.3); }
        .logo-box { width:110px; height:110px; background:white; border-radius:50%; margin:0 auto 20px; display:flex; align-items:center; justify-content:center; border:4px solid var(--primary); box-shadow:0 0 25px rgba(99,102,241,0.5); }
        .nav-link { display:flex; align-items:center; gap:15px; padding:16px; border-radius:15px; color:#94a3b8; text-decoration:none; margin-bottom:10px; font-weight:600; font-size:16px; transition:0.3s; }
        .nav-link:hover, .nav-link.active { background:linear-gradient(90deg,var(--primary),var(--accent)); color:white; transform:translateX(-5px); box-shadow:0 10px 20px rgba(99,102,241,0.2); }
        
        /* MAIN */
        .main { flex:1; padding:40px; overflow-y:auto; background-image:radial-gradient(at 0% 0%, #1e1b4b 0%, transparent 50%); }
        .header { display:flex; justify-content:space-between; align-items:center; margin-bottom:40px; }
        .btn { padding:14px 30px; border-radius:12px; border:none; background:linear-gradient(135deg,var(--primary),var(--accent)); color:white; font-weight:bold; cursor:pointer; font-size:16px; display:inline-flex; align-items:center; gap:10px; text-decoration:none; transition:0.3s; box-shadow:0 4px 15px rgba(99,102,241,0.3); }
        .btn:hover { transform:translateY(-3px); box-shadow:0 10px 25px rgba(99,102,241,0.5); }
        .btn-sec { background:#475569; box-shadow:none; }
        
        /* CARDS & TABLES */
        .card { background:var(--card); border-radius:24px; padding:30px; margin-bottom:30px; border:1px solid #334155; box-shadow:0 20px 40px rgba(0,0,0,0.2); }
        table { width:100%; border-collapse:separate; border-spacing:0 10px; }
        th { text-align:right; padding:15px; color:#94a3b8; font-size:14px; }
        td { background:#1e293b; padding:20px; border-top:1px solid #334155; border-bottom:1px solid #334155; font-size:16px; font-weight:500; }
        td:first-child { border-radius:0 15px 15px 0; border-right:1px solid #334155; }
        td:last-child { border-radius:15px 0 0 15px; border-left:1px solid #334155; }
        .thumb { width:50px; height:50px; border-radius:12px; object-fit:cover; border:2px solid #475569; }
        
        /* MODALS (تصميم جديد ومحسن) */
        .modal { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.85); backdrop-filter:blur(10px); z-index:2000; justify-content:center; align-items:center; opacity:0; transition:0.4s; }
        .modal.active { opacity:1; }
        .modal-content { background:#1e293b; width:800px; padding:50px; border-radius:30px; border:1px solid #475569; max-height:90vh; overflow-y:auto; transform:scale(0.95); transition:0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); box-shadow:0 50px 100px rgba(0,0,0,0.8); }
        .modal.active .modal-content { transform:scale(1); }
        
        /* INPUTS (كبيرة وواضحة) */
        .inp-grid { display:grid; grid-template-columns:1fr 1fr; gap:25px; margin-bottom:25px; }
        .inp-group label { display:block; color:#cbd5e1; margin-bottom:10px; font-size:16px; font-weight:600; }
        .inp { width:100%; padding:18px; background:#0f172a; border:2px solid #334155; border-radius:15px; color:white; font-size:16px; outline:none; transition:0.3s; box-sizing:border-box; font-family:'Tajawal'; }
        .inp:focus { border-color:var(--primary); background:#1e293b; box-shadow:0 0 0 4px rgba(99,102,241,0.2); }
        .full { grid-column:span 2; }
        
        /* Checkboxes for Amenities */
        .amenities-grid { display:grid; grid-template-columns:repeat(4, 1fr); gap:15px; }
        .chk-label { display:flex; align-items:center; gap:8px; cursor:pointer; background:#0f172a; padding:15px; border-radius:12px; border:1px solid #334155; transition:0.3s; }
        .chk-label:hover { border-color:var(--primary); }
        input[type="checkbox"] { width:20px; height:20px; accent-color:var(--primary); }

        /* Empty State */
        .empty-state { text-align:center; padding:40px; color:#94a3b8; }
        .empty-state i { font-size:50px; margin-bottom:20px; opacity:0.3; }
    </style>
</head>
<body>

<div class="sidebar">
    <div style="text-align:center; margin-bottom:40px">
        <div class="logo-box"><img src="<?= getSet('logo') ?: 'logo.png' ?>" style="max-width:80%; max-height:80%"></div>
        <h3 style="margin:0; font-size:20px">دار الميار للمقاولات</h3>
    </div>
    <a href="?p=dashboard" class="nav-link <?= $p=='dashboard'?'active':'' ?>"><i class="fa-solid fa-chart-pie"></i> لوحة التحكم</a>
    <a href="?p=properties" class="nav-link <?= $p=='properties'?'active':'' ?>"><i class="fa-solid fa-city"></i> العقارات</a>
    <a href="?p=units" class="nav-link <?= $p=='units'?'active':'' ?>"><i class="fa-solid fa-door-open"></i> الوحدات</a>
    <a href="?p=tenants" class="nav-link <?= $p=='tenants'?'active':'' ?>"><i class="fa-solid fa-users"></i> المستأجرين</a>
    <a href="?p=contracts" class="nav-link <?= $p=='contracts'?'active':'' ?>"><i class="fa-solid fa-file-signature"></i> العقود</a>
    <?php if($me['role']=='admin'): ?>
    <a href="?p=users" class="nav-link <?= $p=='users'?'active':'' ?>"><i class="fa-solid fa-user-shield"></i> الموظفين</a>
    <a href="?p=settings" class="nav-link <?= $p=='settings'?'active':'' ?>"><i class="fa-solid fa-gear"></i> الإعدادات</a>
    <?php endif; ?>
    <a href="logout.php" class="nav-link" style="color:#ef4444; margin-top:auto"><i class="fa-solid fa-power-off"></i> خروج</a>
</div>

<div class="main">
    <div class="header">
        <div>
            <h1 style="margin:0; font-size:32px"><?= ucfirst($p) ?></h1>
            <p style="margin:5px 0 0 0; color:#94a3b8">أهلاً بك في نظام الإدارة الذكي</p>
        </div>
        <div style="display:flex; gap:15px">
            <button class="btn btn-sec"><i class="fa-solid fa-bell"></i></button>
            <div style="background:#334155; padding:10px 20px; border-radius:50px; display:flex; align-items:center; gap:10px; font-weight:bold">
                <i class="fa-solid fa-user-circle fa-lg"></i> <?= $me['full_name'] ?>
            </div>
        </div>
    </div>

    <?php if($p == 'dashboard'): ?>
    <div class="inp-grid" style="grid-template-columns:repeat(4, 1fr)">
        <div class="card" style="margin:0; background:linear-gradient(135deg, #6366f1, #4f46e5); border:none">
            <h3 style="margin:0; color:#e0e7ff">الإيرادات</h3>
            <div style="font-size:36px; font-weight:800; margin-top:10px"><?= number_format($pdo->query("SELECT SUM(total_amount) FROM contracts")->fetchColumn()) ?></div>
        </div>
        <div class="card" style="margin:0; background:linear-gradient(135deg, #10b981, #059669); border:none">
            <h3 style="margin:0; color:#d1fae5">الوحدات</h3>
            <div style="font-size:36px; font-weight:800; margin-top:10px"><?= $pdo->query("SELECT count(*) FROM units")->fetchColumn() ?></div>
        </div>
        <div class="card" style="margin:0; background:linear-gradient(135deg, #f59e0b, #d97706); border:none">
            <h3 style="margin:0; color:#fef3c7">المستأجرين</h3>
            <div style="font-size:36px; font-weight:800; margin-top:10px"><?= $pdo->query("SELECT count(*) FROM tenants")->fetchColumn() ?></div>
        </div>
        <div class="card" style="margin:0; background:linear-gradient(135deg, #ec4899, #db2777); border:none">
            <h3 style="margin:0; color:#fce7f3">عقود نشطة</h3>
            <div style="font-size:36px; font-weight:800; margin-top:10px"><?= $pdo->query("SELECT count(*) FROM contracts WHERE status='active'")->fetchColumn() ?></div>
        </div>
    </div>
    
    <div class="card" style="margin-top:30px">
        <h3><i class="fa-solid fa-clock-rotate-left"></i> آخر العمليات</h3>
        <table>
            <thead><tr><th>نوع العملية</th><th>المستأجر</th><th>التفاصيل</th><th>التاريخ</th></tr></thead>
            <tbody>
                <?php $q=$pdo->query("SELECT c.*, t.full_name, u.unit_name FROM contracts c JOIN tenants t ON c.tenant_id=t.id JOIN units u ON c.unit_id=u.id ORDER BY c.id DESC LIMIT 5"); while($r=$q->fetch()): ?>
                <tr>
                    <td><span style="background:#312e81; color:#a5b4fc; padding:5px 15px; border-radius:20px; font-size:14px">عقد جديد</span></td>
                    <td><b><?= $r['full_name'] ?></b></td>
                    <td><?= $r['unit_name'] ?> (<?= number_format($r['total_amount']) ?> ريال)</td>
                    <td><?= $r['created_at'] ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <?php if($p == 'properties'): ?>
    <?php if($me['role']=='admin'): ?><button onclick="openM('propM')" class="btn" style="margin-bottom:30px"><i class="fa-solid fa-plus"></i> إضافة عقار جديد</button><?php endif; ?>
    <div class="card">
        <?php $q=$pdo->query("SELECT * FROM properties"); if($q->rowCount()>0): ?>
        <table>
            <thead><tr><th>صورة</th><th>الاسم</th><th>النوع</th><th>الإدارة</th><th>الوحدات</th></tr></thead>
            <tbody>
                <?php while($r=$q->fetch()): $uc=$pdo->query("SELECT count(*) FROM units WHERE property_id={$r['id']}")->fetchColumn(); ?>
                <tr>
                    <td><img src="<?= $r['photo']?:'logo.png' ?>" class="thumb"></td>
                    <td><b><?= $r['name'] ?></b><br><small style="color:#94a3b8"><?= $r['address'] ?></small></td>
                    <td><?= $r['type'] ?></td>
                    <td><?= $r['manager_name'] ?><br><small><?= $r['manager_phone'] ?></small></td>
                    <td><span style="background:#334155; padding:5px 12px; border-radius:10px"><?= $uc ?> وحدة</span></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="empty-state"><i class="fa-solid fa-building"></i><p>لا توجد عقارات مضافة حتى الآن.</p></div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if($p == 'units'): ?>
    <?php if($me['role']=='admin'): ?><button onclick="openM('unitM')" class="btn" style="margin-bottom:30px"><i class="fa-solid fa-plus"></i> إضافة وحدة جديدة</button><?php endif; ?>
    <div class="card">
        <?php $q=$pdo->query("SELECT u.*, p.name as pname FROM units u JOIN properties p ON u.property_id=p.id"); if($q->rowCount()>0): ?>
        <table>
            <thead><tr><th>الوحدة</th><th>التفاصيل</th><th>المميزات</th><th>السعر</th><th>الحالة</th></tr></thead>
            <tbody>
                <?php while($r=$q->fetch()): ?>
                <tr>
                    <td>
                        <div style="display:flex; align-items:center; gap:15px">
                            <img src="<?= $r['photo']?:'logo.png' ?>" class="thumb">
                            <div><b><?= $r['unit_name'] ?></b><br><small style="color:#94a3b8"><?= $r['pname'] ?></small></div>
                        </div>
                    </td>
                    <td><?= $r['type'] ?><br><small>⚡<?= $r['elec_meter_no'] ?></small></td>
                    <td><small style="color:#cbd5e1"><?= $r['amenities'] ?></small></td>
                    <td><?= number_format($r['yearly_price']) ?></td>
                    <td><span style="background:<?= $r['status']=='rented'?'rgba(239,68,68,0.2)':'rgba(16,185,129,0.2)' ?>; color:<?= $r['status']=='rented'?'#f87171':'#34d399' ?>; padding:6px 12px; border-radius:10px"><?= $r['status'] ?></span></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="empty-state"><i class="fa-solid fa-door-open"></i><p>لا توجد وحدات. ابدأ بإضافة وحدة جديدة.</p></div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if($p == 'contracts'): ?>
    <button onclick="openM('conM')" class="btn" style="margin-bottom:30px"><i class="fa-solid fa-pen-nib"></i> إنشاء عقد جديد</button>
    <div class="card">
        <?php $q=$pdo->query("SELECT c.*, t.full_name, u.unit_name FROM contracts c JOIN tenants t ON c.tenant_id=t.id JOIN units u ON c.unit_id=u.id ORDER BY c.id DESC"); if($q->rowCount()>0): ?>
        <table>
            <thead><tr><th>رقم العقد</th><th>المستأجر</th><th>الوحدة</th><th>التاريخ</th><th>الحالة</th><th>إجراء</th></tr></thead>
            <tbody>
                <?php while($r=$q->fetch()): ?>
                <tr>
                    <td>#<?= $r['id'] ?></td>
                    <td><b><?= $r['full_name'] ?></b></td>
                    <td><?= $r['unit_name'] ?></td>
                    <td><?= $r['start_date'] ?> <i class="fa-solid fa-arrow-left" style="font-size:12px; color:#64748b"></i> <?= $r['end_date'] ?></td>
                    <td><span style="background:rgba(16,185,129,0.2); color:#34d399; padding:5px 12px; border-radius:10px">ساري</span></td>
                    <td><a href="invoice_print.php?cid=<?= $r['id'] ?>" target="_blank" class="btn btn-sec"><i class="fa-solid fa-print"></i></a></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="empty-state"><i class="fa-solid fa-file-contract"></i><p>لا توجد عقود مسجلة.</p></div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if($p == 'tenants'): ?>
    <button onclick="openM('tenM')" class="btn" style="margin-bottom:30px"><i class="fa-solid fa-user-plus"></i> مستأجر جديد</button>
    <div class="card">
        <?php $q=$pdo->query("SELECT * FROM tenants"); if($q->rowCount()>0): ?>
        <table>
            <thead><tr><th>الاسم</th><th>الهوية</th><th>الجوال</th><th>الوثائق</th></tr></thead>
            <tbody>
                <?php while($r=$q->fetch()): ?>
                <tr>
                    <td><div style="display:flex; align-items:center; gap:10px"><img src="<?= $r['personal_photo']?:'logo.png' ?>" class="thumb" style="border-radius:50%"> <b><?= $r['full_name'] ?></b></div></td>
                    <td><?= $r['id_number'] ?></td>
                    <td><?= $r['phone'] ?></td>
                    <td><?php if($r['id_photo']): ?><a href="<?= $r['id_photo'] ?>" target="_blank" style="color:#6366f1; text-decoration:none">عرض الهوية</a><?php endif; ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="empty-state"><i class="fa-solid fa-users"></i><p>قائمة المستأجرين فارغة.</p></div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <?php if($p == 'users' && $me['role']=='admin'): ?>
    <button onclick="openM('userM')" class="btn" style="margin-bottom:30px">موظف جديد</button>
    <div class="card">
        <table>
            <thead><tr><th>الاسم</th><th>اسم المستخدم</th><th>الدور</th><th>الجوال</th></tr></thead>
            <tbody>
                <?php $q=$pdo->query("SELECT * FROM users"); while($r=$q->fetch()): ?>
                <tr><td><?= $r['full_name'] ?></td><td><?= $r['username'] ?></td><td><?= $r['role'] ?></td><td><?= $r['phone'] ?></td></tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <?php if($p == 'settings' && $me['role']=='admin'): ?>
    <div class="inp-grid">
        <form method="POST" enctype="multipart/form-data" class="card">
            <h3>⚙️ إعدادات النظام</h3>
            <input type="hidden" name="save_settings" value="1">
            <div class="inp-group"><label>اسم الشركة</label><input type="text" name="set[company_name]" value="<?= getSet('company_name') ?>" class="inp"></div>
            <div class="inp-group"><label>الرقم الضريبي</label><input type="text" name="set[vat_no]" value="<?= getSet('vat_no') ?>" class="inp"></div>
            <div class="inp-group"><label>تحديث الشعار</label><input type="file" name="logo" class="inp"></div>
            <button class="btn">حفظ الإعدادات</button>
        </form>
    </div>
    <?php endif; ?>

</div>

<div id="propM" class="modal"><div class="modal-content">
    <h2 style="margin-top:0; font-size:28px; margin-bottom:30px">🏢 إضافة عقار جديد</h2>
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="add_prop" value="1">
        <div class="inp-grid">
            <div class="full inp-group"><label>اسم العقار</label><input type="text" name="name" class="inp" placeholder="مثال: أبراج العليا" required></div>
            <div class="inp-group"><label>نوع العقار</label><select name="type" class="inp"><option>عمارة سكنية</option><option>مجمع تجاري</option><option>أرض خام</option><option>مستودعات</option></select></div>
            <div class="inp-group"><label>العنوان</label><input type="text" name="address" class="inp"></div>
            <div class="inp-group"><label>اسم المدير</label><input type="text" name="manager" class="inp"></div>
            <div class="inp-group"><label>رقم المدير</label><input type="text" name="phone" class="inp"></div>
            <div class="full inp-group"><label>صورة العقار</label><input type="file" name="photo" class="inp"></div>
        </div>
        <div style="display:flex; gap:20px; margin-top:30px">
            <button class="btn" style="flex:1; justify-content:center; padding:20px">حفظ العقار</button>
            <button type="button" onclick="closeM('propM')" class="btn btn-sec" style="padding:20px">إلغاء</button>
        </div>
    </form>
</div></div>

<div id="unitM" class="modal"><div class="modal-content">
    <h2 style="margin-top:0; font-size:28px; margin-bottom:30px">🏠 إضافة وحدة جديدة</h2>
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="add_unit" value="1">
        
        <?php $props = $pdo->query("SELECT * FROM properties"); if($props->rowCount() > 0): ?>
            <div class="inp-grid">
                <div class="full inp-group">
                    <label>اختر العقار</label>
                    <select name="pid" class="inp" style="height:60px; font-size:18px">
                        <?php while($pr=$props->fetch()) echo "<option value='{$pr['id']}'>{$pr['name']}</option>"; ?>
                    </select>
                </div>
                <div class="inp-group"><label>اسم الوحدة</label><input type="text" name="name" class="inp" placeholder="شقة 101" required></div>
                <div class="inp-group">
                    <label>النوع</label>
                    <select name="type" class="inp"><option value="apartment">شقة</option><option value="shop">محل تجاري</option><option value="villa">فيلا</option><option value="office">مكتب</option></select>
                </div>
                <div class="inp-group"><label>السعر السنوي</label><input type="number" name="price" class="inp"></div>
                <div class="inp-group"><label>عداد كهرباء</label><input type="text" name="elec" class="inp"></div>
                <div class="inp-group"><label>عداد ماء</label><input type="text" name="water" class="inp"></div>
                <div class="full inp-group"><label>صورة الوحدة</label><input type="file" name="photo" class="inp"></div>
                
                <div class="full inp-group">
                    <label style="margin-bottom:15px">المميزات</label>
                    <div class="amenities-grid">
                        <label class="chk-label"><input type="checkbox" name="amenities[]" value="مكيف"> مكيف</label>
                        <label class="chk-label"><input type="checkbox" name="amenities[]" value="مطبخ"> مطبخ</label>
                        <label class="chk-label"><input type="checkbox" name="amenities[]" value="موقف"> موقف</label>
                        <label class="chk-label"><input type="checkbox" name="amenities[]" value="مسبح"> مسبح</label>
                    </div>
                </div>
            </div>
            <div style="display:flex; gap:20px; margin-top:30px">
                <button class="btn" style="flex:1; justify-content:center; padding:20px">حفظ الوحدة</button>
                <button type="button" onclick="closeM('unitM')" class="btn btn-sec" style="padding:20px">إلغاء</button>
            </div>
        <?php else: ?>
            <div style="text-align:center; padding:50px">
                <i class="fa-solid fa-triangle-exclamation" style="font-size:50px; color:#f59e0b; margin-bottom:20px"></i>
                <h3>لا توجد عقارات مضافة!</h3>
                <p>يجب عليك إضافة عقار (مبنى) أولاً قبل إضافة الوحدات.</p>
                <button type="button" onclick="closeM('unitM'); openM('propM')" class="btn">إضافة عقار الآن</button>
            </div>
        <?php endif; ?>
    </form>
</div></div>

<div id="tenM" class="modal"><div class="modal-content">
    <h2 style="margin-top:0; font-size:28px; margin-bottom:30px">👤 مستأجر جديد</h2>
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="add_tenant" value="1">
        <div class="inp-grid">
            <div class="full inp-group"><label>الاسم الكامل</label><input type="text" name="name" class="inp" required></div>
            <div class="inp-group"><label>نوع الهوية</label><select name="id_type" class="inp"><option value="national">هوية وطنية</option><option value="iqama">إقامة</option><option value="commercial">سجل تجاري</option></select></div>
            <div class="inp-group"><label>رقم الهوية</label><input type="text" name="nid" class="inp"></div>
            <div class="inp-group"><label>رقم الجوال</label><input type="text" name="phone" class="inp"></div>
            <div class="inp-group"><label>البريد الإلكتروني</label><input type="email" name="email" class="inp"></div>
            <div class="inp-group"><label>صورة الهوية</label><input type="file" name="id_photo" class="inp"></div>
            <div class="inp-group"><label>صورة شخصية</label><input type="file" name="personal_photo" class="inp"></div>
        </div>
        <button class="btn" style="width:100%; padding:20px; margin-top:20px; justify-content:center">حفظ البيانات</button>
        <button type="button" onclick="closeM('tenM')" class="btn btn-sec" style="width:100%; margin-top:10px; padding:15px; justify-content:center">إلغاء</button>
    </form>
</div></div>

<div id="conM" class="modal"><div class="modal-content">
    <h2 style="margin-top:0; font-size:28px; margin-bottom:30px">📝 عقد جديد</h2>
    <form method="POST" onsubmit="saveSig()">
        <input type="hidden" name="add_contract" value="1">
        <input type="hidden" name="sig" id="sigField">
        
        <?php 
        $tenants = $pdo->query("SELECT * FROM tenants"); 
        $units = $pdo->query("SELECT * FROM units WHERE status='available'");
        if($tenants->rowCount() > 0 && $units->rowCount() > 0): 
        ?>
            <div class="inp-grid">
                <div class="inp-group"><label>المستأجر</label><select name="tid" class="inp"><?php while($t=$tenants->fetch()) echo "<option value='{$t['id']}'>{$t['full_name']}</option>"; ?></select></div>
                <div class="inp-group"><label>الوحدة المتاحة</label><select name="uid" class="inp"><?php while($u=$units->fetch()) echo "<option value='{$u['id']}'>{$u['unit_name']} ({$u['type']})</option>"; ?></select></div>
                <div class="inp-group"><label>تاريخ البدء</label><input type="date" name="start" class="inp"></div>
                <div class="inp-group"><label>تاريخ الانتهاء</label><input type="date" name="end" class="inp"></div>
                <div class="inp-group"><label>إجمالي العقد</label><input type="number" name="total" class="inp"></div>
                <div class="inp-group"><label>نظام الدفع</label><select name="cycle" class="inp"><option value="yearly">سنوي</option><option value="monthly">شهري</option></select></div>
            </div>
            
            <label style="color:#cbd5e1; display:block; margin-bottom:10px; font-weight:bold">توقيع المستأجر (Touch Pad)</label>
            <div style="background:white; border-radius:15px; overflow:hidden; border:2px dashed #64748b; height:200px">
                <canvas id="sigCanvas" width="700" height="200" style="width:100%; height:100%; touch-action:none;"></canvas>
            </div>
            <button type="button" onclick="clearSig()" style="background:#ef4444; color:white; border:none; padding:8px 15px; border-radius:8px; margin-top:10px; cursor:pointer">مسح التوقيع</button>
            
            <button class="btn" style="width:100%; padding:20px; margin-top:30px; justify-content:center">إصدار العقد</button>
            <button type="button" onclick="closeM('conM')" class="btn btn-sec" style="width:100%; margin-top:10px; padding:15px; justify-content:center">إلغاء</button>
        <?php else: ?>
            <div style="text-align:center; padding:50px; color:#ef4444">
                <i class="fa-solid fa-ban" style="font-size:50px; margin-bottom:20px"></i>
                <h3>لا يمكن إنشاء عقد</h3>
                <p>تأكد من وجود مستأجرين ووحدات متاحة في النظام.</p>
                <button type="button" onclick="closeM('conM')" class="btn btn-sec">إغلاق</button>
            </div>
        <?php endif; ?>
    </form>
</div></div>

<div id="userM" class="modal"><div class="modal-content">
    <h2>موظف جديد</h2>
    <form method="POST">
        <input type="hidden" name="add_user" value="1">
        <div class="inp-grid">
            <div class="full inp-group"><label>الاسم</label><input type="text" name="name" class="inp"></div>
            <div class="inp-group"><label>مستخدم</label><input type="text" name="user" class="inp"></div>
            <div class="inp-group"><label>باسورد</label><input type="password" name="pass" class="inp"></div>
            <div class="inp-group"><label>جوال</label><input type="text" name="phone" class="inp"></div>
            <div class="inp-group"><label>الصلاحية</label><select name="role" class="inp"><option value="staff">موظف</option><option value="admin">مدير</option></select></div>
        </div>
        <button class="btn" style="width:100%">حفظ</button>
        <button type="button" onclick="closeM('userM')" class="btn btn-sec" style="width:100%; margin-top:10px">إلغاء</button>
    </form>
</div></div>

<script>
    // Modal System Logic
    function openM(id) {
        let modal = document.getElementById(id);
        if(modal) {
            modal.style.display = 'flex';
            setTimeout(() => modal.classList.add('active'), 10);
        } else {
            console.error("Modal not found: " + id);
        }
    }
    
    function closeM(id) {
        let modal = document.getElementById(id);
        if(modal) {
            modal.classList.remove('active');
            setTimeout(() => modal.style.display = 'none', 400);
        }
    }

    // Signature Logic
    const cvs = document.getElementById('sigCanvas');
    if(cvs) {
        const ctx = cvs.getContext('2d');
        let wrt = false;
        
        // Resize canvas correctly
        function resizeCanvas() {
            cvs.width = cvs.offsetWidth;
            cvs.height = cvs.offsetHeight;
        }
        window.addEventListener('resize', resizeCanvas);
        // Call resize initially when modal opens (needs delay or observer)
        
        function start(e) { wrt=true; ctx.beginPath(); let p=pos(e); ctx.moveTo(p.x, p.y); }
        function end() { wrt=false; }
        function move(e) { 
            if(!wrt) return; 
            e.preventDefault(); 
            let p=pos(e); 
            ctx.lineWidth=3; ctx.lineCap='round'; ctx.lineTo(p.x, p.y); ctx.stroke(); 
        }
        function pos(e) {
            let r = cvs.getBoundingClientRect();
            let x = (e.clientX || e.touches[0].clientX) - r.left;
            let y = (e.clientY || e.touches[0].clientY) - r.top;
            return {x, y};
        }
        
        cvs.addEventListener('mousedown', start); cvs.addEventListener('mouseup', end); cvs.addEventListener('mousemove', move);
        cvs.addEventListener('touchstart', start); cvs.addEventListener('touchend', end); cvs.addEventListener('touchmove', move);
    }
    
    function clearSig() { 
        const cvs = document.getElementById('sigCanvas');
        const ctx = cvs.getContext('2d');
        ctx.clearRect(0, 0, cvs.width, cvs.height); 
    }
    function saveSig() { 
        const cvs = document.getElementById('sigCanvas');
        document.getElementById('sigField').value = cvs.toDataURL(); 
    }
</script>
</body>
</html>
