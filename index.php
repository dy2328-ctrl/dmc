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

    // إضافة عقد (الكود المصحح)
    if (isset($_POST['add_contract'])) {
        try {
            $pdo->prepare("INSERT INTO contracts (tenant_id, unit_id, start_date, end_date, total_amount, payment_cycle, signature_img) VALUES (?,?,?,?,?,?,?)")
                ->execute([$_POST['tid'], $_POST['uid'], $_POST['start'], $_POST['end'], $_POST['total'], $_POST['cycle'], $_POST['sig']]);
            
            // تحديث حالة الوحدة إلى مؤجرة
            $pdo->prepare("UPDATE units SET status='rented' WHERE id=?")->execute([$_POST['uid']]);
            
            header("Location: ?p=contracts&success=1"); exit;
        } catch (PDOException $e) {
            die("خطأ في قاعدة البيانات: " . $e->getMessage());
        }
    }

    // إضافة مستأجر
    if(isset($_POST['add_tenant'])){
        $pdo->prepare("INSERT INTO tenants (full_name, phone, id_number) VALUES (?,?,?)")->execute([$_POST['name'],$_POST['phone'],$_POST['nid']]);
        header("Location: ?p=tenants"); exit;
    }

    // إضافة موظف
    if(isset($_POST['add_user'])){
        $pdo->prepare("INSERT INTO users (full_name, username, password, role) VALUES (?,?,?,?)")->execute([$_POST['name'],$_POST['user'],password_hash($_POST['pass'],PASSWORD_DEFAULT),$_POST['role']]);
        header("Location: ?p=users"); exit;
    }

    // حفظ الإعدادات
    if (isset($_POST['save_settings'])) {
        foreach($_POST['set'] as $k=>$v) saveSet($k,$v);
        header("Location: ?p=settings"); exit;
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
    <title><?= $company ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root { --bg:#0f172a; --card:#1e293b; --border:#334155; --primary:#6366f1; --text:#f8fafc; --muted:#94a3b8; }
        body { font-family:'Tajawal'; background:var(--bg); color:var(--text); margin:0; display:flex; height:100vh; overflow:hidden; }
        
        .sidebar { width:260px; background:var(--card); border-left:1px solid var(--border); padding:20px; display:flex; flex-direction:column; }
        .logo-area { text-align:center; padding-bottom:20px; margin-bottom:20px; border-bottom:1px solid var(--border); }
        .logo-img { width:80px; height:80px; background:white; border-radius:50%; margin-bottom:10px; padding:5px; }
        .nav-link { display:flex; align-items:center; gap:12px; padding:14px; color:var(--muted); text-decoration:none; border-radius:10px; margin-bottom:5px; transition:0.3s; font-weight:500; }
        .nav-link:hover, .nav-link.active { background:var(--primary); color:white; }
        
        .main { flex:1; padding:30px; overflow-y:auto; background:radial-gradient(at top left, #1e1b4b, transparent 50%); }
        .header { display:flex; justify-content:space-between; align-items:center; margin-bottom:30px; }
        .card { background:var(--card); border:1px solid var(--border); border-radius:16px; padding:25px; margin-bottom:20px; }
        
        table { width:100%; border-collapse:collapse; }
        th { text-align:right; color:var(--muted); padding:15px; border-bottom:1px solid var(--border); }
        td { padding:15px; border-bottom:1px solid var(--border); font-weight:500; }
        
        .btn { padding:12px 24px; background:var(--primary); color:white; border:none; border-radius:10px; cursor:pointer; font-weight:bold; display:inline-flex; align-items:center; gap:8px; text-decoration:none; font-family:inherit; }
        .btn:hover { opacity:0.9; transform:translateY(-2px); }
        .btn-outline { background:transparent; border:2px solid var(--border); color:var(--muted); }

        /* MODALS */
        .modal { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); backdrop-filter:blur(5px); z-index:1000; justify-content:center; align-items:center; }
        .modal.active { display:flex !important; }
        .modal-content { background:#1e293b; width:700px; padding:40px; border-radius:20px; border:1px solid #475569; max-height:90vh; overflow-y:auto; position:relative; }
        
        .inp-grid { display:grid; grid-template-columns:1fr 1fr; gap:20px; }
        .inp-group label { display:block; color:#cbd5e1; margin-bottom:8px; font-weight:bold; }
        .inp { width:100%; padding:14px; background:#0f172a; border:2px solid #334155; border-radius:10px; color:white; outline:none; box-sizing:border-box; font-family:'Tajawal'; }
        .inp:focus { border-color:var(--primary); }
        .full { grid-column:span 2; }

        .stat-box { background:var(--card); padding:20px; border-radius:15px; border:1px solid var(--border); text-align:center; }
        .stat-num { font-size:32px; font-weight:800; margin:10px 0; }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="logo-area">
        <img src="logo.png" class="logo-img" onerror="this.src='https://via.placeholder.com/80'">
        <h3 style="margin:0; font-size:16px"><?= $company ?></h3>
    </div>
    <a href="?p=dashboard" class="nav-link <?= $p=='dashboard'?'active':'' ?>"><i class="fa-solid fa-chart-pie"></i> لوحة التحكم</a>
    <a href="?p=properties" class="nav-link <?= $p=='properties'?'active':'' ?>"><i class="fa-solid fa-building"></i> العقارات</a>
    <a href="?p=units" class="nav-link <?= $p=='units'?'active':'' ?>"><i class="fa-solid fa-door-open"></i> الوحدات</a>
    <a href="?p=contracts" class="nav-link <?= $p=='contracts'?'active':'' ?>"><i class="fa-solid fa-file-contract"></i> العقود</a>
    <a href="?p=tenants" class="nav-link <?= $p=='tenants'?'active':'' ?>"><i class="fa-solid fa-users"></i> المستأجرين</a>
    <?php if($me['role']=='admin'): ?>
    <a href="?p=users" class="nav-link <?= $p=='users'?'active':'' ?>"><i class="fa-solid fa-user-shield"></i> الموظفين</a>
    <a href="?p=settings" class="nav-link <?= $p=='settings'?'active':'' ?>"><i class="fa-solid fa-gear"></i> الإعدادات</a>
    <?php endif; ?>
    <a href="logout.php" class="nav-link" style="margin-top:auto; color:#ef4444"><i class="fa-solid fa-power-off"></i> خروج</a>
</div>

<div class="main">
    <div class="header">
        <h2 style="margin:0"><?php 
            if($p=='dashboard') echo 'لوحة القيادة';
            elseif($p=='properties') echo 'إدارة العقارات';
            elseif($p=='units') echo 'إدارة الوحدات';
            elseif($p=='contracts') echo 'العقود والإيجارات';
            elseif($p=='tenants') echo 'المستأجرين';
            else echo 'النظام';
        ?></h2>
        <div>مرحباً، <?= $me['full_name'] ?></div>
    </div>

    <?php if($p == 'dashboard'): ?>
        <div class="inp-grid" style="grid-template-columns:repeat(3,1fr); margin-bottom:30px">
            <div class="stat-box" style="border-bottom:4px solid #10b981">
                <div style="color:#94a3b8">الإيرادات</div>
                <div class="stat-num"><?= number_format($pdo->query("SELECT SUM(total_amount) FROM contracts")->fetchColumn()) ?></div>
            </div>
            <div class="stat-box" style="border-bottom:4px solid #6366f1">
                <div style="color:#94a3b8">الوحدات</div>
                <div class="stat-num"><?= $pdo->query("SELECT count(*) FROM units")->fetchColumn() ?></div>
            </div>
            <div class="stat-box" style="border-bottom:4px solid #f59e0b">
                <div style="color:#94a3b8">مستأجرين</div>
                <div class="stat-num"><?= $pdo->query("SELECT count(*) FROM tenants")->fetchColumn() ?></div>
            </div>
        </div>
        <div class="card">
            <h3>آخر العقود المضافة</h3>
            <table>
                <?php $q=$pdo->query("SELECT c.*, t.full_name FROM contracts c JOIN tenants t ON c.tenant_id=t.id ORDER BY id DESC LIMIT 5"); while($r=$q->fetch()): ?>
                <tr><td>#<?= $r['id'] ?></td><td><?= $r['full_name'] ?></td><td><?= number_format($r['total_amount']) ?></td><td><?= $r['start_date'] ?></td></tr>
                <?php endwhile; ?>
            </table>
        </div>
    <?php endif; ?>

    <?php if($p == 'properties'): ?>
        <button onclick="openModal('propModal')" class="btn" style="margin-bottom:20px"><i class="fa-solid fa-plus"></i> عقار جديد</button>
        <div class="card">
            <table>
                <thead><tr><th>الاسم</th><th>النوع</th><th>العنوان</th><th>المدير</th></tr></thead>
                <tbody>
                    <?php $q=$pdo->query("SELECT * FROM properties"); while($r=$q->fetch()): ?>
                    <tr><td><?= $r['name'] ?></td><td><?= $r['type'] ?></td><td><?= $r['address'] ?></td><td><?= $r['manager_name'] ?></td></tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <?php if($p == 'units'): ?>
        <button onclick="openModal('unitModal')" class="btn" style="margin-bottom:20px"><i class="fa-solid fa-plus"></i> وحدة جديدة</button>
        <div class="card">
            <table>
                <thead><tr><th>الوحدة</th><th>المبنى</th><th>النوع</th><th>السعر</th><th>الحالة</th></tr></thead>
                <tbody>
                    <?php $q=$pdo->query("SELECT u.*, p.name as pname FROM units u LEFT JOIN properties p ON u.property_id=p.id"); while($r=$q->fetch()): ?>
                    <tr>
                        <td><?= $r['unit_name'] ?></td><td><?= $r['pname'] ?></td><td><?= $r['type'] ?></td>
                        <td><?= number_format($r['yearly_price']) ?></td>
                        <td><span style="padding:5px 10px; border-radius:8px; background:<?= $r['status']=='rented'?'#991b1b':'#064e3b' ?>"><?= $r['status']=='rented'?'مؤجر':'متاح' ?></span></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <?php if($p == 'contracts'): ?>
        <button type="button" onclick="openModal('contractModal')" class="btn" style="margin-bottom:20px"><i class="fa-solid fa-file-contract"></i> إنشاء عقد جديد</button>
        
        <?php if(isset($_GET['success'])): ?>
            <div style="background:#064e3b; color:#a7f3d0; padding:15px; border-radius:10px; margin-bottom:20px">✅ تم إنشاء العقد بنجاح!</div>
        <?php endif; ?>

        <div class="card">
            <table>
                <thead><tr><th>#</th><th>المستأجر</th><th>الوحدة</th><th>القيمة</th><th>طباعة</th></tr></thead>
                <tbody>
                    <?php $q=$pdo->query("SELECT c.*, t.full_name, u.unit_name FROM contracts c JOIN tenants t ON c.tenant_id=t.id JOIN units u ON c.unit_id=u.id ORDER BY c.id DESC"); 
                    while($r=$q->fetch()): ?>
                    <tr>
                        <td><?= $r['id'] ?></td>
                        <td><?= $r['full_name'] ?></td>
                        <td><?= $r['unit_name'] ?></td>
                        <td><?= number_format($r['total_amount']) ?></td>
                        <td><a href="invoice_print.php?cid=<?= $r['id'] ?>" target="_blank" class="btn btn-outline" style="padding:5px 10px"><i class="fa-solid fa-print"></i></a></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <?php if($p == 'tenants'): ?>
        <button onclick="openModal('tenantModal')" class="btn" style="margin-bottom:20px">مستأجر جديد</button>
        <div class="card">
            <table>
                <thead><tr><th>الاسم</th><th>الجوال</th><th>الهوية</th></tr></thead>
                <tbody>
                    <?php $q=$pdo->query("SELECT * FROM tenants"); while($r=$q->fetch()): ?>
                    <tr><td><?= $r['full_name'] ?></td><td><?= $r['phone'] ?></td><td><?= $r['id_number'] ?></td></tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <?php if($p == 'settings'): ?>
        <form method="POST" class="card" style="max-width:500px">
            <h3>إعدادات الشركة</h3>
            <input type="hidden" name="save_settings" value="1">
            <div class="inp-group" style="margin-bottom:15px">
                <label>اسم الشركة</label>
                <input type="text" name="set[company_name]" value="<?= $company ?>" class="inp">
            </div>
            <div class="inp-group" style="margin-bottom:20px">
                <label>الرقم الضريبي</label>
                <input type="text" name="set[vat_no]" value="<?= getSet('vat_no') ?>" class="inp">
            </div>
            <button class="btn">حفظ التغييرات</button>
        </form>
    <?php endif; ?>

    <?php if($p == 'users'): ?>
        <button onclick="openModal('userModal')" class="btn" style="margin-bottom:20px">موظف جديد</button>
        <div class="card">
            <table>
                <?php $q=$pdo->query("SELECT * FROM users"); while($r=$q->fetch()): ?>
                <tr><td><?= $r['full_name'] ?></td><td><?= $r['username'] ?></td><td><?= $r['role'] ?></td></tr>
                <?php endwhile; ?>
            </table>
        </div>
    <?php endif; ?>

</div>

<div id="propModal" class="modal"><div class="modal-content">
    <h2 style="margin-top:0">إضافة عقار جديد</h2>
    <form method="POST">
        <input type="hidden" name="add_prop" value="1">
        <div class="inp-grid">
            <div class="full inp-group"><label>اسم العقار</label><input type="text" name="name" class="inp" required></div>
            <div class="inp-group"><label>النوع</label><select name="type" class="inp"><option>عمارة سكنية</option><option>مجمع تجاري</option><option>أرض</option></select></div>
            <div class="inp-group"><label>العنوان</label><input type="text" name="address" class="inp"></div>
            <div class="inp-group"><label>المدير</label><input type="text" name="manager" class="inp"></div>
            <div class="inp-group"><label>هاتف المدير</label><input type="text" name="phone" class="inp"></div>
        </div>
        <div style="margin-top:20px; display:flex; gap:10px">
            <button class="btn" style="flex:1; justify-content:center">حفظ</button>
            <button type="button" onclick="closeModal('propModal')" class="btn btn-outline">إلغاء</button>
        </div>
    </form>
</div></div>

<div id="unitModal" class="modal"><div class="modal-content">
    <h2 style="margin-top:0">إضافة وحدة</h2>
    <form method="POST">
        <input type="hidden" name="add_unit" value="1">
        <div class="inp-grid">
            <div class="full inp-group">
                <label>تابع للعقار</label>
                <select name="pid" class="inp" required>
                    <option value="">-- اختر عقاراً --</option>
                    <?php 
                    $props = $pdo->query("SELECT * FROM properties")->fetchAll();
                    foreach($props as $pr) echo "<option value='{$pr['id']}'>{$pr['name']}</option>"; 
                    ?>
                </select>
            </div>
            <div class="inp-group"><label>اسم الوحدة</label><input type="text" name="name" class="inp" placeholder="شقة 1 / معرض A"></div>
            <div class="inp-group"><label>النوع</label><select name="type" class="inp"><option>شقة</option><option>محل تجاري</option><option>مستودع</option></select></div>
            <div class="inp-group"><label>السعر السنوي</label><input type="number" name="price" class="inp"></div>
            <div class="inp-group"><label>عداد كهرباء</label><input type="text" name="elec" class="inp"></div>
            <div class="inp-group"><label>عداد مياه</label><input type="text" name="water" class="inp"></div>
        </div>
        <div style="margin-top:20px; display:flex; gap:10px">
            <button class="btn" style="flex:1; justify-content:center">حفظ</button>
            <button type="button" onclick="closeModal('unitModal')" class="btn btn-outline">إلغاء</button>
        </div>
    </form>
</div></div>

<div id="contractModal" class="modal"><div class="modal-content">
    <h2 style="margin-top:0">إصدار عقد جديد</h2>
    <form method="POST" onsubmit="saveSig()">
        <input type="hidden" name="add_contract" value="1">
        <input type="hidden" name="sig" id="sigField">
        
        <div class="inp-grid">
            <div class="inp-group">
                <label>المستأجر</label>
                <select name="tid" class="inp" required>
                    <option value="">-- اختر المستأجر --</option>
                    <?php 
                    $tenants = $pdo->query("SELECT * FROM tenants")->fetchAll();
                    foreach($tenants as $t) echo "<option value='{$t['id']}'>{$t['full_name']}</option>";
                    ?>
                </select>
            </div>
            
            <div class="inp-group">
                <label>الوحدة (المتاحة فقط)</label>
                <select name="uid" class="inp" required>
                    <option value="">-- اختر الوحدة --</option>
                    <?php 
                    $units = $pdo->query("SELECT * FROM units WHERE status='available'")->fetchAll();
                    foreach($units as $u) echo "<option value='{$u['id']}'>{$u['unit_name']} ({$u['yearly_price']})</option>";
                    ?>
                </select>
            </div>
            
            <div class="inp-group"><label>بداية العقد</label><input type="date" name="start" class="inp" required></div>
            <div class="inp-group"><label>نهاية العقد</label><input type="date" name="end" class="inp" required></div>
            <div class="inp-group"><label>القيمة الإجمالية</label><input type="number" name="total" class="inp" required></div>
            <div class="inp-group"><label>الدفع</label><select name="cycle" class="inp"><option value="yearly">سنوي</option><option value="monthly">شهري</option></select></div>
        </div>

        <div style="margin-top:20px">
            <label style="display:block; margin-bottom:5px">التوقيع الإلكتروني</label>
            <div style="border:2px dashed #475569; background:white; border-radius:10px; height:150px; overflow:hidden">
                <canvas id="sigCanvas" style="width:100%; height:100%"></canvas>
            </div>
            <button type="button" onclick="clearSig()" style="margin-top:5px; color:red; background:none; border:none; cursor:pointer">مسح التوقيع</button>
        </div>

        <div style="margin-top:20px; display:flex; gap:10px">
            <button class="btn" style="flex:1; justify-content:center">إصدار العقد</button>
            <button type="button" onclick="closeModal('contractModal')" class="btn btn-outline">إلغاء</button>
        </div>
    </form>
</div></div>

<div id="tenantModal" class="modal"><div class="modal-content">
    <h2 style="margin-top:0">إضافة مستأجر</h2>
    <form method="POST">
        <input type="hidden" name="add_tenant" value="1">
        <div class="inp-grid">
            <div class="full inp-group"><label>الاسم</label><input type="text" name="name" class="inp" required></div>
            <div class="inp-group"><label>الجوال</label><input type="text" name="phone" class="inp"></div>
            <div class="inp-group"><label>الهوية</label><input type="text" name="nid" class="inp"></div>
        </div>
        <div style="margin-top:20px; display:flex; gap:10px">
            <button class="btn" style="flex:1; justify-content:center">حفظ</button>
            <button type="button" onclick="closeModal('tenantModal')" class="btn btn-outline">إلغاء</button>
        </div>
    </form>
</div></div>

<div id="userModal" class="modal"><div class="modal-content">
    <h2 style="margin-top:0">موظف جديد</h2>
    <form method="POST">
        <input type="hidden" name="add_user" value="1">
        <div class="inp-grid">
            <div class="inp-group"><label>الاسم</label><input type="text" name="name" class="inp" required></div>
            <div class="inp-group"><label>المستخدم</label><input type="text" name="user" class="inp" required></div>
            <div class="inp-group"><label>كلمة المرور</label><input type="password" name="pass" class="inp" required></div>
            <div class="inp-group"><label>الدور</label><select name="role" class="inp"><option value="staff">موظف</option><option value="admin">مدير</option></select></div>
        </div>
        <div style="margin-top:20px; display:flex; gap:10px">
            <button class="btn" style="flex:1; justify-content:center">حفظ</button>
            <button type="button" onclick="closeModal('userModal')" class="btn btn-outline">إلغاء</button>
        </div>
    </form>
</div></div>

<script>
    function openModal(id) {
        document.getElementById(id).classList.add('active');
        if(id === 'contractModal') {
            setTimeout(resizeCanvas, 100); // Fix canvas size when modal opens
        }
    }
    
    function closeModal(id) {
        document.getElementById(id).classList.remove('active');
    }

    // Signature Logic
    const cvs = document.getElementById('sigCanvas');
    const ctx = cvs.getContext('2d');
    let isDrawing = false;

    function resizeCanvas() {
        cvs.width = cvs.parentElement.offsetWidth;
        cvs.height = cvs.parentElement.offsetHeight;
    }
    window.addEventListener('resize', resizeCanvas);

    function start(e) { isDrawing = true; ctx.beginPath(); ctx.moveTo(getX(e), getY(e)); }
    function end() { isDrawing = false; }
    function move(e) { if(!isDrawing) return; e.preventDefault(); ctx.lineTo(getX(e), getY(e)); ctx.stroke(); }
    function getX(e) { return (e.clientX || e.touches[0].clientX) - cvs.getBoundingClientRect().left; }
    function getY(e) { return (e.clientY || e.touches[0].clientY) - cvs.getBoundingClientRect().top; }

    cvs.addEventListener('mousedown', start); cvs.addEventListener('mouseup', end); cvs.addEventListener('mousemove', move);
    cvs.addEventListener('touchstart', start); cvs.addEventListener('touchend', end); cvs.addEventListener('touchmove', move);

    function clearSig() { ctx.clearRect(0,0,cvs.width,cvs.height); }
    function saveSig() { document.getElementById('sigField').value = cvs.toDataURL(); }
</script>

</body>
</html>
