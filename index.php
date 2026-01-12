<?php
require 'db.php';
if(!isset($_SESSION['uid'])) { header("Location: login.php"); exit; }

// === LOGIC ENGINE ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // إضافة عقار
    if (isset($_POST['add_prop'])) {
        $img = upload($_FILES['photo']);
        $pdo->prepare("INSERT INTO properties (name, type, address, manager_name, manager_phone, photo) VALUES (?,?,?,?,?,?)")
            ->execute([$_POST['name'], $_POST['type'], $_POST['address'], $_POST['manager'], $_POST['phone'], $img]);
        header("Location: ?p=properties"); exit;
    }

    // إضافة وحدة
    if (isset($_POST['add_unit'])) {
        $img = upload($_FILES['photo']);
        $pdo->prepare("INSERT INTO units (property_id, unit_name, type, yearly_price, elec_meter_no, water_meter_no, status, photo) VALUES (?,?,?,?,?,?,?,?)")
            ->execute([$_POST['pid'], $_POST['name'], $_POST['type'], $_POST['price'], $_POST['elec'], $_POST['water'], 'available', $img]);
        header("Location: ?p=units"); exit;
    }

    // إضافة مستأجر (بيانات كاملة)
    if (isset($_POST['add_tenant'])) {
        $id_img = upload($_FILES['id_photo']);
        $pdo->prepare("INSERT INTO tenants (full_name, phone, id_number, id_type, cr_number, email, address, id_photo) VALUES (?,?,?,?,?,?,?,?)")
            ->execute([$_POST['name'], $_POST['phone'], $_POST['nid'], $_POST['id_type'], $_POST['cr'], $_POST['email'], $_POST['address'], $id_img]);
        header("Location: ?p=tenants"); exit;
    }

    // إنشاء عقد + توليد الدفعات (الذكاء الاصطناعي للنظام)
    if (isset($_POST['add_contract'])) {
        // 1. إنشاء العقد
        $pdo->prepare("INSERT INTO contracts (tenant_id, unit_id, start_date, end_date, total_amount, payment_cycle, notes) VALUES (?,?,?,?,?,?,?)")
            ->execute([$_POST['tid'], $_POST['uid'], $_POST['start'], $_POST['end'], $_POST['total'], $_POST['cycle'], $_POST['notes']]);
        $cid = $pdo->lastInsertId();
        
        // 2. تحديث حالة الوحدة
        $pdo->prepare("UPDATE units SET status='rented' WHERE id=?")->execute([$_POST['uid']]);

        // 3. توليد الدفعات
        $start = new DateTime($_POST['start']);
        $end = new DateTime($_POST['end']);
        $amount = $_POST['total'];
        $cycle = $_POST['cycle'];
        
        // تحديد التكرار
        $interval_str = 'P1M'; // شهري افتراضي
        $div = 12; // تقسيم افتراضي
        
        if($cycle == 'quarterly') { $interval_str = 'P3M'; $div = 4; }
        if($cycle == 'biannual') { $interval_str = 'P6M'; $div = 2; }
        if($cycle == 'yearly') { $interval_str = 'P1Y'; $div = 1; }
        
        // حساب قيمة القسط (بشكل تقريبي بناء على المبلغ الإجمالي)
        $installment = $amount / $div; // يمكنك تعديل المنطق ليكون بناءً على التواريخ
        
        $currDate = clone $start;
        $i = 1;
        // حلقة التكرار لإنشاء الدفعات
        while ($currDate < $end) {
            $due = $currDate->format('Y-m-d');
            $pdo->prepare("INSERT INTO payments (contract_id, title, amount, due_date, status) VALUES (?,?,?,?,?)")
                ->execute([$cid, "دفعة #$i", $installment, $due, 'pending']);
            
            $currDate->add(new DateInterval($interval_str));
            $i++;
            if($cycle == 'yearly' && $i > 1) break; // توقف إذا كان سنوي (دفعة واحدة)
        }

        header("Location: ?p=contract_view&id=$cid"); exit;
    }

    // تسديد دفعة
    if (isset($_POST['pay_installment'])) {
        $pdo->prepare("UPDATE payments SET status='paid', paid_amount=amount, paid_date=CURRENT_DATE WHERE id=?")->execute([$_POST['pay_id']]);
        header("Location: ".$_SERVER['HTTP_REFERER']); exit;
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
        /* === MASTER EDITION DARK THEME === */
        :root { --bg: #050505; --card: #121212; --border: #2a2a2a; --primary: #6366f1; --accent: #a855f7; --text: #ffffff; --muted: #a1a1aa; }
        body { font-family: 'Tajawal'; background: var(--bg); color: var(--text); margin: 0; display: flex; height: 100vh; overflow: hidden; }
        
        /* Sidebar */
        .sidebar { width: 280px; background: #0a0a0a; border-left: 1px solid var(--border); display: flex; flex-direction: column; padding: 25px; z-index: 10; box-shadow: 5px 0 50px rgba(0,0,0,0.5); }
        .logo-box { width: 80px; height: 80px; margin: 0 auto 20px; border-radius: 50%; background: white; padding: 5px; display: flex; align-items: center; justify-content: center; box-shadow: 0 0 30px rgba(99,102,241,0.3); }
        .nav-link { display: flex; align-items: center; gap: 15px; padding: 16px; margin-bottom: 8px; border-radius: 14px; color: var(--muted); text-decoration: none; font-weight: 500; transition: 0.3s; border: 1px solid transparent; }
        .nav-link:hover, .nav-link.active { background: rgba(99,102,241,0.1); color: white; border-color: rgba(99,102,241,0.3); box-shadow: 0 0 20px rgba(99,102,241,0.1); }
        .nav-link i { width: 25px; text-align: center; color: var(--primary); }

        /* Main Content */
        .main { flex: 1; padding: 40px; overflow-y: auto; background-image: radial-gradient(circle at top left, #1e1b4b, transparent 40%); }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px; }
        .page-title { font-size: 32px; font-weight: 800; background: linear-gradient(to right, #fff, #aaa); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        
        /* Glass Cards */
        .card { background: rgba(30, 30, 30, 0.4); backdrop-filter: blur(10px); border: 1px solid var(--border); border-radius: 24px; padding: 30px; margin-bottom: 25px; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: rgba(20, 20, 20, 0.6); padding: 25px; border-radius: 20px; border: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; }
        
        /* Tables */
        table { width: 100%; border-collapse: separate; border-spacing: 0 10px; }
        th { text-align: right; padding: 15px; color: var(--muted); font-size: 14px; }
        td { background: #18181b; padding: 20px; border-top: 1px solid var(--border); border-bottom: 1px solid var(--border); font-size: 15px; }
        td:first-child { border-right: 1px solid var(--border); border-radius: 0 15px 15px 0; }
        td:last-child { border-left: 1px solid var(--border); border-radius: 15px 0 0 15px; }

        /* Elements */
        .btn { padding: 12px 25px; background: linear-gradient(135deg, var(--primary), var(--accent)); color: white; border: none; border-radius: 12px; cursor: pointer; font-weight: bold; font-family: inherit; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; transition: 0.3s; box-shadow: 0 5px 15px rgba(99,102,241,0.2); }
        .btn:hover { transform: translateY(-3px); box-shadow: 0 10px 25px rgba(99,102,241,0.4); }
        .btn-green { background: #10b981; box-shadow: 0 5px 15px rgba(16,185,129,0.2); }
        
        .badge { padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; }
        .badge.paid { background: rgba(16,185,129,0.2); color: #34d399; border: 1px solid rgba(16,185,129,0.3); }
        .badge.late { background: rgba(239,68,68,0.2); color: #f87171; border: 1px solid rgba(239,68,68,0.3); }
        .badge.pending { background: rgba(245,158,11,0.2); color: #fbbf24; border: 1px solid rgba(245,158,11,0.3); }

        /* Modals */
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.9); z-index: 1000; justify-content: center; align-items: center; backdrop-filter: blur(5px); }
        .modal-content { background: #121212; width: 800px; padding: 40px; border-radius: 30px; border: 1px solid #333; max-height: 90vh; overflow-y: auto; }
        .inp-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .inp { width: 100%; padding: 15px; background: #050505; border: 2px solid #333; border-radius: 12px; color: white; font-size: 16px; outline: none; margin-bottom: 5px; box-sizing: border-box; font-family: inherit; }
        .inp:focus { border-color: var(--primary); }
        label { display: block; margin-bottom: 8px; color: #d1d5db; font-size: 14px; }
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
    <a href="?p=units" class="nav-link <?= $p=='units'?'active':'' ?>"><i class="fa-solid fa-building"></i> الوحدات</a>
    <a href="?p=properties" class="nav-link <?= $p=='properties'?'active':'' ?>"><i class="fa-solid fa-city"></i> العقارات</a>
    <a href="?p=contracts" class="nav-link <?= $p=='contracts'?'active':'' ?>"><i class="fa-solid fa-file-contract"></i> العقود</a>
    <a href="?p=tenants" class="nav-link <?= $p=='tenants'?'active':'' ?>"><i class="fa-solid fa-users"></i> المستأجرين</a>
    <a href="?p=alerts" class="nav-link <?= $p=='alerts'?'active':'' ?>"><i class="fa-solid fa-bell"></i> التنبيهات</a>
    <?php if($me['role']=='admin'): ?>
    <a href="?p=settings" class="nav-link <?= $p=='settings'?'active':'' ?>"><i class="fa-solid fa-sliders"></i> الإعدادات</a>
    <?php endif; ?>
    <a href="logout.php" class="nav-link" style="margin-top:auto; color:#ef4444; border-color:rgba(239,68,68,0.2)"><i class="fa-solid fa-power-off"></i> خروج</a>
</div>

<div class="main">
    <div class="header">
        <div class="page-title">
            <?php 
            if($p=='contract_view') echo 'تفاصيل العقد والمديونية';
            elseif($p=='tenant_view') echo 'ملف المستأجر';
            elseif($p=='alerts') echo 'التنبيهات والمطالبات';
            else echo 'إدارة '.ucfirst($p);
            ?>
        </div>
        <div style="background:#18181b; padding:10px 20px; border-radius:30px; border:1px solid #333">
            <i class="fa-solid fa-user-circle"></i> <?= $me['full_name'] ?>
        </div>
    </div>

    <?php if($p == 'dashboard'): ?>
    <div class="stats-grid">
        <div class="stat-card">
            <div><div style="font-size:28px; font-weight:800; color:#fff"><?= $pdo->query("SELECT count(*) FROM units")->fetchColumn() ?></div><div style="color:#888">إجمالي الوحدات</div></div>
            <i class="fa-solid fa-building" style="font-size:30px; color:#6366f1"></i>
        </div>
        <div class="stat-card">
            <div><div style="font-size:28px; font-weight:800; color:#fff"><?= $pdo->query("SELECT count(*) FROM units WHERE status='rented'")->fetchColumn() ?></div><div style="color:#888">المؤجرة</div></div>
            <i class="fa-solid fa-check-circle" style="font-size:30px; color:#10b981"></i>
        </div>
        <div class="stat-card">
            <div><div style="font-size:28px; font-weight:800; color:#fff"><?= $pdo->query("SELECT count(*) FROM payments WHERE status='late'")->fetchColumn() ?></div><div style="color:#888">متأخرات</div></div>
            <i class="fa-solid fa-exclamation-triangle" style="font-size:30px; color:#ef4444"></i>
        </div>
    </div>
    <div class="card">
        <h3>قائمة العقارات</h3>
        <table>
            <thead><tr><th>العقار</th><th>العنوان</th><th>المسؤول</th><th>هاتف المسؤول</th></tr></thead>
            <tbody>
                <?php $q=$pdo->query("SELECT * FROM properties"); while($r=$q->fetch()): ?>
                <tr>
                    <td><div style="display:flex; align-items:center; gap:10px"><img src="<?= $r['photo']?:'logo.png' ?>" width="40" height="40" style="border-radius:8px"> <b><?= $r['name'] ?></b></div></td>
                    <td><?= $r['address'] ?></td><td><?= $r['manager_name'] ?></td><td style="color:#a855f7"><?= $r['manager_phone'] ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <?php if($p == 'contract_view'): 
        $id = $_GET['id'];
        $c = $pdo->query("SELECT c.*, t.full_name, t.phone, u.unit_name, p.name as pname FROM contracts c JOIN tenants t ON c.tenant_id=t.id JOIN units u ON c.unit_id=u.id JOIN properties p ON u.property_id=p.id WHERE c.id=$id")->fetch();
        $paid = $pdo->query("SELECT SUM(paid_amount) FROM payments WHERE contract_id=$id")->fetchColumn() ?: 0;
        $remaining = $c['total_amount'] - $paid;
    ?>
    <div class="card" style="background:linear-gradient(135deg, rgba(99,102,241,0.1), rgba(168,85,247,0.1)); border:1px solid rgba(99,102,241,0.3)">
        <div style="display:flex; justify-content:space-between; align-items:center">
            <div>
                <h2 style="margin:0; color:white">عقد إيجار #<?= $c['id'] ?></h2>
                <p style="margin:5px 0 0; color:#aaa"><?= $c['full_name'] ?> - <?= $c['pname'] ?> (<?= $c['unit_name'] ?>)</p>
            </div>
            <div style="text-align:left">
                <h2 style="margin:0; color:#a855f7"><?= number_format($c['total_amount']) ?> ر.س</h2>
                <span class="badge paid">عقد نشط</span>
            </div>
        </div>
    </div>
    
    <div class="stats-grid">
        <div class="stat-card"><div><div style="font-size:24px; font-weight:bold; color:#f87171"><?= number_format($remaining) ?></div><div style="color:#888">المتبقي</div></div></div>
        <div class="stat-card"><div><div style="font-size:24px; font-weight:bold; color:#34d399"><?= number_format($paid) ?></div><div style="color:#888">المدفوع</div></div></div>
        <div class="stat-card"><div><div style="font-size:24px; font-weight:bold; color:#fbbf24"><?= $c['payment_cycle'] ?></div><div style="color:#888">الدورة</div></div></div>
    </div>

    <div class="card">
        <h3>جدول الدفعات (الأقساط)</h3>
        <table>
            <thead><tr><th>#</th><th>تاريخ الاستحقاق</th><th>المبلغ</th><th>المدفوع</th><th>المتبقي</th><th>الحالة</th><th>إجراء</th></tr></thead>
            <tbody>
                <?php $pays=$pdo->query("SELECT * FROM payments WHERE contract_id=$id"); $i=1; while($py=$pays->fetch()): 
                    $rem = $py['amount'] - $py['paid_amount'];
                    $st = $py['status'];
                    $badge = $st=='paid'?'paid':($st=='late'?'late':'pending');
                ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td><?= $py['due_date'] ?></td>
                    <td><?= number_format($py['amount']) ?></td>
                    <td style="color:#34d399"><?= number_format($py['paid_amount']) ?></td>
                    <td style="color:#f87171"><?= number_format($rem) ?></td>
                    <td><span class="badge <?= $badge ?>"><?= $st ?></span></td>
                    <td>
                        <?php if($st != 'paid'): ?>
                        <form method="POST"><input type="hidden" name="pay_installment" value="1"><input type="hidden" name="pay_id" value="<?= $py['id'] ?>"><button class="btn btn-green" style="padding:5px 10px; font-size:12px">تسديد</button></form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <?php if($p == 'tenant_view'): 
        $tid = $_GET['id'];
        $t = $pdo->query("SELECT * FROM tenants WHERE id=$tid")->fetch();
    ?>
    <div class="card" style="display:flex; gap:20px; align-items:center">
        <img src="<?= $t['personal_photo']?:'logo.png' ?>" style="width:100px; height:100px; border-radius:50%; border:3px solid var(--primary)">
        <div>
            <h2 style="margin:0"><?= $t['full_name'] ?></h2>
            <p style="color:#aaa; margin:5px 0"><?= $t['phone'] ?> | <?= $t['id_number'] ?></p>
            <div style="display:flex; gap:10px">
                <span class="badge paid">سجل: <?= $t['cr_number'] ?></span>
                <a href="<?= $t['id_photo'] ?>" target="_blank" class="badge pending">صورة الهوية</a>
            </div>
        </div>
    </div>
    <div class="card">
        <h3>سجل العقود</h3>
        <table>
            <thead><tr><th>رقم العقد</th><th>الوحدة</th><th>التاريخ</th><th>القيمة</th><th>عرض</th></tr></thead>
            <tbody>
                <?php $conts=$pdo->query("SELECT c.*, u.unit_name FROM contracts c JOIN units u ON c.unit_id=u.id WHERE c.tenant_id=$tid"); while($row=$conts->fetch()): ?>
                <tr>
                    <td>#<?= $row['id'] ?></td><td><?= $row['unit_name'] ?></td><td><?= $row['start_date'] ?></td><td><?= number_format($row['total_amount']) ?></td>
                    <td><a href="?p=contract_view&id=<?= $row['id'] ?>" class="btn" style="padding:5px 10px">تفاصيل</a></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <?php if($p == 'alerts'): ?>
    <div class="card">
        <h3 style="color:#f87171">🚨 مطالبات متأخرة السداد</h3>
        <table>
            <thead><tr><th>المستأجر</th><th>العقد</th><th>المبلغ المتأخر</th><th>تاريخ الاستحقاق</th><th>تواصل</th></tr></thead>
            <tbody>
                <?php 
                $late = $pdo->query("SELECT p.*, c.id as cid, t.full_name, t.phone FROM payments p JOIN contracts c ON p.contract_id=c.id JOIN tenants t ON c.tenant_id=t.id WHERE p.status != 'paid' AND p.due_date < CURRENT_DATE");
                while($row=$late->fetch()):
                ?>
                <tr>
                    <td><?= $row['full_name'] ?></td>
                    <td><a href="?p=contract_view&id=<?= $row['cid'] ?>" style="color:var(--primary)">#<?= $row['cid'] ?></a></td>
                    <td style="color:#f87171; font-weight:bold"><?= number_format($row['amount'] - $row['paid_amount']) ?></td>
                    <td><?= $row['due_date'] ?></td>
                    <td><a href="https://wa.me/<?= $row['phone'] ?>" target="_blank" class="btn btn-green" style="background:#25D366"><i class="fa-brands fa-whatsapp"></i> واتساب</a></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <?php if($p == 'tenants'): ?>
    <button onclick="openM('tenM')" class="btn" style="margin-bottom:20px">مستأجر جديد</button>
    <div class="card"><table><thead><tr><th>الاسم</th><th>الجوال</th><th>الهوية</th><th>ملف</th></tr></thead><tbody><?php $q=$pdo->query("SELECT * FROM tenants"); while($r=$q->fetch()): ?><tr><td><?= $r['full_name'] ?></td><td><?= $r['phone'] ?></td><td><?= $r['id_number'] ?></td><td><a href="?p=tenant_view&id=<?= $r['id'] ?>" class="btn">عرض</a></td></tr><?php endwhile; ?></tbody></table></div>
    <?php endif; ?>

    <?php if($p == 'contracts'): ?>
    <button onclick="openM('conM')" class="btn" style="margin-bottom:20px">عقد جديد</button>
    <div class="card"><table><thead><tr><th>رقم العقد</th><th>المستأجر</th><th>القيمة</th><th>الحالة</th><th>عرض</th></tr></thead><tbody><?php $q=$pdo->query("SELECT c.*, t.full_name FROM contracts c JOIN tenants t ON c.tenant_id=t.id ORDER BY id DESC"); while($r=$q->fetch()): ?><tr><td>#<?= $r['id'] ?></td><td><?= $r['full_name'] ?></td><td><?= number_format($r['total_amount']) ?></td><td>نشط</td><td><a href="?p=contract_view&id=<?= $r['id'] ?>" class="btn">تفاصيل</a></td></tr><?php endwhile; ?></tbody></table></div>
    <?php endif; ?>
    
    <?php if($p == 'units'): ?>
    <button onclick="openM('unitM')" class="btn" style="margin-bottom:20px">إضافة وحدة</button>
    <div class="card"><table><thead><tr><th>الوحدة</th><th>النوع</th><th>السعر</th><th>الحالة</th></tr></thead><tbody><?php $q=$pdo->query("SELECT * FROM units"); while($r=$q->fetch()): ?><tr><td><?= $r['unit_name'] ?></td><td><?= $r['type'] ?></td><td><?= number_format($r['yearly_price']) ?></td><td><?= $r['status'] ?></td></tr><?php endwhile; ?></tbody></table></div>
    <?php endif; ?>

    <?php if($p == 'properties'): ?>
    <button onclick="openM('propM')" class="btn" style="margin-bottom:20px">إضافة عقار</button>
    <div class="card"><table><thead><tr><th>العقار</th><th>العنوان</th><th>المدير</th></tr></thead><tbody><?php $q=$pdo->query("SELECT * FROM properties"); while($r=$q->fetch()): ?><tr><td><?= $r['name'] ?></td><td><?= $r['address'] ?></td><td><?= $r['manager_name'] ?></td></tr><?php endwhile; ?></tbody></table></div>
    <?php endif; ?>

</div>

<div id="conM" class="modal"><div class="modal-content">
    <div style="display:flex;justify-content:space-between;margin-bottom:20px"><h2 style="margin:0">عقد جديد</h2><span onclick="closeM('conM')" style="cursor:pointer;font-size:24px">✕</span></div>
    <form method="POST">
        <input type="hidden" name="add_contract" value="1">
        <div class="inp-grid">
            <div class="inp-group"><label>المستأجر</label><select name="tid" class="inp"><?php $ts=$pdo->query("SELECT * FROM tenants"); foreach($ts as $t) echo "<option value='{$t['id']}'>{$t['full_name']}</option>"; ?></select></div>
            <div class="inp-group"><label>الوحدة</label><select name="uid" class="inp"><?php $us=$pdo->query("SELECT * FROM units WHERE status='available'"); foreach($us as $u) echo "<option value='{$u['id']}'>{$u['unit_name']}</option>"; ?></select></div>
            <div class="inp-group"><label>تاريخ البداية</label><input type="date" name="start" class="inp"></div>
            <div class="inp-group"><label>تاريخ النهاية</label><input type="date" name="end" class="inp"></div>
            <div class="inp-group"><label>القيمة الإجمالية</label><input type="number" name="total" class="inp"></div>
            <div class="inp-group"><label>دورة الدفعات</label><select name="cycle" class="inp"><option value="monthly">شهري</option><option value="quarterly">ربع سنوي</option><option value="biannual">نصف سنوي</option><option value="yearly">سنوي</option></select></div>
            <div class="full inp-group"><label>ملاحظات وشروط</label><textarea name="notes" class="inp" style="height:100px"></textarea></div>
        </div>
        <button class="btn" style="width:100%;justify-content:center;margin-top:20px">إنشاء العقد وتوليد الدفعات</button>
    </form>
</div></div>

<div id="tenM" class="modal"><div class="modal-content">
    <div style="display:flex;justify-content:space-between;margin-bottom:20px"><h2 style="margin:0">مستأجر جديد</h2><span onclick="closeM('tenM')" style="cursor:pointer;font-size:24px">✕</span></div>
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="add_tenant" value="1">
        <div class="inp-grid">
            <div class="inp-group"><label>الاسم الكامل</label><input type="text" name="name" class="inp" required></div>
            <div class="inp-group"><label>الجوال</label><input type="text" name="phone" class="inp" required></div>
            <div class="inp-group"><label>نوع الهوية</label><select name="id_type" class="inp"><option>هوية وطنية</option><option>إقامة</option></select></div>
            <div class="inp-group"><label>رقم الهوية</label><input type="text" name="nid" class="inp"></div>
            <div class="full inp-group"><label>صورة الهوية</label><input type="file" name="id_photo" class="inp"></div>
            <div class="full inp-group"><label>السجل التجاري (للشركات)</label><input type="text" name="cr" class="inp"></div>
            <div class="full inp-group"><label>البريد الإلكتروني</label><input type="email" name="email" class="inp"></div>
            <div class="full inp-group"><label>العنوان</label><input type="text" name="address" class="inp"></div>
        </div>
        <button class="btn" style="width:100%;justify-content:center;margin-top:20px">حفظ البيانات</button>
    </form>
</div></div>

<div id="propM" class="modal"><div class="modal-content"><form method="POST" enctype="multipart/form-data"><input type="hidden" name="add_prop" value="1"><h2>إضافة عقار</h2><div class="inp-grid"><div class="full"><label>الاسم</label><input type="text" name="name" class="inp"></div><div class="full"><label>صورة</label><input type="file" name="photo" class="inp"></div></div><button class="btn" style="margin-top:10px">حفظ</button></form></div></div>
<div id="unitM" class="modal"><div class="modal-content"><form method="POST" enctype="multipart/form-data"><input type="hidden" name="add_unit" value="1"><h2>إضافة وحدة</h2><div class="inp-grid"><div class="full"><label>العقار</label><select name="pid" class="inp"><?php $q=$pdo->query("SELECT * FROM properties"); while($r=$q->fetch()) echo "<option value='{$r['id']}'>{$r['name']}</option>"; ?></select></div><div class="full"><label>الاسم</label><input type="text" name="name" class="inp"></div></div><button class="btn" style="margin-top:10px">حفظ</button></form></div></div>

<script>
    function openM(id){document.getElementById(id).style.display='flex'}
    function closeM(id){document.getElementById(id).style.display='none'}
    window.onclick=function(e){if(e.target.classList.contains('modal'))e.target.style.display='none'}
</script>

</body>
</html>
