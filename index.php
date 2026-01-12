<?php
require 'db.php';
if(!isset($_SESSION['uid'])) { header("Location: login.php"); exit; }

// --- BACKEND LOGIC ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // إضافة عقار
    if (isset($_POST['add_prop'])) {
        $pdo->prepare("INSERT INTO properties (name, type, address, manager_name, manager_phone) VALUES (?,?,?,?,?)")
            ->execute([$_POST['name'], $_POST['type'], $_POST['address'], $_POST['manager'], $_POST['phone']]);
        header("Location: ?p=properties"); exit;
    }
    // إضافة وحدة
    if (isset($_POST['add_unit'])) {
        $pdo->prepare("INSERT INTO units (property_id, unit_name, type, yearly_price, elec_meter_no, water_meter_no) VALUES (?,?,?,?,?,?)")
            ->execute([$_POST['pid'], $_POST['name'], $_POST['type'], $_POST['price'], $_POST['elec'], $_POST['water']]);
        header("Location: ?p=units"); exit;
    }
    // إضافة عقد (الإصلاح)
    if (isset($_POST['add_contract'])) {
        $pdo->prepare("INSERT INTO contracts (tenant_id, unit_id, start_date, end_date, total_amount, payment_cycle, signature_img) VALUES (?,?,?,?,?,?,?)")
            ->execute([$_POST['tid'], $_POST['uid'], $_POST['start'], $_POST['end'], $_POST['total'], $_POST['cycle'], $_POST['sig']]);
        $pdo->prepare("UPDATE units SET status='rented' WHERE id=?")->execute([$_POST['uid']]);
        header("Location: ?p=contracts"); exit;
    }
    // حفظ الإعدادات
    if (isset($_POST['save_settings'])) {
        foreach($_POST['set'] as $k=>$v) saveSet($k,$v);
        header("Location: ?p=settings"); exit;
    }
    // إضافة موظف
    if(isset($_POST['add_user'])){
        $pdo->prepare("INSERT INTO users (full_name, username, password, role) VALUES (?,?,?,?)")->execute([$_POST['name'],$_POST['user'],password_hash($_POST['pass'],PASSWORD_DEFAULT),$_POST['role']]);
        header("Location: ?p=users"); exit;
    }
    // إضافة مستأجر
    if(isset($_POST['add_tenant'])){
        $pdo->prepare("INSERT INTO tenants (full_name, phone, id_number) VALUES (?,?,?)")->execute([$_POST['name'],$_POST['phone'],$_POST['nid']]);
        header("Location: ?p=tenants"); exit;
    }
}

