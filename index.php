<?php
require 'db.php';
if(!isset($_SESSION['uid'])) { header("Location: login.php"); exit; }

// --- معالجة البيانات ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. إضافة عقار
    if (isset($_POST['add_prop'])) {
        $img = upload($_FILES['photo']);
        $pdo->prepare("INSERT INTO properties (name, type, address, manager_name, manager_phone, photo) VALUES (?,?,?,?,?,?)")
            ->execute([$_POST['name'], $_POST['type'], $_POST['address'], $_POST['manager'], $_POST['phone'], $img]);
        header("Location: ?p=properties"); exit;
    }
    // 2. إضافة وحدة
    if (isset($_POST['add_unit'])) {
        $img = upload($_FILES['photo']);
        $pdo->prepare("INSERT INTO units (property_id, unit_name, type, yearly_price, elec_meter_no, water_meter_no, status, photo) VALUES (?,?,?,?,?,?,?,?)")
            ->execute([$_POST['pid'], $_POST['name'], $_POST['type'], $_POST['price'], $_POST['elec'], $_POST['water'], 'available', $img]);
        header("Location: ?p=units"); exit;
    }
    // 3. إضافة عقد (تم الإصلاح الجذري)
    if (isset($_POST['add_contract'])) {
        $pdo->prepare("INSERT INTO contracts (tenant_id, unit_id, start_date, end_date, total_amount, payment_cycle, signature_img) VALUES (?,?,?,?,?,?,?)")
            ->execute([$_POST['tid'], $_POST['uid'], $_POST['start'], $_POST['end'], $_POST['total'], $_POST['cycle'], $_POST['sig']]);
        $pdo->prepare("UPDATE units SET status='rented' WHERE id=?")->execute([$_POST['uid']]);
        header("Location: ?p=contracts"); exit;
    }
    // 4. حفظ الإعدادات (شاملة الشعار والضريبة)
    if (isset($_POST['save_settings'])) {
        foreach($_POST['set'] as $k=>$v) saveSet($k,$v);
        // التعامل مع الشيك بوكس (إذا لم يرسل يعني 0)
        saveSet('vat_enabled', isset($_POST['set']['vat_enabled']) ? '1' : '0');
        
        if(!empty($_FILES['logo_file']['name'])){
            $l = upload($_FILES['logo_file']);
            saveSet('logo', $l);
        }
        header("Location: ?p=settings&ok=1"); exit;
    }
    // 5. إضافة مستأجر
    if (isset($_POST['add_tenant'])) {
        $i = upload($_FILES['id_photo']); $p = upload($_FILES['personal_photo']);
        $pdo->prepare("INSERT INTO tenants (full_name, phone, id_number, id_type, id_photo, personal_photo) VALUES (?,?,?,?,?,?)")
            ->execute([$_POST['name'], $_POST['phone'], $_POST['nid'], $_POST['id_type'], $i, $p]);
        header("Location: ?p=tenants"); exit;
    }
}

