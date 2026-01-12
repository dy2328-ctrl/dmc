<?php
require 'db.php';
if(!isset($_SESSION['uid'])) { header("Location: login.php"); exit; }

// === ACTION HANDLER ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // DELETE ACTION
    if(isset($_POST['delete_item'])){
        $table = $_POST['table']; $id = $_POST['id'];
        $pdo->prepare("DELETE FROM $table WHERE id=?")->execute([$id]);
        logAct("تم حذف سجل من $table رقم $id", "danger");
        header("Location: ?p=".$_GET['p']); exit;
    }

    // PAY INSTALLMENT
    if (isset($_POST['pay_installment'])) {
        $pid = $_POST['pay_id']; $amt = $_POST['amount'];
        $pdo->prepare("INSERT INTO transactions (payment_id, amount_paid, payment_method, transaction_date) VALUES (?,?,?,CURDATE())")->execute([$pid, $amt, $_POST['method']]);
        $curr = $pdo->query("SELECT * FROM payments WHERE id=$pid")->fetch();
        $new_paid = $curr['paid_amount'] + $amt;
        $st = ($new_paid >= $curr['amount']) ? 'paid' : 'partial';
        $pdo->prepare("UPDATE payments SET paid_amount=?, status=?, paid_date=CURDATE() WHERE id=?")->execute([$new_paid, $st, $pid]);
        logAct("استلام دفعة بقيمة $amt للعقد", "success");
        header("Location: ".$_SERVER['HTTP_REFERER']); exit;
    }

    // SETTINGS SAVE
    if (isset($_POST['save_settings'])) {
        foreach($_POST['set'] as $k=>$v) saveSet($k,$v);
        saveSet('vat_enabled', isset($_POST['set']['vat_enabled'])?'1':'0'); // Checkbox fix
        if(!empty($_FILES['logo']['name'])){ $l=upload($_FILES['logo']); saveSet('logo',$l); }
        logAct("تم تحديث إعدادات النظام", "warning");
        header("Location: ?p=settings"); exit;
    }

    // ADD ACTIONS
    if(isset($_POST['add_prop'])){ $i=upload($_FILES['photo']); $pdo->prepare("INSERT INTO properties (name,type,address,manager_name,manager_phone,photo)VALUES(?,?,?,?,?,?)")->execute([$_POST['name'],$_POST['type'],$_POST['address'],$_POST['manager'],$_POST['phone'],$i]); logAct("إضافة عقار: ".$_POST['name']); header("Location: ?p=properties");exit;}
    
    if(isset($_POST['add_unit'])){ $i=upload($_FILES['photo']); $pdo->prepare("INSERT INTO units (property_id,unit_name,type,yearly_price,elec_meter_no,water_meter_no,status,photo)VALUES(?,?,?,?,?,?,?,?)")->execute([$_POST['pid'],$_POST['name'],$_POST['type'],$_POST['price'],$_POST['elec'],$_POST['water'],'available',$i]); logAct("إضافة وحدة: ".$_POST['name']); header("Location: ?p=units");exit;}
    
    if(isset($_POST['add_tenant'])){ $i=upload($_FILES['id_photo']); $pdo->prepare("INSERT INTO tenants (full_name,phone,email,id_number,id_type,cr_number,address,id_photo)VALUES(?,?,?,?,?,?,?,?)")->execute([$_POST['name'],$_POST['phone'],$_POST['email'],$_POST['nid'],$_POST['id_type'],$_POST['cr'],$_POST['address'],$i]); logAct("إضافة مستأجر: ".$_POST['name']); header("Location: ?p=tenants");exit;}
    
    // CONTRACT WITH SERVICES
    if(isset($_POST['add_contract'])){
        $serv = $_POST['services'] ?: 0;
        $total = $_POST['total'] + $serv;
        $pdo->prepare("INSERT INTO contracts (tenant_id,unit_id,start_date,end_date,total_amount,services_fee,payment_cycle)VALUES(?,?,?,?,?,?,?)")->execute([$_POST['tid'],$_POST['uid'],$_POST['start'],$_POST['end'], $total, $serv, $_POST['cycle']]);
        $cid = $pdo->lastInsertId();
        $pdo->prepare("UPDATE units SET status='rented' WHERE id=?")->execute([$_POST['uid']]);
        
        // Generate Payments
        $start = new DateTime($_POST['start']); $end = new DateTime($_POST['end']);
        $cycle = $_POST['cycle'];
        $div = ($cycle=='monthly')?12:($cycle=='quarterly'?4:($cycle=='biannual'?2:1));
        $inst = $total/$div; 
        $interval = ($cycle=='monthly')?'P1M':($cycle=='quarterly'?'P3M':($cycle=='biannual'?'P6M':'P1Y'));
        
        $curr = clone $start; $i=1;
        while($curr < $end){
            $pdo->prepare("INSERT INTO payments (contract_id,title,amount,due_date,status)VALUES(?,?,?,?,?)")->execute([$cid,"دفعة #$i",$inst,$curr->format('Y-m-d'),'pending']);
            $curr->add(new DateInterval($interval)); $i++; if($cycle=='yearly'&&$i>1)break;
        }
        logAct("إنشاء عقد جديد #$cid");
        header("Location: ?p=contract_view&id=$cid"); exit;
    }
}