$p = $_GET['p'] ?? 'dashboard';
$me = $pdo->query("SELECT * FROM users WHERE id=".$_SESSION['uid'])->fetch();
$company = getSet('company_name') ?: 'دار الميار للمقاولات';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $company ?> - النظام الذكي</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* === تصميم احترافي عالي الجودة === */
        :root {
            --bg: #0f172a; --card: #1e293b; --border: #334155; 
            --primary: #6366f1; --success: #10b981; --text: #f8fafc; --text-muted: #94a3b8;
        }
        body { font-family: 'Tajawal'; background: var(--bg); color: var(--text); margin: 0; display: flex; height: 100vh; overflow: hidden; }
        
        /* Sidebar */
        .sidebar { width: 280px; background: var(--card); border-left: 1px solid var(--border); display: flex; flex-direction: column; padding: 25px; z-index: 10; box-shadow: 5px 0 30px rgba(0,0,0,0.3); }
        .logo-area { text-align: center; margin-bottom: 40px; padding-bottom: 20px; border-bottom: 1px solid var(--border); }
        .logo-img { width: 90px; height: 90px; background: white; border-radius: 50%; padding: 5px; margin-bottom: 15px; box-shadow: 0 0 20px rgba(99,102,241,0.5); }
        .nav-link { display: flex; align-items: center; gap: 15px; padding: 16px; margin-bottom: 8px; border-radius: 12px; color: var(--text-muted); text-decoration: none; font-weight: 500; transition: 0.3s; font-size: 16px; }
        .nav-link:hover, .nav-link.active { background: var(--primary); color: white; transform: translateX(-5px); box-shadow: 0 5px 15px rgba(99,102,241,0.3); }
        
        /* Main Content */
        .main { flex: 1; padding: 40px; overflow-y: auto; background-image: radial-gradient(circle at top left, #1e1b4b, transparent 40%); }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px; }
        .page-title { font-size: 32px; font-weight: 800; color: white; }
        
        /* Cards */
        .card { background: var(--card); border: 1px solid var(--border); border-radius: 20px; padding: 30px; margin-bottom: 25px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
        
        /* Tables */
        table { width: 100%; border-collapse: collapse; }
        th { text-align: right; padding: 20px; color: var(--text-muted); border-bottom: 1px solid var(--border); font-size: 14px; }
        td { padding: 20px; border-bottom: 1px solid var(--border); font-size: 16px; font-weight: 500; }
        
        /* Buttons */
        .btn { padding: 14px 28px; background: var(--primary); color: white; border: none; border-radius: 12px; cursor: pointer; font-family: inherit; font-weight: bold; font-size: 16px; display: inline-flex; align-items: center; gap: 10px; text-decoration: none; transition: 0.3s; }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(99,102,241,0.4); }
        .btn-outline { background: transparent; border: 2px solid var(--border); color: var(--text-muted); }

        /* === Smart Dashboard Features === */
        .stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 25px; margin-bottom: 30px; }
        .stat-box { background: var(--card); border: 1px solid var(--border); border-radius: 16px; padding: 25px; position: relative; overflow: hidden; }
        .stat-val { font-size: 36px; font-weight: 800; margin-top: 10px; }
        .progress-bar { background: #334155; height: 8px; border-radius: 4px; margin-top: 15px; overflow: hidden; }
        .progress-fill { height: 100%; background: var(--success); border-radius: 4px; }

        /* === MODALS (FIXED & BEAUTIFIED) === */
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); backdrop-filter: blur(5px); z-index: 1000; justify-content: center; align-items: center; }
        .modal.active { display: flex; animation: fadeIn 0.3s; }
        .modal-content { background: #1e293b; width: 800px; padding: 50px; border-radius: 24px; border: 1px solid #475569; box-shadow: 0 25px 50px rgba(0,0,0,0.5); max-height: 90vh; overflow-y: auto; }
        
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 25px; }
        .form-group { margin-bottom: 5px; }
        .form-group label { display: block; margin-bottom: 10px; color: #cbd5e1; font-size: 15px; font-weight: bold; }
        .inp { width: 100%; padding: 16px; background: #0f172a; border: 2px solid #334155; border-radius: 12px; color: white; font-size: 16px; outline: none; transition: 0.3s; box-sizing: border-box; font-family: inherit; }
        .inp:focus { border-color: var(--primary); background: #020617; }
        .full { grid-column: span 2; }
        
        @keyframes fadeIn { from { opacity: 0; transform: scale(0.95); } to { opacity: 1; transform: scale(1); } }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="logo-area">
        <img src="logo.png" class="logo-img" onerror="this.src='https://via.placeholder.com/90'">
        <h3 style="margin:0; font-size:18px"><?= $company ?></h3>
    </div>
    <a href="?p=dashboard" class="nav-link <?= $p=='dashboard'?'active':'' ?>"><i class="fa-solid fa-chart-pie"></i> لوحة القيادة</a>
    <a href="?p=properties" class="nav-link <?= $p=='properties'?'active':'' ?>"><i class="fa-solid fa-city"></i> العقارات</a>
    <a href="?p=units" class="nav-link <?= $p=='units'?'active':'' ?>"><i class="fa-solid fa-door-open"></i> الوحدات</a>
    <a href="?p=contracts" class="nav-link <?= $p=='contracts'?'active':'' ?>"><i class="fa-solid fa-file-contract"></i> العقود</a>
    <a href="?p=tenants" class="nav-link <?= $p=='tenants'?'active':'' ?>"><i class="fa-solid fa-users"></i> المستأجرين</a>
    <?php if($me['role']=='admin'): ?>
    <a href="?p=users" class="nav-link <?= $p=='users'?'active':'' ?>"><i class="fa-solid fa-user-shield"></i> الموظفين</a>
    <a href="?p=settings" class="nav-link <?= $p=='settings'?'active':'' ?>"><i class="fa-solid fa-gear"></i> الإعدادات</a>
    <?php endif; ?>
    <a href="logout.php" class="nav-link" style="color:#ef4444; margin-top:auto"><i class="fa-solid fa-right-from-bracket"></i> تسجيل الخروج</a>
</div>

<div class="main">
    <div class="header">
        <div class="page-title">
            <?php 
            $titles = ['dashboard'=>'لوحة القيادة الذكية', 'properties'=>'إدارة العقارات', 'contracts'=>'العقود', 'units'=>'الوحدات', 'settings'=>'إعدادات الشركة'];
            echo $titles[$p] ?? 'النظام';
            ?>
        </div>
        <div style="color:var(--text-muted)">مرحباً، <b><?= $me['full_name'] ?></b></div>
    </div>

    <?php if($p == 'dashboard'): 
        $total_units = $pdo->query("SELECT count(*) FROM units")->fetchColumn() ?: 1;
        $rented_units = $pdo->query("SELECT count(*) FROM units WHERE status='rented'")->fetchColumn();
        $occ_rate = round(($rented_units / $total_units) * 100);
        $income = $pdo->query("SELECT SUM(total_amount) FROM contracts")->fetchColumn();
    ?>
    <div class="stat-grid">
        <div class="stat-box" style="border-right: 5px solid var(--success)">
            <div style="color:var(--text-muted); font-size:14px">معدل الإشغال</div>
            <div class="stat-val"><?= $occ_rate ?>%</div>
            <div class="progress-bar"><div class="progress-fill" style="width:<?= $occ_rate ?>%"></div></div>
            <small style="color:#94a3b8; margin-top:10px; display:block">مشغول: <?= $rented_units ?> من أصل <?= $total_units ?></small>
        </div>
        <div class="stat-box" style="border-right: 5px solid var(--primary)">
            <div style="color:var(--text-muted); font-size:14px">إجمالي الإيرادات</div>
            <div class="stat-val"><?= number_format($income) ?> <span style="font-size:16px">ر.س</span></div>
            <div style="margin-top:15px; color:#94a3b8"><i class="fa-solid fa-arrow-trend-up"></i> أداء مالي ممتاز</div>
        </div>
    </div>
    <div class="card">
        <h3><i class="fa-solid fa-bolt" style="color:#f59e0b"></i> نشاطات حديثة</h3>
        <table>
            <thead><tr><th>النشاط</th><th>التفاصيل</th><th>الوقت</th></tr></thead>
            <tbody>
                <?php $q=$pdo->query("SELECT * FROM contracts ORDER BY id DESC LIMIT 5"); while($r=$q->fetch()): ?>
                <tr>
                    <td><span style="background:rgba(99,102,241,0.2); color:#818cf8; padding:5px 10px; border-radius:8px">عقد جديد</span></td>
                    <td>تم توقيع العقد رقم #<?= $r['id'] ?></td>
                    <td><?= $r['created_at'] ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <?php if($p == 'properties'): ?>
    <button onclick="openM('propM')" class="btn" style="margin-bottom:30px"><i class="fa-solid fa-plus"></i> إضافة عقار جديد</button>
    <div class="card">
        <table>
            <thead><tr><th>اسم العقار</th><th>النوع</th><th>العنوان</th><th>مدير العقار</th></tr></thead>
            <tbody>
                <?php $q=$pdo->query("SELECT * FROM properties"); while($r=$q->fetch()): ?>
                <tr><td><b><?= $r['name'] ?></b></td><td><?= $r['type'] ?></td><td><?= $r['address'] ?></td><td><?= $r['manager_name'] ?></td></tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <?php if($p == 'units'): ?>
    <button onclick="openM('unitM')" class="btn" style="margin-bottom:30px"><i class="fa-solid fa-plus"></i> إضافة وحدة</button>
    <div class="card">
        <table>
            <thead><tr><th>الوحدة</th><th>المبنى</th><th>النوع</th><th>السعر السنوي</th><th>العدادات</th><th>الحالة</th></tr></thead>
            <tbody>
                <?php $q=$pdo->query("SELECT u.*, p.name as pname FROM units u LEFT JOIN properties p ON u.property_id=p.id"); while($r=$q->fetch()): ?>
                <tr>
                    <td><b><?= $r['unit_name'] ?></b></td>
                    <td><?= $r['pname'] ?></td>
                    <td><?= $r['type'] ?></td>
                    <td><?= number_format($r['yearly_price']) ?></td>
                    <td>⚡<?= $r['elec_meter_no'] ?> | 💧<?= $r['water_meter_no'] ?></td>
                    <td><span style="padding:5px 10px; border-radius:10px; background:<?= $r['status']=='rented'?'#7f1d1d':'#064e3b' ?>; color:white"><?= $r['status']=='rented'?'مؤجر':'شاغر' ?></span></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <?php if($p == 'contracts'): ?>
    <button onclick="openM('conM')" class="btn" style="margin-bottom:30px"><i class="fa-solid fa-file-signature"></i> إنشاء عقد جديد</button>
    <div class="card">
        <table>
            <thead><tr><th>رقم العقد</th><th>المستأجر</th><th>الوحدة</th><th>القيمة</th><th>طباعة</th></tr></thead>
            <tbody>
                <?php $q=$pdo->query("SELECT c.*, t.full_name, u.unit_name FROM contracts c JOIN tenants t ON c.tenant_id=t.id JOIN units u ON c.unit_id=u.id ORDER BY c.id DESC"); 
                while($r=$q->fetch()): ?>
                <tr>
                    <td>#<?= $r['id'] ?></td>
                    <td><?= $r['full_name'] ?></td>
                    <td><?= $r['unit_name'] ?></td>
                    <td><?= number_format($r['total_amount']) ?></td>
                    <td><a href="invoice_print.php?cid=<?= $r['id'] ?>" target="_blank" class="btn btn-outline" style="padding:5px 15px"><i class="fa-solid fa-print"></i></a></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
    
    <?php if($p == 'settings'): ?>
    <form method="POST" class="card" style="max-width:600px">
        <h3>إعدادات الشركة</h3>
        <input type="hidden" name="save_settings" value="1">
        <label style="display:block; margin-bottom:10px">اسم الشركة الرسمي</label>
        <input type="text" name="set[company_name]" value="<?= $company ?>" class="inp" style="margin-bottom:20px">
        <label style="display:block; margin-bottom:10px">الرقم الضريبي</label>
        <input type="text" name="set[vat_no]" value="<?= getSet('vat_no') ?>" class="inp" style="margin-bottom:20px">
        <button class="btn">حفظ التغييرات</button>
    </form>
    <?php endif; ?>
    
    <?php if($p == 'tenants'): ?>
    <button onclick="openM('tenM')" class="btn" style="margin-bottom:30px">إضافة مستأجر</button>
    <div class="card">
        <table>
            <thead><tr><th>الاسم</th><th>الجوال</th><th>رقم الهوية</th></tr></thead>
            <tbody>
                <?php $q=$pdo->query("SELECT * FROM tenants"); while($r=$q->fetch()): ?>
                <tr><td><?= $r['full_name'] ?></td><td><?= $r['phone'] ?></td><td><?= $r['id_number'] ?></td></tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <?php if($p == 'users'): ?>
    <button onclick="openM('userM')" class="btn" style="margin-bottom:30px">موظف جديد</button>
    <div class="card">
        <table><thead><tr><th>الاسم</th><th>المستخدم</th><th>الدور</th></tr></thead><tbody><?php $q=$pdo->query("SELECT * FROM users"); while($r=$q->fetch()): ?><tr><td><?= $r['full_name'] ?></td><td><?= $r['username'] ?></td><td><?= $r['role'] ?></td></tr><?php endwhile; ?></tbody></table>
    </div>
    <?php endif; ?>

</div>

<div id="propM" class="modal"><div class="modal-content">
    <h2 style="margin-top:0; margin-bottom:30px">إضافة عقار جديد</h2>
    <form method="POST">
        <input type="hidden" name="add_prop" value="1">
        <div class="form-grid">
            <div class="full form-group"><label>اسم العقار</label><input type="text" name="name" class="inp" placeholder="مثال: عمارة الياسمين"></div>
            <div class="form-group"><label>نوع العقار</label><select name="type" class="inp"><option>عمارة سكنية</option><option>مجمع تجاري</option><option>أرض</option></select></div>
            <div class="form-group"><label>العنوان</label><input type="text" name="address" class="inp"></div>
            <div class="form-group"><label>اسم المدير</label><input type="text" name="manager" class="inp"></div>
            <div class="form-group"><label>جوال المدير</label><input type="text" name="phone" class="inp"></div>
        </div>
        <div style="margin-top:30px; display:flex; gap:15px">
            <button class="btn" style="flex:1; justify-content:center">حفظ العقار</button>
            <button type="button" onclick="closeM('propM')" class="btn btn-outline">إلغاء</button>
        </div>
    </form>
</div></div>

<div id="unitM" class="modal"><div class="modal-content">
    <h2 style="margin-top:0; margin-bottom:30px">إضافة وحدة</h2>
    <form method="POST">
        <input type="hidden" name="add_unit" value="1">
        <div class="form-grid">
            <div class="full form-group">
                <label>اختر العقار التابع له</label>
                <select name="pid" class="inp">
                    <?php 
                    $props = $pdo->query("SELECT * FROM properties")->fetchAll();
                    if(count($props) == 0) echo "<option value=''>لا يوجد عقارات! أضف عقاراً أولاً</option>";
                    foreach($props as $pr) echo "<option value='{$pr['id']}'>{$pr['name']}</option>"; 
                    ?>
                </select>
            </div>
            <div class="form-group"><label>اسم الوحدة</label><input type="text" name="name" class="inp" placeholder="شقة 1 / معرض 5"></div>
            <div class="form-group"><label>النوع</label><select name="type" class="inp"><option>شقة</option><option>فيلا</option><option>محل تجاري</option><option>مستودع</option></select></div>
            <div class="form-group"><label>السعر السنوي</label><input type="number" name="price" class="inp"></div>
            <div class="form-group"><label>كهرباء</label><input type="text" name="elec" class="inp"></div>
            <div class="form-group"><label>مياه</label><input type="text" name="water" class="inp"></div>
        </div>
        <div style="margin-top:30px; display:flex; gap:15px">
            <button class="btn" style="flex:1; justify-content:center">حفظ الوحدة</button>
            <button type="button" onclick="closeM('unitM')" class="btn btn-outline">إلغاء</button>
        </div>
    </form>
</div></div>

<div id="conM" class="modal"><div class="modal-content">
    <h2 style="margin-top:0; margin-bottom:30px">توثيق عقد جديد</h2>
    <form method="POST" onsubmit="saveSig()">
        <input type="hidden" name="add_contract" value="1">
        <input type="hidden" name="sig" id="sigField">
        <div class="form-grid">
            <div class="form-group"><label>المستأجر</label><select name="tid" class="inp"><?php $ts=$pdo->query("SELECT * FROM tenants"); foreach($ts as $t) echo "<option value='{$t['id']}'>{$t['full_name']}</option>"; ?></select></div>
            <div class="form-group"><label>الوحدة المتاحة</label><select name="uid" class="inp"><?php $us=$pdo->query("SELECT * FROM units WHERE status='available'"); foreach($us as $u) echo "<option value='{$u['id']}'>{$u['unit_name']}</option>"; ?></select></div>
            <div class="form-group"><label>تاريخ البدء</label><input type="date" name="start" class="inp"></div>
            <div class="form-group"><label>تاريخ الانتهاء</label><input type="date" name="end" class="inp"></div>
            <div class="form-group"><label>إجمالي العقد</label><input type="number" name="total" class="inp"></div>
            <div class="form-group"><label>نظام الدفع</label><select name="cycle" class="inp"><option value="yearly">دفعة سنوية</option><option value="monthly">شهري</option></select></div>
        </div>
        <div style="margin-top:20px">
            <label style="display:block; margin-bottom:10px">توقيع المستأجر (Touch Pad)</label>
            <div style="background:white; border-radius:12px; height:150px; overflow:hidden; border:2px dashed #94a3b8">
                <canvas id="sigCanvas" style="width:100%; height:100%"></canvas>
            </div>
            <button type="button" onclick="clearSig()" style="margin-top:5px; color:#ef4444; background:none; border:none; cursor:pointer">مسح التوقيع</button>
        </div>
        <div style="margin-top:30px; display:flex; gap:15px">
            <button class="btn" style="flex:1; justify-content:center">إصدار العقد</button>
            <button type="button" onclick="closeM('conM')" class="btn btn-outline">إلغاء</button>
        </div>
    </form>
</div></div>

<div id="tenM" class="modal"><div class="modal-content">
    <h2>بيانات المستأجر</h2>
    <form method="POST">
        <input type="hidden" name="add_tenant" value="1">
        <div class="form-grid">
            <div class="full form-group"><label>الاسم الرباعي</label><input type="text" name="name" class="inp"></div>
            <div class="form-group"><label>رقم الهوية</label><input type="text" name="nid" class="inp"></div>
            <div class="form-group"><label>الجوال</label><input type="text" name="phone" class="inp"></div>
        </div>
        <button class="btn" style="width:100%; margin-top:20px; justify-content:center">حفظ</button>
        <button type="button" onclick="closeM('tenM')" class="btn btn-outline" style="width:100%; margin-top:10px; justify-content:center">إلغاء</button>
    </form>
</div></div>

<div id="userM" class="modal"><div class="modal-content">
    <h2>موظف جديد</h2>
    <form method="POST">
        <input type="hidden" name="add_user" value="1">
        <div class="form-grid">
            <div class="form-group"><label>الاسم</label><input type="text" name="name" class="inp"></div>
            <div class="form-group"><label>المستخدم</label><input type="text" name="user" class="inp"></div>
            <div class="form-group"><label>كلمة المرور</label><input type="password" name="pass" class="inp"></div>
            <div class="form-group"><label>الدور</label><select name="role" class="inp"><option value="staff">موظف</option><option value="admin">مدير</option></select></div>
        </div>
        <button class="btn" style="margin-top:20px; width:100%">حفظ</button>
        <button type="button" onclick="closeM('userM')" class="btn btn-outline" style="margin-top:10px; width:100%">إلغاء</button>
    </form>
</div></div>

<script>
    function openM(id){
        const m = document.getElementById(id);
        if(m) { m.classList.add('active'); } 
        else { console.error('Modal not found: ' + id); }
    }
    function closeM(id){
        document.getElementById(id).classList.remove('active');
    }
    
    // Signature Logic
    const cvs = document.getElementById('sigCanvas');
    const ctx = cvs.getContext('2d');
    
    // Fit canvas to container
    function resizeCanvas() {
        cvs.width = cvs.parentElement.offsetWidth;
        cvs.height = cvs.parentElement.offsetHeight;
    }
    window.addEventListener('resize', resizeCanvas);
    setTimeout(resizeCanvas, 500); // Call once when modal might be open

    let isDrawing = false;
    function start(e) { isDrawing=true; ctx.beginPath(); ctx.moveTo(getX(e), getY(e)); }
    function end() { isDrawing=false; }
    function move(e) { if(!isDrawing)return; e.preventDefault(); ctx.lineTo(getX(e), getY(e)); ctx.stroke(); }
    function getX(e) { return (e.clientX || e.touches[0].clientX) - cvs.getBoundingClientRect().left; }
    function getY(e) { return (e.clientY || e.touches[0].clientY) - cvs.getBoundingClientRect().top; }
    
    cvs.addEventListener('mousedown', start); cvs.addEventListener('mouseup', end); cvs.addEventListener('mousemove', move);
    cvs.addEventListener('touchstart', start); cvs.addEventListener('touchend', end); cvs.addEventListener('touchmove', move);
    
    function clearSig() { ctx.clearRect(0,0,cvs.width,cvs.height); }
    function saveSig() { document.getElementById('sigField').value = cvs.toDataURL(); }
</script>

</body>
</html>