$p = $_GET['p'] ?? 'dashboard';
$me = $pdo->query("SELECT * FROM users WHERE id=".$_SESSION['uid'])->fetch();
$comp = getSet('company_name');
$logo = getSet('logo') ?: 'logo.png';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title><?= $comp ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* === GEMINI MASTER DARK THEME === */
        :root {
            --bg: #050505; --card: #121212; --border: #2a2a2a; 
            --primary: #6366f1; --accent: #a855f7; 
            --text: #ffffff; --muted: #a1a1aa;
            --grad: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);
        }
        body { font-family: 'Tajawal'; background: var(--bg); color: var(--text); margin: 0; display: flex; height: 100vh; overflow: hidden; }
        
        /* Sidebar */
        .sidebar { width: 300px; background: #0a0a0a; border-left: 1px solid var(--border); display: flex; flex-direction: column; padding: 25px; z-index: 10; box-shadow: 5px 0 50px rgba(0,0,0,0.5); }
        .logo-box { width: 100px; height: 100px; margin: 0 auto 20px; border-radius: 50%; background: white; padding: 5px; display: flex; align-items: center; justify-content: center; box-shadow: 0 0 30px rgba(99,102,241,0.3); }
        .nav-link { display: flex; align-items: center; gap: 15px; padding: 18px; margin-bottom: 10px; border-radius: 16px; color: var(--muted); text-decoration: none; font-weight: 500; transition: 0.3s; font-size: 16px; border: 1px solid transparent; }
        .nav-link:hover, .nav-link.active { background: rgba(99,102,241,0.1); color: white; border-color: rgba(99,102,241,0.3); box-shadow: 0 0 20px rgba(99,102,241,0.1); }
        .nav-link i { width: 25px; font-size: 20px; color: var(--primary); }

        /* Main Content */
        .main { flex: 1; padding: 40px; overflow-y: auto; background-image: radial-gradient(circle at top left, #1e1b4b, transparent 40%); }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px; }
        .page-title { font-size: 32px; font-weight: 800; background: var(--grad); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        
        /* Cards */
        .card { background: rgba(30, 30, 30, 0.4); backdrop-filter: blur(10px); border: 1px solid var(--border); border-radius: 24px; padding: 35px; margin-bottom: 30px; }
        
        /* Tables */
        table { width: 100%; border-collapse: separate; border-spacing: 0 10px; }
        th { text-align: right; padding: 15px; color: var(--muted); font-size: 14px; }
        td { background: #18181b; padding: 20px; border-top: 1px solid var(--border); border-bottom: 1px solid var(--border); font-size: 16px; }
        td:first-child { border-right: 1px solid var(--border); border-radius: 0 15px 15px 0; }
        td:last-child { border-left: 1px solid var(--border); border-radius: 15px 0 0 15px; }

        /* Buttons & Inputs */
        .btn { padding: 15px 30px; background: var(--grad); color: white; border: none; border-radius: 14px; cursor: pointer; font-weight: bold; font-size: 16px; display: inline-flex; align-items: center; gap: 10px; text-decoration: none; transition: 0.3s; box-shadow: 0 10px 20px rgba(99,102,241,0.2); }
        .btn:hover { transform: translateY(-3px); box-shadow: 0 15px 30px rgba(99,102,241,0.4); }
        .inp { width: 100%; padding: 18px; background: #050505; border: 2px solid #333; border-radius: 14px; color: white; font-size: 16px; outline: none; margin-bottom: 15px; transition: 0.3s; font-family: inherit; box-sizing: border-box; }
        .inp:focus { border-color: var(--primary); background: #0a0a0a; box-shadow: 0 0 15px rgba(99,102,241,0.1); }
        label { display: block; margin-bottom: 8px; color: #d1d5db; font-weight: bold; }

        /* Modals (Fixed) */
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.9); z-index: 9999; justify-content: center; align-items: center; backdrop-filter: blur(5px); }
        .modal-content { background: #121212; width: 800px; padding: 50px; border-radius: 30px; border: 1px solid #333; box-shadow: 0 0 60px rgba(99,102,241,0.2); max-height: 95vh; overflow-y: auto; position: relative; }
        .close-btn { position: absolute; left: 30px; top: 30px; cursor: pointer; color: #ef4444; font-size: 24px; }
        
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 25px; }
        .full { grid-column: span 2; }
    </style>
</head>
<body>

<div class="sidebar">
    <div style="text-align:center; margin-bottom:40px">
        <div class="logo-box"><img src="<?= $logo ?>" style="max-width:80%; max-height:80%"></div>
        <h3 style="margin:10px 0 0"><?= $comp ?></h3>
    </div>
    <a href="?p=dashboard" class="nav-link <?= $p=='dashboard'?'active':'' ?>"><i class="fa-solid fa-chart-line"></i> لوحة التحكم</a>
    <a href="?p=properties" class="nav-link <?= $p=='properties'?'active':'' ?>"><i class="fa-solid fa-building"></i> العقارات</a>
    <a href="?p=units" class="nav-link <?= $p=='units'?'active':'' ?>"><i class="fa-solid fa-door-open"></i> الوحدات</a>
    <a href="?p=contracts" class="nav-link <?= $p=='contracts'?'active':'' ?>"><i class="fa-solid fa-file-contract"></i> العقود</a>
    <a href="?p=tenants" class="nav-link <?= $p=='tenants'?'active':'' ?>"><i class="fa-solid fa-users"></i> العملاء</a>
    <?php if($me['role']=='admin'): ?>
    <a href="?p=settings" class="nav-link <?= $p=='settings'?'active':'' ?>"><i class="fa-solid fa-sliders"></i> الإعدادات</a>
    <?php endif; ?>
    <a href="logout.php" class="nav-link" style="margin-top:auto; color:#ef4444; border-color:rgba(239,68,68,0.2)"><i class="fa-solid fa-power-off"></i> خروج</a>
</div>

<div class="main">
    <div class="header">
        <div class="page-title"><?= $p=='settings' ? 'إعدادات النظام' : ($p=='dashboard' ? 'نظرة عامة' : 'إدارة '.ucfirst($p)) ?></div>
        <div style="background:#18181b; padding:10px 20px; border-radius:30px; border:1px solid #333">
            <i class="fa-solid fa-user-circle"></i> <?= $me['full_name'] ?>
        </div>
    </div>

    <?php if($p == 'dashboard'): ?>
        <div class="grid-2" style="grid-template-columns: repeat(3,1fr); margin-bottom:30px">
            <div class="card" style="margin:0; text-align:center">
                <i class="fa-solid fa-money-bill-wave" style="font-size:30px; color:#10b981; margin-bottom:15px"></i>
                <div style="color:#888">الإيرادات</div>
                <div style="font-size:32px; font-weight:800"><?= number_format($pdo->query("SELECT SUM(total_amount) FROM contracts")->fetchColumn()) ?></div>
            </div>
            <div class="card" style="margin:0; text-align:center">
                <i class="fa-solid fa-building" style="font-size:30px; color:#6366f1; margin-bottom:15px"></i>
                <div style="color:#888">نسبة الإشغال</div>
                <?php 
                    $tot = $pdo->query("SELECT count(*) FROM units")->fetchColumn() ?: 1;
                    $ren = $pdo->query("SELECT count(*) FROM units WHERE status='rented'")->fetchColumn();
                ?>
                <div style="font-size:32px; font-weight:800"><?= round(($ren/$tot)*100) ?>%</div>
            </div>
            <div class="card" style="margin:0; text-align:center">
                <i class="fa-solid fa-users" style="font-size:30px; color:#a855f7; margin-bottom:15px"></i>
                <div style="color:#888">العملاء</div>
                <div style="font-size:32px; font-weight:800"><?= $pdo->query("SELECT count(*) FROM tenants")->fetchColumn() ?></div>
            </div>
        </div>
    <?php endif; ?>

    <?php if($p == 'contracts'): ?>
        <button onclick="openM('conM')" class="btn" style="margin-bottom:30px"><i class="fa-solid fa-plus"></i> إنشاء عقد جديد</button>
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
                        <td><a href="invoice_print.php?cid=<?= $r['id'] ?>" target="_blank" class="btn" style="padding:10px 20px; font-size:14px; background:#18181b; border:1px solid #333">طباعة</a></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <?php if($p == 'settings'): ?>
        <form method="POST" enctype="multipart/form-data" class="grid-2">
            <input type="hidden" name="save_settings" value="1">
            
            <div class="card full">
                <h3 style="color:var(--primary); margin-top:0"><i class="fa-solid fa-building"></i> بيانات المنشأة</h3>
                <div class="grid-2">
                    <div><label>اسم المنشأة</label><input type="text" name="set[company_name]" value="<?= $comp ?>" class="inp"></div>
                    <div><label>رقم السجل التجاري</label><input type="text" name="set[cr_no]" value="<?= getSet('cr_no') ?>" class="inp"></div>
                    <div><label>تغيير الشعار</label><input type="file" name="logo_file" class="inp"></div>
                    <div><label>العملة</label><input type="text" name="set[currency]" value="<?= getSet('currency') ?>" class="inp"></div>
                </div>
            </div>

            <div class="card full">
                <h3 style="color:var(--accent); margin-top:0"><i class="fa-solid fa-percent"></i> إعدادات الضريبة</h3>
                <div class="grid-2">
                    <div style="display:flex; align-items:center; gap:15px; background:#18181b; padding:15px; border-radius:14px; border:2px solid #333">
                        <input type="checkbox" name="set[vat_enabled]" style="width:25px; height:25px" <?= getSet('vat_enabled')=='1'?'checked':'' ?>>
                        <label style="margin:0; cursor:pointer">تفعيل ضريبة القيمة المضافة</label>
                    </div>
                    <div><label>الرقم الضريبي</label><input type="text" name="set[vat_no]" value="<?= getSet('vat_no') ?>" class="inp"></div>
                    <div><label>نسبة الضريبة %</label><input type="number" name="set[vat_percent]" value="<?= getSet('vat_percent') ?>" class="inp"></div>
                </div>
            </div>

            <div class="card full">
                <h3 style="margin-top:0"><i class="fa-solid fa-file-invoice"></i> إعدادات الفواتير</h3>
                <label>شروط وأحكام الفاتورة (تظهر أسفل الفاتورة)</label>
                <textarea name="set[invoice_terms]" class="inp" style="height:100px"><?= getSet('invoice_terms') ?></textarea>
            </div>

            <button class="btn full" style="justify-content:center; padding:20px; font-size:18px">حفظ كافة الإعدادات</button>
        </form>
    <?php endif; ?>

    <?php if($p == 'units'): ?>
        <button onclick="openM('unitM')" class="btn" style="margin-bottom:30px">إضافة وحدة</button>
        <div class="card"><table><thead><tr><th>الوحدة</th><th>النوع</th><th>السعر</th><th>الحالة</th></tr></thead><tbody><?php $q=$pdo->query("SELECT * FROM units"); while($r=$q->fetch()): ?><tr><td><?= $r['unit_name'] ?></td><td><?= $r['type'] ?></td><td><?= number_format($r['yearly_price']) ?></td><td><?= $r['status'] ?></td></tr><?php endwhile; ?></tbody></table></div>
    <?php endif; ?>
    <?php if($p == 'properties'): ?>
        <button onclick="openM('propM')" class="btn" style="margin-bottom:30px">إضافة عقار</button>
        <div class="card"><table><thead><tr><th>الاسم</th><th>العنوان</th><th>المدير</th></tr></thead><tbody><?php $q=$pdo->query("SELECT * FROM properties"); while($r=$q->fetch()): ?><tr><td><?= $r['name'] ?></td><td><?= $r['address'] ?></td><td><?= $r['manager_name'] ?></td></tr><?php endwhile; ?></tbody></table></div>
    <?php endif; ?>
    <?php if($p == 'tenants'): ?>
        <button onclick="openM('tenM')" class="btn" style="margin-bottom:30px">إضافة عميل</button>
        <div class="card"><table><thead><tr><th>الاسم</th><th>الجوال</th><th>الهوية</th></tr></thead><tbody><?php $q=$pdo->query("SELECT * FROM tenants"); while($r=$q->fetch()): ?><tr><td><?= $r['full_name'] ?></td><td><?= $r['phone'] ?></td><td><?= $r['id_number'] ?></td></tr><?php endwhile; ?></tbody></table></div>
    <?php endif; ?>

</div>

<div id="conM" class="modal"><div class="modal-content">
    <i class="fa-solid fa-xmark close-btn" onclick="closeM('conM')"></i>
    <h2 style="margin-top:0; font-size:28px">إنشاء عقد جديد</h2>
    <form method="POST" onsubmit="saveSig()">
        <input type="hidden" name="add_contract" value="1"><input type="hidden" name="sig" id="sigField">
        <div class="grid-2">
            <div class="full"><label>العميل / المستأجر</label><select name="tid" class="inp" required><?php $ts=$pdo->query("SELECT * FROM tenants"); if($ts->rowCount()==0) echo "<option value=''>⚠️ لا يوجد عملاء! أضف عميل أولاً</option>"; foreach($ts as $t) echo "<option value='{$t['id']}'>{$t['full_name']}</option>"; ?></select></div>
            <div class="full"><label>الوحدة السكنية/التجارية</label><select name="uid" class="inp" required><?php $us=$pdo->query("SELECT * FROM units WHERE status='available'"); if($us->rowCount()==0) echo "<option value=''>⚠️ لا توجد وحدات متاحة</option>"; foreach($us as $u) echo "<option value='{$u['id']}'>{$u['unit_name']} ({$u['yearly_price']})</option>"; ?></select></div>
            <div><label>تاريخ البدء</label><input type="date" name="start" class="inp" required></div>
            <div><label>تاريخ الانتهاء</label><input type="date" name="end" class="inp" required></div>
            <div><label>قيمة العقد</label><input type="number" name="total" class="inp" required></div>
            <div><label>طريقة الدفع</label><select name="cycle" class="inp"><option value="yearly">سنوي</option><option value="monthly">شهري</option></select></div>
        </div>
        <div style="margin-top:20px">
            <label>التوقيع الإلكتروني</label>
            <div style="background:white; border-radius:15px; height:200px; overflow:hidden; border:2px dashed #444"><canvas id="sigCanvas" style="width:100%; height:100%"></canvas></div>
            <button type="button" onclick="clearSig()" style="margin-top:10px; color:#ef4444; background:none; border:none; cursor:pointer">مسح التوقيع</button>
        </div>
        <button class="btn" style="width:100%; justify-content:center; margin-top:20px; font-size:20px">إصدار العقد وحفظ</button>
    </form>
</div></div>

<div id="propM" class="modal"><div class="modal-content">
    <i class="fa-solid fa-xmark close-btn" onclick="closeM('propM')"></i>
    <h2>إضافة عقار</h2>
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="add_prop" value="1">
        <div class="grid-2">
            <div class="full"><label>اسم العقار</label><input type="text" name="name" class="inp"></div>
            <div><label>النوع</label><select name="type" class="inp"><option>عمارة</option><option>مجمع</option><option>أرض</option></select></div>
            <div><label>المدينة/الحي</label><input type="text" name="address" class="inp"></div>
            <div><label>اسم المدير</label><input type="text" name="manager" class="inp"></div>
            <div><label>جوال المدير</label><input type="text" name="phone" class="inp"></div>
            <div class="full"><label>صورة العقار</label><input type="file" name="photo" class="inp"></div>
        </div>
        <button class="btn full" style="justify-content:center; margin-top:20px">حفظ</button>
    </form>
</div></div>

<div id="unitM" class="modal"><div class="modal-content">
    <i class="fa-solid fa-xmark close-btn" onclick="closeM('unitM')"></i>
    <h2>إضافة وحدة</h2>
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="add_unit" value="1">
        <div class="grid-2">
            <div class="full"><label>العقار التابع له</label><select name="pid" class="inp"><?php $ps=$pdo->query("SELECT * FROM properties"); foreach($ps as $p) echo "<option value='{$p['id']}'>{$p['name']}</option>"; ?></select></div>
            <div><label>اسم الوحدة</label><input type="text" name="name" class="inp" placeholder="شقة 1"></div>
            <div><label>النوع</label><select name="type" class="inp"><option>شقة</option><option>محل</option><option>فيلا</option></select></div>
            <div><label>السعر</label><input type="number" name="price" class="inp"></div>
            <div><label>كهرباء</label><input type="text" name="elec" class="inp"></div>
            <div class="full"><label>صورة</label><input type="file" name="photo" class="inp"></div>
        </div>
        <button class="btn full" style="justify-content:center; margin-top:20px">حفظ</button>
    </form>
</div></div>

<div id="tenM" class="modal"><div class="modal-content">
    <i class="fa-solid fa-xmark close-btn" onclick="closeM('tenM')"></i>
    <h2>عميل جديد</h2>
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="add_tenant" value="1">
        <div class="grid-2">
            <div class="full"><label>الاسم</label><input type="text" name="name" class="inp"></div>
            <div><label>نوع الهوية</label><select name="id_type" class="inp"><option>هوية وطنية</option><option>إقامة</option><option>سجل تجاري</option></select></div>
            <div><label>رقم الهوية</label><input type="text" name="nid" class="inp"></div>
            <div><label>جوال</label><input type="text" name="phone" class="inp"></div>
            <div class="full"><label>صورة الهوية</label><input type="file" name="id_photo" class="inp"></div>
            <div class="full"><label>صورة شخصية</label><input type="file" name="personal_photo" class="inp"></div>
        </div>
        <button class="btn full" style="justify-content:center; margin-top:20px">حفظ</button>
    </form>
</div></div>

<script>
    function openM(id){ document.getElementById(id).style.display='flex'; if(id=='conM') setTimeout(resizeCanvas,100); }
    function closeM(id){ document.getElementById(id).style.display='none'; }
    
    // Canvas
    const cvs = document.getElementById('sigCanvas'); const ctx = cvs.getContext('2d');
    function resizeCanvas(){ cvs.width = cvs.parentElement.offsetWidth; cvs.height = cvs.parentElement.offsetHeight; }
    let drw=false;
    function start(e){drw=true;ctx.beginPath();ctx.moveTo(getX(e),getY(e));}
    function end(){drw=false;}
    function move(e){if(!drw)return;e.preventDefault();ctx.lineTo(getX(e),getY(e));ctx.stroke();}
    function getX(e){return(e.clientX||e.touches[0].clientX)-cvs.getBoundingClientRect().left;}
    function getY(e){return(e.clientY||e.touches[0].clientY)-cvs.getBoundingClientRect().top;}
    cvs.addEventListener('mousedown',start);cvs.addEventListener('mouseup',end);cvs.addEventListener('mousemove',move);
    cvs.addEventListener('touchstart',start);cvs.addEventListener('touchend',end);cvs.addEventListener('touchmove',move);
    function clearSig(){ctx.clearRect(0,0,cvs.width,cvs.height);}
    function saveSig(){document.getElementById('sigField').value=cvs.toDataURL();}
</script>

</body>
</html>
