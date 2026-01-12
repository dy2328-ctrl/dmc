<?php
require 'db.php';
if(!isset($_SESSION['uid'])) { header("Location: login.php"); exit; }

// === BACKEND ENGINE ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // سداد دفعة
    if (isset($_POST['pay_installment'])) {
        $pid = $_POST['pay_id'];
        $amt = $_POST['amount'];
        $method = $_POST['method'];
        $pdo->prepare("INSERT INTO transactions (payment_id, amount_paid, payment_method, transaction_date, notes) VALUES (?,?,?,?,?)")->execute([$pid, $amt, $method, date('Y-m-d'), 'سداد دفعة']);
        $curr = $pdo->query("SELECT * FROM payments WHERE id=$pid")->fetch();
        $new_paid = $curr['paid_amount'] + $amt;
        $status = ($new_paid >= $curr['amount']) ? 'paid' : 'partial';
        $pdo->prepare("UPDATE payments SET paid_amount=?, status=?, paid_date=CURRENT_DATE WHERE id=?")->execute([$new_paid, $status, $pid]);
        header("Location: ".$_SERVER['HTTP_REFERER']); exit;
    }

    // إضافات النظام
    if(isset($_POST['add_prop'])){ $i=upload($_FILES['photo']); $pdo->prepare("INSERT INTO properties (name,type,address,manager_name,manager_phone,photo)VALUES(?,?,?,?,?,?)")->execute([$_POST['name'],$_POST['type'],$_POST['address'],$_POST['manager'],$_POST['phone'],$i]); header("Location: ?p=properties");exit;}
    if(isset($_POST['add_unit'])){ $i=upload($_FILES['photo']); $pdo->prepare("INSERT INTO units (property_id,unit_name,type,yearly_price,elec_meter_no,water_meter_no,status,photo)VALUES(?,?,?,?,?,?,?,?)")->execute([$_POST['pid'],$_POST['name'],$_POST['type'],$_POST['price'],$_POST['elec'],$_POST['water'],'available',$i]); header("Location: ?p=units");exit;}
    if(isset($_POST['add_tenant'])){ $i=upload($_FILES['id_photo']); $pdo->prepare("INSERT INTO tenants (full_name,phone,id_number,id_type,cr_number,email,address,id_photo)VALUES(?,?,?,?,?,?,?,?)")->execute([$_POST['name'],$_POST['phone'],$_POST['nid'],$_POST['id_type'],$_POST['cr'],$_POST['email'],$_POST['address'],$i]); header("Location: ?p=tenants");exit;}
    if(isset($_POST['add_vendor'])){ $pdo->prepare("INSERT INTO vendors (name,service_type,phone,email)VALUES(?,?,?,?)")->execute([$_POST['name'],$_POST['type'],$_POST['phone'],$_POST['email']]); header("Location: ?p=vendors");exit;}
    if(isset($_POST['add_maintenance'])){ $pdo->prepare("INSERT INTO maintenance (property_id,unit_id,vendor_id,description,cost,request_date)VALUES(?,?,?,?,?,CURRENT_DATE)")->execute([$_POST['pid'],$_POST['uid'],$_POST['vid'],$_POST['desc'],$_POST['cost']]); header("Location: ?p=maintenance");exit;}
    if(isset($_POST['save_settings'])){ saveSet('company_name',$_POST['company_name']); if(!empty($_FILES['logo']['name'])){$l=upload($_FILES['logo']); saveSet('logo',$l);} header("Location: ?p=settings");exit;}

    // إنشاء عقد ذكي وتوليد الدفعات
    if(isset($_POST['add_contract'])){
        $pdo->prepare("INSERT INTO contracts (tenant_id,unit_id,start_date,end_date,total_amount,payment_cycle,notes)VALUES(?,?,?,?,?,?,?)")->execute([$_POST['tid'],$_POST['uid'],$_POST['start'],$_POST['end'],$_POST['total'],$_POST['cycle'],$_POST['notes']]);
        $cid = $pdo->lastInsertId();
        $pdo->prepare("UPDATE units SET status='rented' WHERE id=?")->execute([$_POST['uid']]);
        
        $start = new DateTime($_POST['start']); $end = new DateTime($_POST['end']); $amount = $_POST['total']; $cycle = $_POST['cycle'];
        $div = ($cycle=='monthly')?12:($cycle=='quarterly'?4:($cycle=='biannual'?2:1));
        $inst = $amount/$div; $interval = ($cycle=='monthly')?'P1M':($cycle=='quarterly'?'P3M':($cycle=='biannual'?'P6M':'P1Y'));
        
        $curr = clone $start; $i=1;
        while($curr < $end){
            $pdo->prepare("INSERT INTO payments (contract_id,title,amount,due_date,status)VALUES(?,?,?,?,?)")->execute([$cid,"دفعة #$i",$inst,$curr->format('Y-m-d'),'pending']);
            $curr->add(new DateInterval($interval)); $i++; if($cycle=='yearly'&&$i>1)break;
        }
        header("Location: ?p=contract_view&id=$cid"); exit;
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* GEMINI ULTIMATE DARK THEME */
        :root { --bg:#050505; --card:#111; --border:#222; --primary:#6366f1; --accent:#a855f7; --text:#fff; --muted:#9ca3af; --green:#10b981; --red:#ef4444; }
        body { font-family:'Tajawal'; background:var(--bg); color:var(--text); margin:0; display:flex; height:100vh; overflow:hidden; }
        
        /* Sidebar */
        .sidebar { width:280px; background:#0a0a0a; border-left:1px solid var(--border); display:flex; flex-direction:column; padding:25px; box-shadow:5px 0 50px rgba(0,0,0,0.5); z-index:10; }
        .logo-box { width:70px; height:70px; margin:0 auto 20px; border-radius:50%; background:white; display:flex; align-items:center; justify-content:center; box-shadow:0 0 30px rgba(99,102,241,0.3); }
        .nav-link { display:flex; align-items:center; gap:12px; padding:15px; margin-bottom:5px; border-radius:12px; color:var(--muted); text-decoration:none; font-weight:500; transition:0.3s; }
        .nav-link:hover, .nav-link.active { background:rgba(99,102,241,0.1); color:white; border-right:3px solid var(--primary); }
        .nav-link i { width:25px; text-align:center; color:var(--primary); }

        .main { flex:1; padding:40px; overflow-y:auto; background-image:radial-gradient(circle at top left, #1e1b4b, transparent 40%); }
        .header { display:flex; justify-content:space-between; align-items:center; margin-bottom:40px; }
        
        /* Cards */
        .card { background:rgba(20,20,20,0.7); backdrop-filter:blur(10px); border:1px solid var(--border); border-radius:24px; padding:30px; margin-bottom:30px; }
        .stats-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:25px; margin-bottom:30px; }
        .stat-card { background:#0f0f0f; padding:25px; border-radius:20px; border:1px solid var(--border); position:relative; overflow:hidden; display:flex; flex-direction:column; justify-content:center; align-items:center; text-align:center; }
        .stat-val { font-size:28px; font-weight:800; margin:5px 0; }
        .stat-label { color:var(--muted); font-size:14px; }
        
        /* Special Cards (Green/Purple) */
        .card-green { background:linear-gradient(135deg, #10b981, #059669); color:white; border:none; box-shadow:0 10px 30px rgba(16,185,129,0.3); }
        .card-purple { background:linear-gradient(135deg, #6366f1, #4f46e5); color:white; border:none; box-shadow:0 10px 30px rgba(99,102,241,0.3); }

        /* Tables */
        .search-box { background:#111; border:1px solid #333; padding:10px 20px; border-radius:20px; color:white; width:300px; outline:none; font-family:inherit; }
        .search-box:focus { border-color:var(--primary); }
        table { width:100%; border-collapse:separate; border-spacing:0 10px; }
        th { text-align:right; padding:15px; color:var(--muted); font-size:14px; }
        td { background:#161616; padding:18px; border-top:1px solid var(--border); border-bottom:1px solid var(--border); }
        td:first-child { border-right:1px solid var(--border); border-radius:0 15px 15px 0; }
        td:last-child { border-left:1px solid var(--border); border-radius:15px 0 0 15px; }

        .btn { padding:12px 24px; background:linear-gradient(135deg, var(--primary), var(--accent)); color:white; border:none; border-radius:12px; cursor:pointer; font-weight:bold; display:inline-flex; align-items:center; gap:8px; text-decoration:none; font-size:14px; }
        .btn-green { background:linear-gradient(135deg, #10b981, #059669); }
        .badge { padding:5px 10px; border-radius:15px; font-size:11px; font-weight:bold; }
        .badge.paid { background:rgba(16,185,129,0.1); color:#34d399; }
        .badge.partial { background:rgba(245,158,11,0.1); color:#fbbf24; }
        .badge.late { background:rgba(239,68,68,0.1); color:#f87171; }

        /* Modals */
        .modal { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.9); z-index:1000; justify-content:center; align-items:center; }
        .modal-content { background:#111; width:700px; padding:40px; border-radius:30px; border:1px solid #333; max-height:90vh; overflow-y:auto; position:relative; }
        .close-btn { position:absolute; left:30px; top:30px; color:#ef4444; cursor:pointer; font-size:24px; }
        .inp { width:100%; padding:15px; background:#050505; border:1px solid #333; border-radius:12px; color:white; outline:none; margin-bottom:15px; box-sizing:border-box; font-family:inherit; }
        .inp:focus { border-color:var(--primary); }
        .inp-grid { display:grid; grid-template-columns:1fr 1fr; gap:20px; }
        .full { grid-column:span 2; }
    </style>
</head>
<body>

<div class="sidebar">
    <div style="text-align:center; margin-bottom:40px">
        <div class="logo-box"><img src="<?= $logo ?>" style="max-width:80%; max-height:80%"></div>
        <h3 style="margin:10px 0 0"><?= $comp ?></h3>
    </div>
    <a href="?p=dashboard" class="nav-link <?= $p=='dashboard'?'active':'' ?>"><i class="fa-solid fa-chart-pie"></i> لوحة القيادة</a>
    <a href="?p=properties" class="nav-link <?= $p=='properties'?'active':'' ?>"><i class="fa-solid fa-city"></i> العقارات</a>
    <a href="?p=units" class="nav-link <?= $p=='units'?'active':'' ?>"><i class="fa-solid fa-door-open"></i> الوحدات</a>
    <div style="height:1px; background:#222; margin:15px 0"></div>
    <a href="?p=contracts" class="nav-link <?= $p=='contracts'?'active':'' ?>"><i class="fa-solid fa-file-contract"></i> العقود</a>
    <a href="?p=tenants" class="nav-link <?= $p=='tenants'?'active':'' ?>"><i class="fa-solid fa-users"></i> المستأجرين</a>
    <a href="?p=alerts" class="nav-link <?= $p=='alerts'?'active':'' ?>"><i class="fa-solid fa-bell"></i> التنبيهات والمتأخرات</a>
    <div style="height:1px; background:#222; margin:15px 0"></div>
    <a href="?p=maintenance" class="nav-link <?= $p=='maintenance'?'active':'' ?>"><i class="fa-solid fa-screwdriver-wrench"></i> الصيانة</a>
    <a href="?p=vendors" class="nav-link <?= $p=='vendors'?'active':'' ?>"><i class="fa-solid fa-hard-hat"></i> المقاولين</a>
    <a href="?p=settings" class="nav-link <?= $p=='settings'?'active':'' ?>"><i class="fa-solid fa-gear"></i> الإعدادات</a>
    <a href="logout.php" class="nav-link" style="margin-top:auto; color:#ef4444"><i class="fa-solid fa-power-off"></i> خروج</a>
</div>

<div class="main">
    <div class="header">
        <div>
            <div style="font-size:24px; font-weight:800; color:white"><?= $p=='dashboard' ? 'لوحة التحكم الذكية' : 'إدارة '.ucfirst($p) ?></div>
            <div style="color:#666; font-size:14px">نظرة عامة على الأداء</div>
        </div>
        <div style="display:flex; gap:20px; align-items:center">
            <input type="text" id="tableSearch" onkeyup="searchTable()" class="search-box" placeholder="بحث سريع...">
            <div style="background:#111; padding:10px 20px; border-radius:30px; border:1px solid #333; font-weight:bold">
                <i class="fa-solid fa-user-circle"></i> <?= $me['full_name'] ?>
            </div>
        </div>
    </div>

    <?php if($p == 'dashboard'): 
        $income = $pdo->query("SELECT SUM(paid_amount) FROM payments")->fetchColumn() ?: 0;
        $total_con = $pdo->query("SELECT SUM(total_amount) FROM contracts")->fetchColumn() ?: 0;
        $expense = $pdo->query("SELECT SUM(cost) FROM maintenance")->fetchColumn() ?: 0;
        $occupied = $pdo->query("SELECT count(*) FROM units WHERE status='rented'")->fetchColumn();
        $total_u = $pdo->query("SELECT count(*) FROM units")->fetchColumn() ?: 1;
    ?>
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-val" style="color:#10b981"><?= number_format($income) ?></div><div class="stat-label">إجمالي التحصيل</div>
            <i class="fa-solid fa-wallet" style="margin-top:10px; font-size:24px; color:#10b981"></i>
        </div>
        <div class="stat-card">
            <div class="stat-val" style="color:#6366f1"><?= number_format($total_con) ?></div><div class="stat-label">قيمة العقود</div>
            <i class="fa-solid fa-file-invoice-dollar" style="margin-top:10px; font-size:24px; color:#6366f1"></i>
        </div>
        <div class="stat-card">
            <div class="stat-val" style="color:#ef4444"><?= number_format($expense) ?></div><div class="stat-label">المصروفات</div>
            <i class="fa-solid fa-tools" style="margin-top:10px; font-size:24px; color:#ef4444"></i>
        </div>
        <div class="stat-card">
            <div class="stat-val"><?= $occupied ?> / <?= $total_u ?></div><div class="stat-label">الوحدات المؤجرة</div>
            <i class="fa-solid fa-building" style="margin-top:10px; font-size:24px; color:white"></i>
        </div>
    </div>
    <div class="stats-grid" style="grid-template-columns: 2fr 1fr;">
        <div class="card"><h3>الأداء المالي</h3><canvas id="financeChart" height="100"></canvas></div>
        <div class="card"><h3>نسب الإشغال</h3><canvas id="occupancyChart" height="200"></canvas></div>
    </div>
    <script>
        new Chart(document.getElementById('financeChart'), {type:'bar',data:{labels:['قيمة العقود','المحصل','المصروفات'],datasets:[{label:'ريال',data:[<?=$total_con?>,<?=$income?>,<?=$expense?>],backgroundColor:['#6366f1','#10b981','#ef4444'],borderRadius:10}]},options:{scales:{y:{grid:{color:'#333'}},x:{grid:{display:false}}},plugins:{legend:{display:false}}}});
        new Chart(document.getElementById('occupancyChart'), {type:'doughnut',data:{labels:['مؤجر','شاغر'],datasets:[{data:[<?=$occupied?>,<?=$total_u-$occupied?>],backgroundColor:['#10b981','#333'],borderWidth:0}]},options:{cutout:'70%'}});
    </script>
    <?php endif; ?>

    <?php if($p == 'tenant_view'): 
        $id = $_GET['id'];
        $t = $pdo->query("SELECT * FROM tenants WHERE id=$id")->fetch();
        $total_pay = $pdo->query("SELECT SUM(amount) FROM payments p JOIN contracts c ON p.contract_id=c.id WHERE c.tenant_id=$id")->fetchColumn() ?: 0;
        $paid_pay = $pdo->query("SELECT SUM(paid_amount) FROM payments p JOIN contracts c ON p.contract_id=c.id WHERE c.tenant_id=$id")->fetchColumn() ?: 0;
        $active_c = $pdo->query("SELECT count(*) FROM contracts WHERE tenant_id=$id AND status='active'")->fetchColumn();
    ?>
    <div class="card card-green" style="display:flex; justify-content:space-between; align-items:center; padding:40px">
        <div style="display:flex; align-items:center; gap:20px">
            <img src="<?= $t['personal_photo']?:'logo.png' ?>" style="width:80px; height:80px; border-radius:50%; border:3px solid white; background:white">
            <div>
                <h1 style="margin:0; font-size:28px"><?= $t['full_name'] ?></h1>
                <p style="margin:5px 0 0; opacity:0.9"><i class="fa-solid fa-phone"></i> <?= $t['phone'] ?> | <i class="fa-solid fa-id-card"></i> <?= $t['id_number'] ?></p>
            </div>
        </div>
        <div style="text-align:left">
            <div style="font-size:32px; font-weight:800"><?= number_format($total_pay - $paid_pay) ?> <small style="font-size:14px">رصيد متبقي</small></div>
        </div>
    </div>
    <div class="stats-grid">
        <div class="stat-card"><div class="stat-val"><?= number_format($paid_pay) ?></div><div class="stat-label">إجمالي المدفوع</div></div>
        <div class="stat-card"><div class="stat-val"><?= number_format($total_pay) ?></div><div class="stat-label">إجمالي العقود</div></div>
        <div class="stat-card"><div class="stat-val"><?= $active_c ?></div><div class="stat-label">عقود نشطة</div></div>
    </div>
    <div class="card">
        <h3>سجل العقود</h3>
        <table>
            <thead><tr><th>رقم العقد</th><th>الوحدة</th><th>التاريخ</th><th>القيمة</th><th>عرض</th></tr></thead>
            <tbody>
                <?php $conts=$pdo->query("SELECT c.*, u.unit_name FROM contracts c JOIN units u ON c.unit_id=u.id WHERE c.tenant_id=$id"); while($r=$conts->fetch()): ?>
                <tr><td>#<?= $r['id'] ?></td><td><?= $r['unit_name'] ?></td><td><?= $r['start_date'] ?></td><td><?= number_format($r['total_amount']) ?></td><td><a href="?p=contract_view&id=<?= $r['id'] ?>" class="btn" style="padding:5px 15px">عرض</a></td></tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <?php if($p == 'contract_view'): 
        $id = $_GET['id'];
        $c = $pdo->query("SELECT c.*, t.full_name, t.phone, t.id_number, u.unit_name, p.name as pname FROM contracts c JOIN tenants t ON c.tenant_id=t.id JOIN units u ON c.unit_id=u.id JOIN properties p ON u.property_id=p.id WHERE c.id=$id")->fetch();
        $paid = $pdo->query("SELECT SUM(paid_amount) FROM payments WHERE contract_id=$id")->fetchColumn() ?: 0;
        $remaining = $c['total_amount'] - $paid;
    ?>
    <div class="card card-purple" style="display:flex; justify-content:space-between; align-items:center; padding:40px">
        <div>
            <h1 style="margin:0; font-size:28px">عقد إيجار #<?= $c['id'] ?></h1>
            <p style="margin:5px 0 0; opacity:0.9"><?= $c['full_name'] ?> - <?= $c['pname'] ?> (<?= $c['unit_name'] ?>)</p>
        </div>
        <div style="text-align:left">
            <h1 style="margin:0"><?= number_format($c['total_amount']) ?></h1>
            <span>قيمة الإيجار</span>
        </div>
    </div>
    <div class="stats-grid">
        <div class="stat-card"><div class="stat-val" style="color:#ef4444"><?= number_format($remaining) ?></div><div class="stat-label">المتبقي</div></div>
        <div class="stat-card"><div class="stat-val" style="color:#10b981"><?= number_format($paid) ?></div><div class="stat-label">المدفوع</div></div>
        <div class="stat-card"><div class="stat-val"><?= $c['payment_cycle'] ?></div><div class="stat-label">نظام الدفع</div></div>
    </div>
    <div class="card">
        <h3>جدول الدفعات</h3>
        <table>
            <thead><tr><th>#</th><th>تاريخ الاستحقاق</th><th>المبلغ</th><th>المدفوع</th><th>الحالة</th><th>إجراء</th></tr></thead>
            <tbody>
                <?php $pays=$pdo->query("SELECT * FROM payments WHERE contract_id=$id"); while($py=$pays->fetch()): ?>
                <tr>
                    <td><?= $py['title'] ?></td><td><?= $py['due_date'] ?></td><td><?= number_format($py['amount']) ?></td><td style="color:#10b981"><?= number_format($py['paid_amount']) ?></td>
                    <td><span class="badge <?= $py['status']=='paid'?'paid':'late' ?>"><?= $py['status'] ?></span></td>
                    <td><?php if($py['status']!='paid'): ?><button onclick="openPayModal(<?= $py['id'] ?>, <?= $py['amount']-$py['paid_amount'] ?>)" class="btn btn-green" style="padding:5px 15px; font-size:12px">سداد</button><?php endif; ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <?php if($p == 'alerts'): ?>
    <div class="card">
        <h2 style="color:#ef4444">🚨 المطالبات المتأخرة</h2>
        <table>
            <thead><tr><th>المستأجر</th><th>العقد</th><th>المبلغ المتأخر</th><th>تاريخ الاستحقاق</th><th>أيام التأخير</th><th>تواصل</th></tr></thead>
            <tbody>
                <?php 
                $late = $pdo->query("SELECT p.*, c.id as cid, t.full_name, t.phone FROM payments p JOIN contracts c ON p.contract_id=c.id JOIN tenants t ON c.tenant_id=t.id WHERE p.status != 'paid' AND p.due_date < CURRENT_DATE");
                while($r=$late->fetch()):
                    $days = floor((time() - strtotime($r['due_date'])) / (60 * 60 * 24));
                ?>
                <tr>
                    <td><b><?= $r['full_name'] ?></b></td>
                    <td><a href="?p=contract_view&id=<?= $r['cid'] ?>" class="btn" style="padding:2px 10px; font-size:12px">#<?= $r['cid'] ?></a></td>
                    <td style="color:#ef4444; font-weight:bold"><?= number_format($r['amount']-$r['paid_amount']) ?></td>
                    <td><?= $r['due_date'] ?></td>
                    <td><span class="badge late"><?= $days ?> يوم</span></td>
                    <td><a href="https://wa.me/<?= $r['phone'] ?>?text=نود تذكيركم بسداد الدفعة المستحقة" target="_blank" class="btn btn-green"><i class="fa-brands fa-whatsapp"></i> واتساب</a></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <?php if($p == 'settings'): ?>
    <div class="card" style="max-width:600px">
        <h2>إعدادات النظام</h2>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="save_settings" value="1">
            <div class="inp-grid">
                <div class="full"><label>اسم المنشأة</label><input type="text" name="company_name" value="<?= $comp ?>" class="inp"></div>
                <div class="full"><label>تغيير الشعار</label><input type="file" name="logo" class="inp"></div>
            </div>
            <button class="btn" style="margin-top:20px; width:100%">حفظ التغييرات</button>
        </form>
    </div>
    <?php endif; ?>

    <?php if(in_array($p, ['contracts','units','properties','tenants','maintenance','vendors'])): ?>
    <button onclick="openM('addM')" class="btn" style="margin-bottom:20px"><i class="fa-solid fa-plus"></i> إضافة جديد</button>
    <div class="card">
        <table id="dataTable">
            <?php if($p=='contracts'): ?>
                <thead><tr><th>#</th><th>المستأجر</th><th>القيمة</th><th>الحالة</th><th>عرض</th></tr></thead>
                <tbody><?php $q=$pdo->query("SELECT c.*, t.full_name FROM contracts c JOIN tenants t ON c.tenant_id=t.id ORDER BY id DESC"); while($r=$q->fetch()): ?><tr><td>#<?= $r['id'] ?></td><td><?= $r['full_name'] ?></td><td><?= number_format($r['total_amount']) ?></td><td><span class="badge paid">نشط</span></td><td><a href="?p=contract_view&id=<?= $r['id'] ?>" class="btn" style="padding:5px 15px">عرض</a></td></tr><?php endwhile; ?></tbody>
            <?php elseif($p=='tenants'): ?>
                <thead><tr><th>الاسم</th><th>الجوال</th><th>الهوية</th><th>ملف</th></tr></thead><tbody><?php $q=$pdo->query("SELECT * FROM tenants"); while($r=$q->fetch()): ?><tr><td><?= $r['full_name'] ?></td><td><?= $r['phone'] ?></td><td><?= $r['id_number'] ?></td><td><a href="?p=tenant_view&id=<?= $r['id'] ?>" class="btn">الملف</a></td></tr><?php endwhile; ?></tbody>
            <?php elseif($p=='units'): ?>
                <thead><tr><th>الوحدة</th><th>النوع</th><th>السعر</th><th>الحالة</th></tr></thead><tbody><?php $q=$pdo->query("SELECT * FROM units"); while($r=$q->fetch()): ?><tr><td><?= $r['unit_name'] ?></td><td><?= $r['type'] ?></td><td><?= number_format($r['yearly_price']) ?></td><td><span class="badge <?= $r['status']=='rented'?'late':'paid' ?>"><?= $r['status'] ?></span></td></tr><?php endwhile; ?></tbody>
            <?php elseif($p=='properties'): ?>
                <thead><tr><th>الاسم</th><th>العنوان</th><th>المدير</th></tr></thead><tbody><?php $q=$pdo->query("SELECT * FROM properties"); while($r=$q->fetch()): ?><tr><td><?= $r['name'] ?></td><td><?= $r['address'] ?></td><td><?= $r['manager_name'] ?></td></tr><?php endwhile; ?></tbody>
            <?php elseif($p=='vendors'): ?>
                <thead><tr><th>الاسم</th><th>الخدمة</th><th>الجوال</th></tr></thead><tbody><?php $q=$pdo->query("SELECT * FROM vendors"); while($r=$q->fetch()): ?><tr><td><?= $r['name'] ?></td><td><?= $r['service_type'] ?></td><td><?= $r['phone'] ?></td></tr><?php endwhile; ?></tbody>
            <?php elseif($p=='maintenance'): ?>
                <thead><tr><th>العقار</th><th>المشكلة</th><th>المقاول</th><th>التكلفة</th></tr></thead><tbody><?php $q=$pdo->query("SELECT m.*,p.name as pname,v.name as vname FROM maintenance m JOIN properties p ON m.property_id=p.id LEFT JOIN vendors v ON m.vendor_id=v.id"); while($r=$q->fetch()): ?><tr><td><?= $r['pname'] ?></td><td><?= $r['description'] ?></td><td><?= $r['vname'] ?></td><td style="color:#ef4444"><?= number_format($r['cost']) ?></td></tr><?php endwhile; ?></tbody>
            <?php endif; ?>
        </table>
    </div>
    <?php endif; ?>
</div>

<div id="payM" class="modal"><div class="modal-content"><span class="close-btn" onclick="closeM('payM')">✕</span><h2>تسجيل دفعة</h2><form method="POST"><input type="hidden" name="pay_installment" value="1"><input type="hidden" name="pay_id" id="pay_id_input"><label>المبلغ</label><input type="number" name="amount" id="pay_amount_input" class="inp"><label>طريقة الدفع</label><select name="method" class="inp"><option>كاش</option><option>تحويل بنكي</option></select><button class="btn btn-green" style="width:100%;margin-top:10px">تأكيد</button></form></div></div>

<div id="addM" class="modal"><div class="modal-content"><span class="close-btn" onclick="closeM('addM')">✕</span><h2>إضافة جديد</h2>
    <?php if($p=='contracts'): ?>
        <form method="POST"><input type="hidden" name="add_contract" value="1"><div class="inp-grid"><div class="inp-group"><label>مستأجر</label><select name="tid" class="inp"><?php $ts=$pdo->query("SELECT * FROM tenants"); foreach($ts as $t) echo "<option value='{$t['id']}'>{$t['full_name']}</option>"; ?></select></div><div class="inp-group"><label>وحدة</label><select name="uid" class="inp"><?php $us=$pdo->query("SELECT * FROM units WHERE status='available'"); foreach($us as $u) echo "<option value='{$u['id']}'>{$u['unit_name']}</option>"; ?></select></div><div class="inp-group"><label>من</label><input type="date" name="start" class="inp"></div><div class="inp-group"><label>إلى</label><input type="date" name="end" class="inp"></div><div class="inp-group"><label>القيمة</label><input type="number" name="total" class="inp"></div><div class="inp-group"><label>الدفعات</label><select name="cycle" class="inp"><option value="monthly">شهري</option><option value="quarterly">ربع سنوي</option><option value="yearly">سنوي</option></select></div></div><button class="btn" style="margin-top:20px; width:100%">حفظ</button></form>
    <?php elseif($p=='tenants'): ?>
        <form method="POST" enctype="multipart/form-data"><input type="hidden" name="add_tenant" value="1"><div class="inp-grid"><input type="text" name="name" placeholder="الاسم" class="inp"><input type="text" name="phone" placeholder="الجوال" class="inp"><input type="text" name="nid" placeholder="الهوية" class="inp"><div class="full"><label>صورة الهوية</label><input type="file" name="id_photo" class="inp"></div></div><button class="btn" style="width:100%;margin-top:10px">حفظ</button></form>
    <?php elseif($p=='units'): ?>
        <form method="POST" enctype="multipart/form-data"><input type="hidden" name="add_unit" value="1"><div class="inp-grid"><div class="full"><label>العقار</label><select name="pid" class="inp"><?php $ps=$pdo->query("SELECT * FROM properties"); foreach($ps as $p) echo "<option value='{$p['id']}'>{$p['name']}</option>"; ?></select></div><input type="text" name="name" placeholder="اسم الوحدة" class="inp"><input type="number" name="price" placeholder="السعر" class="inp"><div class="full"><label>صورة</label><input type="file" name="photo" class="inp"></div></div><button class="btn" style="width:100%;margin-top:10px">حفظ</button></form>
    <?php elseif($p=='properties'): ?>
        <form method="POST" enctype="multipart/form-data"><input type="hidden" name="add_prop" value="1"><div class="inp-grid"><input type="text" name="name" placeholder="الاسم" class="inp"><input type="text" name="address" placeholder="العنوان" class="inp"><div class="full"><label>صورة</label><input type="file" name="photo" class="inp"></div></div><button class="btn" style="width:100%;margin-top:10px">حفظ</button></form>
    <?php elseif($p=='vendors'): ?>
        <form method="POST"><input type="hidden" name="add_vendor" value="1"><div class="inp-grid"><input type="text" name="name" placeholder="الاسم" class="inp"><input type="text" name="type" placeholder="الخدمة" class="inp"></div><button class="btn" style="width:100%;margin-top:10px">حفظ</button></form>
    <?php elseif($p=='maintenance'): ?>
        <form method="POST"><input type="hidden" name="add_maintenance" value="1"><div class="inp-grid"><div class="full"><label>العقار</label><select name="pid" class="inp"><?php $ps=$pdo->query("SELECT * FROM properties"); foreach($ps as $p) echo "<option value='{$p['id']}'>{$p['name']}</option>"; ?></select></div><div class="full"><label>الوحدة</label><select name="uid" class="inp"><?php $us=$pdo->query("SELECT * FROM units"); foreach($us as $u) echo "<option value='{$u['id']}'>{$u['unit_name']}</option>"; ?></select></div><input type="text" name="desc" placeholder="وصف المشكلة" class="inp"><input type="number" name="cost" placeholder="التكلفة" class="inp"></div><button class="btn" style="width:100%;margin-top:10px">حفظ</button></form>
    <?php endif; ?>
</div></div>

<script>
    function openM(id){ if(document.getElementById(id)) document.getElementById(id).style.display='flex'; }
    function closeM(id){ document.getElementById(id).style.display='none'; }
    function openPayModal(id, amount){ document.getElementById('pay_id_input').value=id; document.getElementById('pay_amount_input').value=amount; openM('payM'); }
    window.onclick = function(e){ if(e.target.classList.contains('modal')) e.target.style.display='none'; }
    function searchTable() {
        var input = document.getElementById("tableSearch"), filter = input.value.toUpperCase(), tr = document.getElementById("dataTable").getElementsByTagName("tr");
        for (var i = 1; i < tr.length; i++) {
            var td = tr[i].getElementsByTagName("td")[0];
            if (td) tr[i].style.display = (td.textContent || td.innerText).toUpperCase().indexOf(filter) > -1 ? "" : "none";
        }
    }
</script>
</body>
</html>