$p = $_GET['p'] ?? 'dashboard';
$me = $pdo->query("SELECT * FROM users WHERE id=".$_SESSION['uid'])->fetch();
$comp = getSet('company_name');
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
        /* GEMINI ULTIMATE PRO MAX THEME */
        :root { --bg:#050505; --card:#111; --border:#222; --primary:#6366f1; --text:#fff; --muted:#9ca3af; --green:#10b981; --red:#ef4444; }
        body { font-family:'Tajawal'; background:var(--bg); color:var(--text); margin:0; display:flex; height:100vh; overflow:hidden; }
        
        .sidebar { width:260px; background:#0a0a0a; border-left:1px solid var(--border); display:flex; flex-direction:column; padding:20px; z-index:10; }
        .nav-link { display:flex; align-items:center; gap:12px; padding:14px; margin-bottom:5px; border-radius:10px; color:var(--muted); text-decoration:none; font-weight:500; transition:0.3s; }
        .nav-link:hover, .nav-link.active { background:rgba(99,102,241,0.1); color:#fff; border-right:3px solid var(--primary); }
        .nav-link i { width:20px; text-align:center; }

        .main { flex:1; padding:30px; overflow-y:auto; background:radial-gradient(circle at top left, #1e1b4b, transparent 40%); }
        .header { display:flex; justify-content:space-between; margin-bottom:30px; align-items:center; }
        
        .card { background:rgba(20,20,20,0.7); backdrop-filter:blur(10px); border:1px solid var(--border); border-radius:20px; padding:25px; margin-bottom:20px; }
        .stats-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:20px; margin-bottom:20px; }
        .stat-box { background:#0f0f0f; padding:20px; border-radius:15px; border:1px solid var(--border); text-align:center; }
        
        table { width:100%; border-collapse:separate; border-spacing:0 8px; }
        th { text-align:right; padding:15px; color:var(--muted); font-size:13px; }
        td { background:#161616; padding:15px; border-top:1px solid var(--border); border-bottom:1px solid var(--border); font-size:14px; }
        td:first-child { border-radius:0 10px 10px 0; border-right:1px solid var(--border); }
        td:last-child { border-left:1px solid var(--border); border-radius:10px 0 0 10px; }

        .btn { padding:10px 20px; border-radius:10px; border:none; color:#fff; cursor:pointer; font-family:inherit; font-weight:bold; text-decoration:none; display:inline-flex; align-items:center; gap:5px; font-size:13px; }
        .btn-primary { background:linear-gradient(135deg, #6366f1, #8b5cf6); }
        .btn-green { background:linear-gradient(135deg, #10b981, #059669); }
        .btn-red { background:rgba(239,68,68,0.2); color:#f87171; border:1px solid rgba(239,68,68,0.3); }
        
        .badge { padding:5px 10px; border-radius:15px; font-size:11px; font-weight:bold; }
        .badge.paid { background:rgba(16,185,129,0.15); color:#34d399; }
        .badge.late { background:rgba(239,68,68,0.15); color:#f87171; }

        /* SETTINGS GRID FROM SCREENSHOTS */
        .settings-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(300px, 1fr)); gap:20px; }
        .set-card { border-radius:15px; overflow:hidden; border:1px solid var(--border); background:#111; }
        .set-head { padding:15px; font-weight:bold; color:#fff; }
        .set-body { padding:20px; }
        .head-blue { background:#4f46e5; } .head-green { background:#10b981; } .head-orange { background:#f59e0b; } .head-red { background:#ef4444; } .head-purple { background:#8b5cf6; }

        /* MODALS */
        .modal { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.9); z-index:999; justify-content:center; align-items:center; }
        .modal-content { background:#151515; width:700px; padding:40px; border-radius:20px; border:1px solid #333; max-height:90vh; overflow-y:auto; position:relative; }
        .inp { width:100%; padding:12px; background:#050505; border:1px solid #333; border-radius:10px; color:#fff; outline:none; margin-bottom:10px; box-sizing:border-box; font-family:inherit; }
        .inp:focus { border-color:var(--primary); }
        .inp-grid { display:grid; grid-template-columns:1fr 1fr; gap:15px; }
        label { display:block; margin-bottom:5px; color:#aaa; font-size:13px; }
    </style>
</head>
<body>

<div class="sidebar">
    <div style="text-align:center; margin-bottom:30px">
        <img src="<?= getSet('logo')?:'logo.png' ?>" style="width:70px; border-radius:50%; background:#fff; padding:5px; box-shadow:0 0 20px rgba(99,102,241,0.3)">
        <h4 style="margin:10px 0 0"><?= $comp ?></h4>
    </div>
    <a href="?p=dashboard" class="nav-link <?= $p=='dashboard'?'active':'' ?>"><i class="fa-solid fa-chart-pie"></i> لوحة التحكم</a>
    <a href="?p=properties" class="nav-link <?= $p=='properties'?'active':'' ?>"><i class="fa-solid fa-city"></i> العقارات</a>
    <a href="?p=units" class="nav-link <?= $p=='units'?'active':'' ?>"><i class="fa-solid fa-door-open"></i> الوحدات</a>
    <a href="?p=contracts" class="nav-link <?= $p=='contracts'?'active':'' ?>"><i class="fa-solid fa-file-contract"></i> العقود الإيجارية</a>
    <a href="?p=tenants" class="nav-link <?= $p=='tenants'?'active':'' ?>"><i class="fa-solid fa-users"></i> المستأجرين</a>
    <a href="?p=alerts" class="nav-link <?= $p=='alerts'?'active':'' ?>"><i class="fa-solid fa-bell"></i> التنبيهات</a>
    <div style="height:1px; background:#222; margin:10px 0"></div>
    <a href="?p=settings" class="nav-link <?= $p=='settings'?'active':'' ?>"><i class="fa-solid fa-gear"></i> الإعدادات</a>
    <a href="logout.php" class="nav-link" style="margin-top:auto; color:#ef4444"><i class="fa-solid fa-power-off"></i> خروج</a>
</div>

<div class="main">
    <div class="header">
        <h2><?= $p=='dashboard'?'لوحة التحكم':($p=='settings'?'إعدادات النظام':'إدارة '.ucfirst($p)) ?></h2>
        <div style="display:flex; gap:15px; align-items:center">
            <button onclick="openM('addM')" class="btn btn-primary"><i class="fa-solid fa-plus"></i> إضافة جديد</button>
            <div style="background:#111; padding:8px 15px; border-radius:20px; border:1px solid #333"><i class="fa-solid fa-user"></i> <?= $me['full_name'] ?></div>
        </div>
    </div>

    <?php if($p == 'dashboard'): 
        $income = $pdo->query("SELECT SUM(paid_amount) FROM payments")->fetchColumn() ?: 0;
        $total_con = $pdo->query("SELECT SUM(total_amount) FROM contracts")->fetchColumn() ?: 0;
        $late_count = $pdo->query("SELECT count(*) FROM payments WHERE status!='paid' AND due_date < CURDATE()")->fetchColumn();
    ?>
    <div class="stats-grid">
        <div class="stat-box" style="border-bottom:3px solid #6366f1">
            <h2 style="color:#6366f1; margin:0"><?= number_format($total_con) ?></h2><small>قيمة العقود</small>
        </div>
        <div class="stat-box" style="border-bottom:3px solid #10b981">
            <h2 style="color:#10b981; margin:0"><?= number_format($income) ?></h2><small>المحصل الفعلي</small>
        </div>
        <div class="stat-box" style="border-bottom:3px solid #ef4444">
            <h2 style="color:#ef4444; margin:0"><?= $late_count ?></h2><small>دفعات متأخرة</small>
        </div>
        <div class="stat-box" style="border-bottom:3px solid #f59e0b">
            <h2 style="color:#f59e0b; margin:0"><?= $pdo->query("SELECT count(*) FROM contracts WHERE end_date < DATE_ADD(CURDATE(), INTERVAL 30 DAY)")->fetchColumn() ?></h2><small>عقود تنتهي قريباً</small>
        </div>
    </div>
    
    <div class="inp-grid">
        <div class="card">
            <h3><i class="fa-solid fa-clock-rotate-left"></i> آخر النشاطات</h3>
            <?php $logs=$pdo->query("SELECT * FROM activity_log ORDER BY id DESC LIMIT 5"); while($l=$logs->fetch()): ?>
            <div style="padding:10px; border-bottom:1px solid #222; font-size:14px">
                <span style="color:var(--primary)">●</span> <?= $l['description'] ?> <span style="float:left; color:#666; font-size:11px"><?= substr($l['created_at'],5,11) ?></span>
            </div>
            <?php endwhile; ?>
        </div>
        <div class="card">
            <h3><i class="fa-solid fa-calendar-check"></i> دفعات قادمة (30 يوم)</h3>
            <?php $upc=$pdo->query("SELECT * FROM payments WHERE status!='paid' AND due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) LIMIT 5"); 
            if($upc->rowCount()==0) echo "<div style='text-align:center; padding:20px; color:#666'>لا توجد دفعات قادمة</div>";
            while($up=$upc->fetch()): ?>
            <div style="padding:10px; border-bottom:1px solid #222; display:flex; justify-content:space-between">
                <span><?= $up['title'] ?></span><span style="color:#10b981"><?= number_format($up['amount']) ?> SAR</span>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if($p == 'settings'): ?>
    <form method="POST" enctype="multipart/form-data" class="settings-grid">
        <input type="hidden" name="save_settings" value="1">
        
        <div class="set-card">
            <div class="set-head head-blue"><i class="fa-solid fa-building"></i> معلومات الشركة</div>
            <div class="set-body">
                <label>اسم الشركة</label><input type="text" name="set[company_name]" value="<?= getSet('company_name') ?>" class="inp">
                <label>الهاتف</label><input type="text" name="set[company_phone]" value="<?= getSet('company_phone') ?>" class="inp">
                <label>تغيير الشعار</label><input type="file" name="logo" class="inp">
            </div>
        </div>

        <div class="set-card">
            <div class="set-head head-green"><i class="fa-solid fa-percent"></i> إعدادات الضريبة</div>
            <div class="set-body">
                <div style="display:flex; justify-content:space-between; margin-bottom:10px">
                    <label>تفعيل الضريبة</label>
                    <input type="checkbox" name="set[vat_enabled]" <?= getSet('vat_enabled')?'checked':'' ?>>
                </div>
                <label>الرقم الضريبي</label><input type="text" name="set[vat_no]" value="<?= getSet('vat_no') ?>" class="inp">
                <label>السجل التجاري</label><input type="text" name="set[cr_no]" value="<?= getSet('cr_no') ?>" class="inp">
                <label>نسبة الضريبة %</label><input type="number" name="set[vat_percent]" value="<?= getSet('vat_percent') ?>" class="inp">
            </div>
        </div>

        <div class="set-card">
            <div class="set-head head-red"><i class="fa-solid fa-bell"></i> إعدادات التنبيهات</div>
            <div class="set-body">
                <label>تنبيه قبل انتهاء العقد (يوم)</label><input type="number" name="set[alert_before]" value="<?= getSet('alert_before') ?>" class="inp">
            </div>
        </div>

        <div class="set-card">
            <div class="set-head head-purple"><i class="fa-solid fa-file-invoice"></i> إعدادات الفواتير</div>
            <div class="set-body">
                <label>شروط الفاتورة</label><textarea name="set[invoice_terms]" class="inp" style="height:80px"><?= getSet('invoice_terms') ?></textarea>
            </div>
        </div>

        <div class="set-card">
            <div class="set-head head-orange"><i class="fa-solid fa-coins"></i> إعدادات العملة</div>
            <div class="set-body">
                <label>رمز العملة</label><input type="text" name="set[currency]" value="<?= getSet('currency') ?>" class="inp">
            </div>
        </div>

        <div style="grid-column: 1 / -1;">
            <button class="btn btn-primary" style="width:100%; padding:15px; font-size:16px">حفظ كافة الإعدادات</button>
        </div>
    </form>
    <?php endif; ?>

    <?php if($p == 'contract_view'): 
        $id = $_GET['id'];
        $c = $pdo->query("SELECT c.*, t.full_name, t.phone, u.unit_name, p.name as pname FROM contracts c JOIN tenants t ON c.tenant_id=t.id JOIN units u ON c.unit_id=u.id JOIN properties p ON u.property_id=p.id WHERE c.id=$id")->fetch();
        $paid = $pdo->query("SELECT SUM(paid_amount) FROM payments WHERE contract_id=$id")->fetchColumn() ?: 0;
        $rem = $c['total_amount'] - $paid;
    ?>
    <div class="card" style="background:linear-gradient(135deg, #6366f1, #4f46e5); color:white; border:none; display:flex; justify-content:space-between; align-items:center">
        <div>
            <h1 style="margin:0; font-size:24px"><i class="fa-solid fa-file-contract"></i> عقد إيجار #<?= $c['id'] ?></h1>
            <p style="margin:5px 0 0; opacity:0.8"><?= $c['full_name'] ?> - <?= $c['pname'] ?> (<?= $c['unit_name'] ?>)</p>
        </div>
        <div style="text-align:left">
            <div style="font-size:28px; font-weight:bold"><?= number_format($c['total_amount']) ?></div>
            <small>قيمة العقد</small>
        </div>
    </div>
    <div class="stats-grid">
        <div class="stat-box"><h3 style="color:#ef4444; margin:0"><?= number_format($rem) ?></h3><small>المتبقي</small></div>
        <div class="stat-box"><h3 style="color:#10b981; margin:0"><?= number_format($paid) ?></h3><small>المدفوع</small></div>
        <div class="stat-box"><h3 style="color:#f59e0b; margin:0"><?= number_format($c['services_fee']) ?></h3><small>الخدمات</small></div>
        <div class="stat-box"><h3 style="margin:0"><?= number_format($c['total_amount']/12) ?></h3><small>القيمة الشهرية</small></div>
    </div>
    
    <div class="card">
        <h3>جدول الدفعات</h3>
        <table>
            <thead><tr><th>#</th><th>الاستحقاق</th><th>المبلغ</th><th>المدفوع</th><th>الحالة</th><th>الإجراء</th></tr></thead>
            <tbody>
                <?php $pays=$pdo->query("SELECT * FROM payments WHERE contract_id=$id"); while($r=$pays->fetch()): ?>
                <tr>
                    <td><?= $r['title'] ?></td><td><?= $r['due_date'] ?></td><td><?= number_format($r['amount']) ?></td><td style="color:#10b981"><?= number_format($r['paid_amount']) ?></td>
                    <td><span class="badge <?= $r['status']=='paid'?'paid':'late' ?>"><?= $r['status'] ?></span></td>
                    <td><?php if($r['status']!='paid'): ?><button onclick="openPay(<?=$r['id']?>, <?=$r['amount']-$r['paid_amount']?>)" class="btn btn-green" style="padding:5px 10px; font-size:12px">سداد</button><?php endif; ?></td>
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
                $late = $pdo->query("SELECT p.*, c.id as cid, t.full_name, t.phone FROM payments p JOIN contracts c ON p.contract_id=c.id JOIN tenants t ON c.tenant_id=t.id WHERE p.status != 'paid' AND p.due_date < CURDATE()");
                while($r=$late->fetch()):
                    $days = floor((time() - strtotime($r['due_date']))/(60*60*24));
                ?>
                <tr>
                    <td><b><?= $r['full_name'] ?></b></td>
                    <td><a href="?p=contract_view&id=<?= $r['cid'] ?>" class="btn" style="padding:2px 8px; font-size:11px">#<?= $r['cid'] ?></a></td>
                    <td style="color:#ef4444; font-weight:bold"><?= number_format($r['amount']-$r['paid_amount']) ?></td>
                    <td><?= $r['due_date'] ?></td>
                    <td><span class="badge late"><?= $days ?> يوم</span></td>
                    <td><a href="https://wa.me/<?= $r['phone'] ?>" target="_blank" class="btn btn-green"><i class="fa-brands fa-whatsapp"></i></a></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <?php if($p == 'tenant_view'): 
        $id = $_GET['id'];
        $t = $pdo->query("SELECT * FROM tenants WHERE id=$id")->fetch();
    ?>
    <div class="card" style="background:linear-gradient(135deg, #10b981, #059669); color:white; border:none; display:flex; gap:20px; align-items:center; padding:30px">
        <img src="<?= $t['personal_photo']?:'logo.png' ?>" style="width:100px; height:100px; border-radius:50%; background:white; border:3px solid white">
        <div style="flex:1">
            <h1 style="margin:0"><?= $t['full_name'] ?></h1>
            <p style="margin:5px 0; opacity:0.9"><i class="fa-solid fa-phone"></i> <?= $t['phone'] ?> | <?= $t['id_number'] ?></p>
            <p style="margin:0; font-size:13px; opacity:0.8"><?= $t['address'] ?></p>
        </div>
        <div style="text-align:center">
            <a href="mailto:<?= $t['email'] ?>" class="btn" style="background:rgba(255,255,255,0.2)">مراسلة</a>
        </div>
    </div>
    
    <div class="card">
        <h3>سجل العقود</h3>
        <table>
            <thead><tr><th>رقم العقد</th><th>الوحدة</th><th>تاريخ البدء</th><th>القيمة</th><th>عرض</th></tr></thead>
            <tbody>
                <?php $cons=$pdo->query("SELECT c.*, u.unit_name FROM contracts c JOIN units u ON c.unit_id=u.id WHERE c.tenant_id=$id"); while($r=$cons->fetch()): ?>
                <tr><td>#<?= $r['id'] ?></td><td><?= $r['unit_name'] ?></td><td><?= $r['start_date'] ?></td><td><?= number_format($r['total_amount']) ?></td><td><a href="?p=contract_view&id=<?= $r['id'] ?>" class="btn btn-primary" style="padding:5px 10px; font-size:12px">عرض</a></td></tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <?php if(in_array($p, ['contracts','units','properties','tenants'])): ?>
    <div class="card">
        <table id="dataTable">
            <?php if($p=='contracts'): ?>
                <thead><tr><th>#</th><th>المستأجر</th><th>القيمة</th><th>الحالة</th><th>إجراءات</th></tr></thead>
                <tbody><?php $q=$pdo->query("SELECT c.*, t.full_name FROM contracts c JOIN tenants t ON c.tenant_id=t.id"); while($r=$q->fetch()): ?>
                <tr><td>#<?= $r['id'] ?></td><td><?= $r['full_name'] ?></td><td><?= number_format($r['total_amount']) ?></td><td>نشط</td>
                <td><a href="?p=contract_view&id=<?= $r['id'] ?>" class="btn btn-primary" style="padding:5px 10px"><i class="fa-solid fa-eye"></i></a> <form method="POST" style="display:inline" onsubmit="return confirm('حذف؟')"><input type="hidden" name="delete_item" value="1"><input type="hidden" name="table" value="contracts"><input type="hidden" name="id" value="<?=$r['id']?>"><button class="btn btn-red" style="padding:5px 10px"><i class="fa-solid fa-trash"></i></button></form></td></tr><?php endwhile; ?></tbody>
            <?php elseif($p=='tenants'): ?>
                <thead><tr><th>الاسم</th><th>الجوال</th><th>الهوية</th><th>ملف</th></tr></thead><tbody><?php $q=$pdo->query("SELECT * FROM tenants"); while($r=$q->fetch()): ?><tr><td><?= $r['full_name'] ?></td><td><?= $r['phone'] ?></td><td><?= $r['id_number'] ?></td><td><a href="?p=tenant_view&id=<?= $r['id'] ?>" class="btn btn-primary">الملف</a> <form method="POST" style="display:inline" onsubmit="return confirm('حذف؟')"><input type="hidden" name="delete_item" value="1"><input type="hidden" name="table" value="tenants"><input type="hidden" name="id" value="<?=$r['id']?>"><button class="btn btn-red"><i class="fa-solid fa-trash"></i></button></form></td></tr><?php endwhile; ?></tbody>
            <?php elseif($p=='units'): ?>
                <thead><tr><th>الوحدة</th><th>النوع</th><th>السعر</th><th>الحالة</th><th>حذف</th></tr></thead><tbody><?php $q=$pdo->query("SELECT * FROM units"); while($r=$q->fetch()): ?><tr><td><?= $r['unit_name'] ?></td><td><?= $r['type'] ?></td><td><?= number_format($r['yearly_price']) ?></td><td><span class="badge <?= $r['status']=='rented'?'late':'paid' ?>"><?= $r['status'] ?></span></td><td><form method="POST" onsubmit="return confirm('حذف؟')"><input type="hidden" name="delete_item" value="1"><input type="hidden" name="table" value="units"><input type="hidden" name="id" value="<?=$r['id']?>"><button class="btn btn-red"><i class="fa-solid fa-trash"></i></button></form></td></tr><?php endwhile; ?></tbody>
            <?php elseif($p=='properties'): ?>
                <thead><tr><th>الاسم</th><th>العنوان</th><th>المدير</th><th>حذف</th></tr></thead><tbody><?php $q=$pdo->query("SELECT * FROM properties"); while($r=$q->fetch()): ?><tr><td><?= $r['name'] ?></td><td><?= $r['address'] ?></td><td><?= $r['manager_name'] ?></td><td><form method="POST" onsubmit="return confirm('حذف؟')"><input type="hidden" name="delete_item" value="1"><input type="hidden" name="table" value="properties"><input type="hidden" name="id" value="<?=$r['id']?>"><button class="btn btn-red"><i class="fa-solid fa-trash"></i></button></form></td></tr><?php endwhile; ?></tbody>
            <?php endif; ?>
        </table>
    </div>
    <?php endif; ?>

</div>

<div id="addM" class="modal"><div class="modal-content"><span onclick="closeM('addM')" style="float:left;cursor:pointer;color:red;font-size:20px">✕</span>
<h2>إضافة جديد</h2>
<?php if($p=='contracts'): ?>
    <form method="POST"><input type="hidden" name="add_contract" value="1">
    <div class="inp-grid">
        <div class="inp-group"><label>المستأجر</label><select name="tid" class="inp"><?php $ts=$pdo->query("SELECT * FROM tenants"); foreach($ts as $t) echo "<option value='{$t['id']}'>{$t['full_name']}</option>"; ?></select></div>
        <div class="inp-group"><label>الوحدة</label><select name="uid" class="inp"><?php $us=$pdo->query("SELECT * FROM units WHERE status='available'"); foreach($us as $u) echo "<option value='{$u['id']}'>{$u['unit_name']}</option>"; ?></select></div>
        <div class="inp-group"><label>من</label><input type="date" name="start" class="inp"></div>
        <div class="inp-group"><label>إلى</label><input type="date" name="end" class="inp"></div>
        <div class="inp-group"><label>القيمة السنوية</label><input type="number" name="total" class="inp"></div>
        <div class="inp-group"><label>رسوم خدمات إضافية</label><input type="number" name="services" class="inp" value="0"></div>
        <div class="inp-group"><label>الدفعات</label><select name="cycle" class="inp"><option value="monthly">شهري</option><option value="quarterly">ربع سنوي</option><option value="yearly">سنوي</option></select></div>
    </div><button class="btn btn-primary" style="width:100%;margin-top:15px">حفظ العقد</button></form>
<?php elseif($p=='tenants'): ?>
    <form method="POST" enctype="multipart/form-data"><input type="hidden" name="add_tenant" value="1">
    <div class="inp-grid">
        <input type="text" name="name" placeholder="الاسم الكامل *" class="inp" required>
        <input type="text" name="phone" placeholder="الجوال *" class="inp" required>
        <input type="email" name="email" placeholder="البريد الإلكتروني" class="inp">
        <input type="text" name="nid" placeholder="رقم الهوية" class="inp">
        <input type="text" name="cr" placeholder="السجل التجاري (إن وجد)" class="inp">
        <input type="text" name="address" placeholder="العنوان الوطني" class="inp">
        <div style="grid-column: span 2"><label>صورة الهوية</label><input type="file" name="id_photo" class="inp"></div>
    </div><button class="btn btn-primary" style="width:100%;margin-top:15px">حفظ المستأجر</button></form>
<?php elseif($p=='units'): ?>
    <form method="POST" enctype="multipart/form-data"><input type="hidden" name="add_unit" value="1">
    <div class="inp-grid">
        <div style="grid-column:span 2"><label>العقار</label><select name="pid" class="inp"><?php $ps=$pdo->query("SELECT * FROM properties"); foreach($ps as $p) echo "<option value='{$p['id']}'>{$p['name']}</option>"; ?></select></div>
        <input type="text" name="name" placeholder="اسم الوحدة" class="inp">
        <input type="number" name="price" placeholder="السعر السنوي" class="inp">
        <input type="text" name="elec" placeholder="عداد كهرباء" class="inp">
        <input type="text" name="water" placeholder="عداد ماء" class="inp">
    </div><button class="btn btn-primary" style="width:100%;margin-top:15px">حفظ الوحدة</button></form>
<?php elseif($p=='properties'): ?>
    <form method="POST" enctype="multipart/form-data"><input type="hidden" name="add_prop" value="1">
    <div class="inp-grid">
        <input type="text" name="name" placeholder="اسم العقار" class="inp">
        <input type="text" name="type" placeholder="النوع (عمارة/مجمع)" class="inp">
        <input type="text" name="address" placeholder="العنوان" class="inp">
        <input type="text" name="manager" placeholder="اسم المسؤول" class="inp">
        <input type="text" name="phone" placeholder="هاتف المسؤول" class="inp">
        <div style="grid-column: span 2"><label>صورة العقار</label><input type="file" name="photo" class="inp"></div>
    </div><button class="btn btn-primary" style="width:100%;margin-top:15px">حفظ العقار</button></form>
<?php endif; ?>
</div></div>

<div id="payM" class="modal"><div class="modal-content"><span onclick="closeM('payM')" style="float:left;cursor:pointer;color:red;font-size:20px">✕</span><h2>سداد دفعة</h2><form method="POST"><input type="hidden" name="pay_installment" value="1"><input type="hidden" name="pay_id" id="pid"><label>المبلغ</label><input type="number" name="amount" id="pamt" class="inp"><label>طريقة الدفع</label><select name="method" class="inp"><option>كاش</option><option>تحويل</option></select><button class="btn btn-green" style="width:100%">تأكيد</button></form></div></div>

<script>
    function openM(id){document.getElementById(id).style.display='flex'}
    function closeM(id){document.getElementById(id).style.display='none'}
    function openPay(id,amt){document.getElementById('pid').value=id;document.getElementById('pamt').value=amt;openM('payM');}
    window.onclick=function(e){if(e.target.classList.contains('modal'))e.target.style.display='none'}
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
