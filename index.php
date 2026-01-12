<?php
require 'db.php';
if(!isset($_SESSION['uid'])) { header("Location: login.php"); exit; }

// === ENGINE ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. إضافة مورد
    if (isset($_POST['add_vendor'])) {
        $pdo->prepare("INSERT INTO vendors (name, service_type, phone, email) VALUES (?,?,?,?)")->execute([$_POST['name'], $_POST['type'], $_POST['phone'], $_POST['email']]);
        header("Location: ?p=vendors"); exit;
    }
    // 2. أمر صيانة جديد (مصروفات)
    if (isset($_POST['add_maintenance'])) {
        $pdo->prepare("INSERT INTO maintenance (property_id, unit_id, vendor_id, description, cost, request_date, status) VALUES (?,?,?,?,?,?,?)")
            ->execute([$_POST['pid'], $_POST['uid'], $_POST['vid'], $_POST['desc'], $_POST['cost'], date('Y-m-d'), 'pending']);
        header("Location: ?p=maintenance"); exit;
    }
    // 3. إضافة عقار، وحدة، مستأجر، عقد (نفس الكود السابق لضمان العمل)
    if (isset($_POST['add_prop'])) {
        $img = upload($_FILES['photo']);
        $pdo->prepare("INSERT INTO properties (name, type, address, manager_name, manager_phone, photo) VALUES (?,?,?,?,?,?)")->execute([$_POST['name'], $_POST['type'], $_POST['address'], $_POST['manager'], $_POST['phone'], $img]);
        header("Location: ?p=properties"); exit;
    }
    if (isset($_POST['add_unit'])) {
        $img = upload($_FILES['photo']);
        $pdo->prepare("INSERT INTO units (property_id, unit_name, type, yearly_price, elec_meter_no, water_meter_no, status, photo) VALUES (?,?,?,?,?,?,?,?)")->execute([$_POST['pid'], $_POST['name'], $_POST['type'], $_POST['price'], $_POST['elec'], $_POST['water'], 'available', $img]);
        header("Location: ?p=units"); exit;
    }
    if (isset($_POST['add_tenant'])) {
        $id_img = upload($_FILES['id_photo']);
        $pdo->prepare("INSERT INTO tenants (full_name, phone, id_number, id_type, cr_number, email, address, id_photo) VALUES (?,?,?,?,?,?,?,?)")->execute([$_POST['name'], $_POST['phone'], $_POST['nid'], $_POST['id_type'], $_POST['cr'], $_POST['email'], $_POST['address'], $id_img]);
        header("Location: ?p=tenants"); exit;
    }
    if (isset($_POST['add_contract'])) {
        $pdo->prepare("INSERT INTO contracts (tenant_id, unit_id, start_date, end_date, total_amount, payment_cycle, notes) VALUES (?,?,?,?,?,?,?)")->execute([$_POST['tid'], $_POST['uid'], $_POST['start'], $_POST['end'], $_POST['total'], $_POST['cycle'], $_POST['notes']]);
        $cid = $pdo->lastInsertId();
        $pdo->prepare("UPDATE units SET status='rented' WHERE id=?")->execute([$_POST['uid']]);
        $start = new DateTime($_POST['start']); $end = new DateTime($_POST['end']); $amount = $_POST['total']; $cycle = $_POST['cycle'];
        $interval_str = 'P1M'; $div = 12;
        if($cycle == 'quarterly') { $interval_str = 'P3M'; $div = 4; }
        if($cycle == 'biannual') { $interval_str = 'P6M'; $div = 2; }
        if($cycle == 'yearly') { $interval_str = 'P1Y'; $div = 1; }
        $installment = $amount / $div; 
        $currDate = clone $start; $i = 1;
        while ($currDate < $end) {
            $pdo->prepare("INSERT INTO payments (contract_id, title, amount, due_date, status) VALUES (?,?,?,?,?)")->execute([$cid, "دفعة #$i", $installment, $currDate->format('Y-m-d'), 'pending']);
            $currDate->add(new DateInterval($interval_str)); $i++;
            if($cycle == 'yearly' && $i > 1) break;
        }
        header("Location: ?p=contract_view&id=$cid"); exit;
    }
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
<html lang="ar" dir="rtl" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <title><?= $comp ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* === VARIABLES FOR THEMING === */
        :root {
            --bg: #050505; --card: #121212; --border: #2a2a2a; 
            --primary: #6366f1; --accent: #a855f7; --text: #ffffff; --muted: #a1a1aa;
            --sidebar: #0a0a0a; --grad: linear-gradient(135deg, rgba(99,102,241,0.1), rgba(168,85,247,0.1));
        }
        [data-theme="light"] {
            --bg: #f3f4f6; --card: #ffffff; --border: #e5e7eb; 
            --primary: #4f46e5; --accent: #7c3aed; --text: #1f2937; --muted: #6b7280;
            --sidebar: #1f2937; --grad: linear-gradient(135deg, #e0e7ff, #f3e8ff);
        }

        body { font-family: 'Tajawal'; background: var(--bg); color: var(--text); margin: 0; display: flex; height: 100vh; overflow: hidden; transition: 0.3s; }
        
        .sidebar { width: 280px; background: var(--sidebar); border-left: 1px solid var(--border); display: flex; flex-direction: column; padding: 25px; z-index: 10; box-shadow: 5px 0 30px rgba(0,0,0,0.1); transition: 0.3s; }
        .logo-box { width: 80px; height: 80px; margin: 0 auto 20px; border-radius: 50%; background: white; padding: 5px; display: flex; align-items: center; justify-content: center; box-shadow: 0 0 20px rgba(99,102,241,0.3); }
        .nav-link { display: flex; align-items: center; gap: 15px; padding: 14px; margin-bottom: 5px; border-radius: 12px; color: var(--muted); text-decoration: none; font-weight: 500; transition: 0.3s; border: 1px solid transparent; }
        .nav-link:hover, .nav-link.active { background: var(--primary); color: white; box-shadow: 0 5px 15px rgba(99,102,241,0.3); }
        .nav-link i { width: 25px; text-align: center; }

        .main { flex: 1; padding: 40px; overflow-y: auto; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px; }
        .theme-toggle { background: var(--card); border: 1px solid var(--border); color: var(--text); padding: 10px 15px; border-radius: 20px; cursor: pointer; display: flex; align-items: center; gap: 8px; transition:0.3s; }
        .theme-toggle:hover { border-color: var(--primary); }

        .card { background: var(--card); border: 1px solid var(--border); border-radius: 20px; padding: 25px; margin-bottom: 25px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: var(--card); padding: 25px; border-radius: 20px; border: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; position: relative; overflow: hidden; }
        
        table { width: 100%; border-collapse: separate; border-spacing: 0 8px; }
        th { text-align: right; padding: 15px; color: var(--muted); font-size: 14px; }
        td { background: var(--card); padding: 18px; border-top: 1px solid var(--border); border-bottom: 1px solid var(--border); font-size: 15px; }
        td:first-child { border-right: 1px solid var(--border); border-radius: 0 12px 12px 0; }
        td:last-child { border-left: 1px solid var(--border); border-radius: 12px 0 0 12px; }

        .btn { padding: 10px 20px; background: linear-gradient(135deg, var(--primary), var(--accent)); color: white; border: none; border-radius: 10px; cursor: pointer; font-weight: bold; font-family: inherit; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; }
        .badge { padding: 5px 10px; border-radius: 15px; font-size: 12px; font-weight: bold; }
        .badge.paid { background: rgba(16,185,129,0.2); color: #10b981; }
        .badge.late { background: rgba(239,68,68,0.2); color: #ef4444; }

        /* Modals */
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 1000; justify-content: center; align-items: center; backdrop-filter: blur(5px); }
        .modal-content { background: var(--card); width: 700px; padding: 40px; border-radius: 25px; border: 1px solid var(--border); color: var(--text); max-height: 90vh; overflow-y: auto; }
        .inp-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .inp { width: 100%; padding: 12px; background: var(--bg); border: 1px solid var(--border); border-radius: 10px; color: var(--text); outline: none; margin-bottom: 5px; box-sizing: border-box; font-family: inherit; }
        label { display: block; margin-bottom: 5px; color: var(--muted); font-size: 14px; }
        .full { grid-column: span 2; }
    </style>
</head>
<body>

<div class="sidebar">
    <div style="text-align:center; margin-bottom:30px">
        <div class="logo-box"><img src="<?= $logo ?>" style="max-width:80%; max-height:80%"></div>
        <h4 style="margin:10px 0 0; color:#9ca3af"><?= $comp ?></h4>
    </div>
    <a href="?p=dashboard" class="nav-link <?= $p=='dashboard'?'active':'' ?>"><i class="fa-solid fa-chart-pie"></i> لوحة التحكم</a>
    <a href="?p=properties" class="nav-link <?= $p=='properties'?'active':'' ?>"><i class="fa-solid fa-city"></i> العقارات</a>
    <a href="?p=units" class="nav-link <?= $p=='units'?'active':'' ?>"><i class="fa-solid fa-door-open"></i> الوحدات</a>
    <div style="height:1px; background:var(--border); margin:10px 0"></div>
    <a href="?p=maintenance" class="nav-link <?= $p=='maintenance'?'active':'' ?>"><i class="fa-solid fa-screwdriver-wrench"></i> الصيانة والمصروفات</a>
    <a href="?p=vendors" class="nav-link <?= $p=='vendors'?'active':'' ?>"><i class="fa-solid fa-hard-hat"></i> المقاولين والموردين</a>
    <div style="height:1px; background:var(--border); margin:10px 0"></div>
    <a href="?p=contracts" class="nav-link <?= $p=='contracts'?'active':'' ?>"><i class="fa-solid fa-file-contract"></i> العقود</a>
    <a href="?p=tenants" class="nav-link <?= $p=='tenants'?'active':'' ?>"><i class="fa-solid fa-users"></i> المستأجرين</a>
    <a href="?p=alerts" class="nav-link <?= $p=='alerts'?'active':'' ?>"><i class="fa-solid fa-bell"></i> المتأخرات</a>
    <a href="logout.php" class="nav-link" style="margin-top:auto; color:#ef4444"><i class="fa-solid fa-power-off"></i> خروج</a>
</div>

<div class="main">
    <div class="header">
        <h2 style="margin:0"><?= $p=='dashboard' ? 'نظرة عامة على الأملاك' : 'إدارة '.ucfirst($p) ?></h2>
        <div style="display:flex; gap:15px; align-items:center">
            <button class="theme-toggle" onclick="toggleTheme()">
                <i class="fa-solid fa-moon"></i> <span>مظهر</span>
            </button>
            <div style="background:var(--card); padding:8px 15px; border-radius:20px; border:1px solid var(--border)">
                <i class="fa-solid fa-user-tie"></i> <?= $me['full_name'] ?>
            </div>
        </div>
    </div>

    <?php if($p == 'dashboard'): 
        $income = $pdo->query("SELECT SUM(paid_amount) FROM payments")->fetchColumn() ?: 0;
        $expense = $pdo->query("SELECT SUM(cost) FROM maintenance")->fetchColumn() ?: 0;
        $profit = $income - $expense;
    ?>
    <div class="stats-grid">
        <div class="stat-card">
            <div><div style="font-size:26px; font-weight:800; color:#10b981"><?= number_format($income) ?></div><div style="color:var(--muted)">إجمالي التحصيل</div></div>
            <i class="fa-solid fa-arrow-down" style="color:#10b981; font-size:24px"></i>
        </div>
        <div class="stat-card">
            <div><div style="font-size:26px; font-weight:800; color:#ef4444"><?= number_format($expense) ?></div><div style="color:var(--muted)">مصروفات الصيانة</div></div>
            <i class="fa-solid fa-arrow-up" style="color:#ef4444; font-size:24px"></i>
        </div>
        <div class="stat-card" style="border-color:var(--primary)">
            <div><div style="font-size:26px; font-weight:800; color:var(--primary)"><?= number_format($profit) ?></div><div style="color:var(--muted)">صافي الربح (ROI)</div></div>
            <i class="fa-solid fa-wallet" style="color:var(--primary); font-size:24px"></i>
        </div>
    </div>
    
    <div class="card">
        <h3>آخر طلبات الصيانة (Work Orders)</h3>
        <table>
            <thead><tr><th>العقار</th><th>المشكلة</th><th>المقاول</th><th>التكلفة</th><th>الحالة</th></tr></thead>
            <tbody>
                <?php $mains=$pdo->query("SELECT m.*, p.name as pname, v.name as vname FROM maintenance m JOIN properties p ON m.property_id=p.id JOIN vendors v ON m.vendor_id=v.id ORDER BY id DESC LIMIT 5"); while($m=$mains->fetch()): ?>
                <tr>
                    <td><?= $m['pname'] ?></td>
                    <td><?= $m['description'] ?></td>
                    <td><?= $m['vname'] ?></td>
                    <td style="color:#ef4444">- <?= number_format($m['cost']) ?></td>
                    <td><span class="badge" style="background:var(--border)"><?= $m['status'] ?></span></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <?php if($p == 'maintenance'): ?>
    <button onclick="openM('mainM')" class="btn" style="margin-bottom:20px"><i class="fa-solid fa-plus"></i> أمر صيانة جديد</button>
    <div class="card">
        <table>
            <thead><tr><th>#</th><th>العقار</th><th>المشكلة</th><th>المقاول</th><th>التكلفة</th><th>التاريخ</th></tr></thead>
            <tbody>
                <?php $mains=$pdo->query("SELECT m.*, p.name as pname, v.name as vname FROM maintenance m JOIN properties p ON m.property_id=p.id JOIN vendors v ON m.vendor_id=v.id"); while($m=$mains->fetch()): ?>
                <tr>
                    <td><?= $m['id'] ?></td>
                    <td><?= $m['pname'] ?></td>
                    <td><?= $m['description'] ?></td>
                    <td><?= $m['vname'] ?></td>
                    <td style="color:#ef4444"><?= number_format($m['cost']) ?></td>
                    <td><?= $m['request_date'] ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <?php if($p == 'vendors'): ?>
    <button onclick="openM('vendM')" class="btn" style="margin-bottom:20px">إضافة مقاول/مورد</button>
    <div class="card">
        <table>
            <thead><tr><th>الاسم</th><th>التخصص</th><th>الجوال</th><th>تواصل</th></tr></thead>
            <tbody>
                <?php $vends=$pdo->query("SELECT * FROM vendors"); while($v=$vends->fetch()): ?>
                <tr>
                    <td><?= $v['name'] ?></td>
                    <td><?= $v['service_type'] ?></td>
                    <td><?= $v['phone'] ?></td>
                    <td><a href="tel:<?= $v['phone'] ?>" class="btn" style="padding:5px 10px; font-size:12px">اتصال</a></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <?php if($p == 'alerts'): ?>
    <div class="card">
        <h3 style="color:#ef4444">🚨 مطالبات متأخرة السداد</h3>
        <table>
            <thead><tr><th>المستأجر</th><th>المبلغ المتأخر</th><th>تاريخ الاستحقاق</th><th>إجراء</th></tr></thead>
            <tbody>
                <?php 
                $late = $pdo->query("SELECT p.*, c.id as cid, t.full_name, t.phone FROM payments p JOIN contracts c ON p.contract_id=c.id JOIN tenants t ON c.tenant_id=t.id WHERE p.status != 'paid' AND p.due_date < CURRENT_DATE");
                while($row=$late->fetch()):
                ?>
                <tr>
                    <td><?= $row['full_name'] ?></td>
                    <td style="color:#ef4444; font-weight:bold"><?= number_format($row['amount'] - $row['paid_amount']) ?></td>
                    <td><?= $row['due_date'] ?></td>
                    <td><a href="https://wa.me/<?= $row['phone'] ?>" target="_blank" class="btn" style="background:#25D366"><i class="fa-brands fa-whatsapp"></i> واتساب</a></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <?php if($p == 'contract_view'): 
        $id = $_GET['id'];
        $c = $pdo->query("SELECT c.*, t.full_name, u.unit_name FROM contracts c JOIN tenants t ON c.tenant_id=t.id JOIN units u ON c.unit_id=u.id WHERE c.id=$id")->fetch();
        $paid = $pdo->query("SELECT SUM(paid_amount) FROM payments WHERE contract_id=$id")->fetchColumn() ?: 0;
        $remaining = $c['total_amount'] - $paid;
    ?>
    <div class="card" style="background:var(--grad)">
        <div style="display:flex; justify-content:space-between">
            <div><h2>عقد #<?= $c['id'] ?></h2><p><?= $c['full_name'] ?> - <?= $c['unit_name'] ?></p></div>
            <div><h2><?= number_format($c['total_amount']) ?></h2><span class="badge paid">نشط</span></div>
        </div>
    </div>
    <div class="stats-grid">
        <div class="stat-card"><div><h3><?= number_format($paid) ?></h3><small>المدفوع</small></div></div>
        <div class="stat-card"><div><h3 style="color:#ef4444"><?= number_format($remaining) ?></h3><small>المتبقي</small></div></div>
    </div>
    <div class="card">
        <table>
            <thead><tr><th>#</th><th>تاريخ الاستحقاق</th><th>المبلغ</th><th>المدفوع</th><th>الحالة</th><th>إجراء</th></tr></thead>
            <tbody>
                <?php $pays=$pdo->query("SELECT * FROM payments WHERE contract_id=$id"); while($py=$pays->fetch()): $rem=$py['amount']-$py['paid_amount']; ?>
                <tr>
                    <td><?= $py['title'] ?></td><td><?= $py['due_date'] ?></td><td><?= number_format($py['amount']) ?></td>
                    <td style="color:#10b981"><?= number_format($py['paid_amount']) ?></td>
                    <td><span class="badge <?= $py['status']=='paid'?'paid':'late' ?>"><?= $py['status'] ?></span></td>
                    <td><?php if($py['status']!='paid'): ?><form method="POST"><input type="hidden" name="pay_installment" value="1"><input type="hidden" name="pay_id" value="<?= $py['id'] ?>"><button class="btn" style="padding:5px 10px; font-size:12px">تسديد</button></form><?php endif; ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <?php if(in_array($p, ['contracts','units','properties','tenants'])): ?>
    <button onclick="openM('addM')" class="btn" style="margin-bottom:20px">إضافة جديد</button>
    <div class="card">
        <table>
            <?php if($p=='contracts'): ?>
                <thead><tr><th>#</th><th>المستأجر</th><th>القيمة</th><th>تفاصيل</th></tr></thead>
                <tbody><?php $q=$pdo->query("SELECT c.*, t.full_name FROM contracts c JOIN tenants t ON c.tenant_id=t.id"); while($r=$q->fetch()): ?><tr><td>#<?= $r['id'] ?></td><td><?= $r['full_name'] ?></td><td><?= number_format($r['total_amount']) ?></td><td><a href="?p=contract_view&id=<?= $r['id'] ?>" class="btn" style="padding:5px 10px">عرض</a></td></tr><?php endwhile; ?></tbody>
            <?php elseif($p=='units'): ?>
                <thead><tr><th>الوحدة</th><th>النوع</th><th>السعر</th><th>الحالة</th></tr></thead>
                <tbody><?php $q=$pdo->query("SELECT * FROM units"); while($r=$q->fetch()): ?><tr><td><?= $r['unit_name'] ?></td><td><?= $r['type'] ?></td><td><?= number_format($r['yearly_price']) ?></td><td><span class="badge <?= $r['status']=='rented'?'late':'paid' ?>"><?= $r['status'] ?></span></td></tr><?php endwhile; ?></tbody>
            <?php elseif($p=='tenants'): ?>
                <thead><tr><th>الاسم</th><th>الجوال</th><th>الهوية</th></tr></thead>
                <tbody><?php $q=$pdo->query("SELECT * FROM tenants"); while($r=$q->fetch()): ?><tr><td><?= $r['full_name'] ?></td><td><?= $r['phone'] ?></td><td><?= $r['id_number'] ?></td></tr><?php endwhile; ?></tbody>
            <?php elseif($p=='properties'): ?>
                <thead><tr><th>الاسم</th><th>العنوان</th><th>المدير</th></tr></thead>
                <tbody><?php $q=$pdo->query("SELECT * FROM properties"); while($r=$q->fetch()): ?><tr><td><?= $r['name'] ?></td><td><?= $r['address'] ?></td><td><?= $r['manager_name'] ?></td></tr><?php endwhile; ?></tbody>
            <?php endif; ?>
        </table>
    </div>
    <?php endif; ?>

</div>

<div id="mainM" class="modal"><div class="modal-content">
    <div style="display:flex;justify-content:space-between"><h2>أمر صيانة جديد</h2><span onclick="closeM('mainM')" style="cursor:pointer">✕</span></div>
    <form method="POST">
        <input type="hidden" name="add_maintenance" value="1">
        <div class="inp-grid">
            <div class="inp-group"><label>العقار</label><select name="pid" class="inp"><?php $ps=$pdo->query("SELECT * FROM properties"); foreach($ps as $p) echo "<option value='{$p['id']}'>{$p['name']}</option>"; ?></select></div>
            <div class="inp-group"><label>الوحدة المتضررة</label><select name="uid" class="inp"><?php $us=$pdo->query("SELECT * FROM units"); foreach($us as $u) echo "<option value='{$u['id']}'>{$u['unit_name']}</option>"; ?></select></div>
            <div class="inp-group"><label>نوع المشكلة / الوصف</label><input type="text" name="desc" class="inp" placeholder="مثال: تسريب مياه في المطبخ"></div>
            <div class="inp-group"><label>المقاول المسؤول</label><select name="vid" class="inp"><?php $vs=$pdo->query("SELECT * FROM vendors"); if($vs->rowCount()==0) echo "<option value=''>لا يوجد مقاولين - أضف أولاً</option>"; foreach($vs as $v) echo "<option value='{$v['id']}'>{$v['name']} ({$v['service_type']})</option>"; ?></select></div>
            <div class="inp-group"><label>التكلفة التقديرية</label><input type="number" name="cost" class="inp"></div>
        </div>
        <button class="btn" style="width:100%;justify-content:center;margin-top:20px">إصدار أمر العمل</button>
    </form>
</div></div>

<div id="vendM" class="modal"><div class="modal-content">
    <div style="display:flex;justify-content:space-between"><h2>إضافة مقاول/مورد</h2><span onclick="closeM('vendM')" style="cursor:pointer">✕</span></div>
    <form method="POST">
        <input type="hidden" name="add_vendor" value="1">
        <div class="inp-grid">
            <div class="inp-group"><label>اسم الشركة/الشخص</label><input type="text" name="name" class="inp"></div>
            <div class="inp-group"><label>التخصص</label><select name="type" class="inp"><option>سباكة</option><option>كهرباء</option><option>تكييف</option><option>نظافة</option><option>مقاولات عامة</option></select></div>
            <div class="inp-group"><label>الجوال</label><input type="text" name="phone" class="inp"></div>
            <div class="inp-group"><label>الايميل</label><input type="email" name="email" class="inp"></div>
        </div>
        <button class="btn" style="width:100%;justify-content:center;margin-top:20px">حفظ</button>
    </form>
</div></div>

<div id="addM" class="modal"><div class="modal-content">
    <?php if($p=='contracts'): ?>
        <h2>عقد جديد</h2>
        <form method="POST"><input type="hidden" name="add_contract" value="1">
        <div class="inp-grid">
            <div class="inp-group"><label>مستأجر</label><select name="tid" class="inp"><?php $ts=$pdo->query("SELECT * FROM tenants"); foreach($ts as $t) echo "<option value='{$t['id']}'>{$t['full_name']}</option>"; ?></select></div>
            <div class="inp-group"><label>وحدة</label><select name="uid" class="inp"><?php $us=$pdo->query("SELECT * FROM units WHERE status='available'"); foreach($us as $u) echo "<option value='{$u['id']}'>{$u['unit_name']}</option>"; ?></select></div>
            <div class="inp-group"><label>من</label><input type="date" name="start" class="inp"></div>
            <div class="inp-group"><label>إلى</label><input type="date" name="end" class="inp"></div>
            <div class="inp-group"><label>المبلغ</label><input type="number" name="total" class="inp"></div>
            <div class="inp-group"><label>الدفع</label><select name="cycle" class="inp"><option value="monthly">شهري</option><option value="quarterly">ربع سنوي</option><option value="yearly">سنوي</option></select></div>
        </div><button class="btn" style="margin-top:20px">حفظ</button></form>
    <?php elseif($p=='tenants'): ?>
        <h2>مستأجر جديد</h2><form method="POST" enctype="multipart/form-data"><input type="hidden" name="add_tenant" value="1"><div class="inp-grid"><input type="text" name="name" placeholder="الاسم" class="inp"><input type="text" name="phone" placeholder="الجوال" class="inp"></div><button class="btn">حفظ</button></form>
    <?php else: ?>
        <h2>إضافة</h2><p>يرجى الذهاب للقسم المحدد للإضافة التفصيلية</p>
    <?php endif; ?>
</div></div>

<script>
    function openM(id){document.getElementById(id).style.display='flex'}
    function closeM(id){document.getElementById(id).style.display='none'}
    window.onclick=function(e){if(e.target.classList.contains('modal'))e.target.style.display='none'}

    // THEME TOGGLE LOGIC
    function toggleTheme() {
        const html = document.documentElement;
        const current = html.getAttribute('data-theme');
        const next = current === 'dark' ? 'light' : 'dark';
        html.setAttribute('data-theme', next);
        localStorage.setItem('theme', next);
    }
    // Load Saved Theme
    const saved = localStorage.getItem('theme') || 'dark';
    document.documentElement.setAttribute('data-theme', saved);
</script>

</body>
</html>
