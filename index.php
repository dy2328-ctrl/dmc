<?php
require 'db.php';

// === المعالجة الخلفية (Backend Logic) ===

// 1. إضافة وحدة
if (isset($_POST['add_unit'])) {
    $photo = '';
    if(!empty($_FILES['photo']['tmp_name'])) {
        $data = file_get_contents($_FILES['photo']['tmp_name']);
        $photo = 'data:image/jpeg;base64,' . base64_encode($data);
    }
    $pdo->prepare("INSERT INTO units (property_id, unit_name, unit_number, floor_number, yearly_price, meter_number, photo_url) VALUES (?,?,?,?,?,?,?)")
        ->execute([1, $_POST['name'], $_POST['num'], $_POST['floor'], $_POST['price'], $_POST['meter'], $photo]);
    header("Location: ?p=units"); exit;
}

// 2. إضافة مستأجر
if (isset($_POST['add_tenant'])) {
    $pdo->prepare("INSERT INTO tenants (full_name, phone, id_number, email) VALUES (?,?,?,?)")
        ->execute([$_POST['name'], $_POST['phone'], $_POST['nid'], $_POST['email']]);
    header("Location: ?p=tenants"); exit;
}

// 3. إنشاء عقد جديد (تحديث حالة الوحدة تلقائياً)
if (isset($_POST['add_contract'])) {
    $pdo->prepare("INSERT INTO contracts (tenant_id, unit_id, start_date, end_date, total_amount, payment_cycle) VALUES (?,?,?,?,?,?)")
        ->execute([$_POST['tid'], $_POST['uid'], $_POST['start'], $_POST['end'], $_POST['total'], $_POST['cycle']]);
    $pdo->prepare("UPDATE units SET status='rented' WHERE id=?")->execute([$_POST['uid']]);
    header("Location: ?p=contracts"); exit;
}

// 4. تسجيل دفعة مالية (جديد)
if (isset($_POST['add_payment'])) {
    $pdo->prepare("INSERT INTO payments (contract_id, amount, payment_date, payment_method, note) VALUES (?,?,?,?,?)")
        ->execute([$_POST['cid'], $_POST['amount'], $_POST['date'], $_POST['method'], $_POST['note']]);
    header("Location: ?p=contracts"); exit;
}

// 5. تسجيل قراءة عداد (جديد)
if (isset($_POST['add_reading'])) {
    $pdo->prepare("INSERT INTO meter_readings (unit_id, reading_date, reading_value, notes) VALUES (?,?,?,?)")
        ->execute([$_POST['uid'], $_POST['date'], $_POST['val'], $_POST['note']]);
    header("Location: ?p=meters"); exit;
}

// === الإحصائيات الذكية ===
$p = $_GET['p'] ?? 'dashboard';

// جلب التنبيهات (عقود تنتهي خلال 30 يوم)
$alerts = $pdo->query("SELECT c.*, t.full_name, u.unit_name, DATEDIFF(c.end_date, CURRENT_DATE) as days_left 
                       FROM contracts c 
                       JOIN tenants t ON c.tenant_id=t.id 
                       JOIN units u ON c.unit_id=u.id 
                       WHERE c.status='active' AND c.end_date <= DATE_ADD(CURRENT_DATE, INTERVAL 30 DAY)")->fetchAll();

// إحصائيات عامة
$total_revenue = $pdo->query("SELECT SUM(total_amount) FROM contracts WHERE status='active'")->fetchColumn() ?: 0;
$collected = $pdo->query("SELECT SUM(amount) FROM payments")->fetchColumn() ?: 0;
$pending = $total_revenue - $collected;

?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نظام العقارات الذكي</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root { --primary: #6366f1; --dark: #1e293b; --bg: #f1f5f9; --success: #10b981; --warning: #f59e0b; --danger: #ef4444; }
        body { font-family: 'Tajawal'; background: var(--bg); margin: 0; display: flex; height: 100vh; overflow: hidden; }
        
        /* Sidebar */
        .sidebar { width: 260px; background: var(--dark); color: white; display: flex; flex-direction: column; padding: 20px; }
        .brand { font-size: 22px; font-weight: 800; margin-bottom: 40px; display: flex; align-items: center; gap: 10px; color: var(--primary); }
        .nav-item { display: flex; align-items: center; gap: 12px; padding: 14px; color: #cbd5e1; text-decoration: none; border-radius: 12px; margin-bottom: 8px; transition: 0.3s; }
        .nav-item:hover, .nav-item.active { background: var(--primary); color: white; }
        
        /* Main */
        .main { flex: 1; padding: 30px; overflow-y: auto; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .btn-main { background: var(--primary); color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; text-decoration: none; display: flex; align-items: center; gap: 8px; }
        
        /* Cards */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .card { background: white; padding: 25px; border-radius: 16px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); position: relative; overflow: hidden; }
        .card h3 { margin: 0; font-size: 14px; color: #64748b; }
        .card .val { font-size: 28px; font-weight: 800; margin-top: 10px; color: var(--dark); }
        .card .icon { position: absolute; left: 20px; top: 20px; font-size: 40px; opacity: 0.1; }

        /* Tables */
        table { width: 100%; border-collapse: separate; border-spacing: 0 8px; }
        th { text-align: right; padding: 15px; color: #64748b; font-size: 13px; }
        td { background: white; padding: 15px; border-top: 1px solid #f1f5f9; border-bottom: 1px solid #f1f5f9; }
        td:first-child { border-radius: 0 10px 10px 0; border-right: 1px solid #f1f5f9; }
        td:last-child { border-radius: 10px 0 0 10px; border-left: 1px solid #f1f5f9; }
        
        /* Alerts */
        .alert-box { background: #fee2e2; border-right: 4px solid var(--danger); padding: 15px; margin-bottom: 20px; border-radius: 8px; display: flex; align-items: center; gap: 15px; color: #991b1b; }

        /* Modal */
        .modal { display: none; position: fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); backdrop-filter: blur(4px); z-index: 100; justify-content:center; align-items:center; }
        .modal-content { background: white; padding: 30px; border-radius: 20px; width: 500px; max-width: 90%; }
        .inp { width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 8px; margin-bottom: 15px; background: #f8fafc; font-family: inherit; }

        @media (max-width: 768px) { body { flex-direction: column; } .sidebar { width: 100%; flex-direction: row; padding: 10px; overflow-x: auto; } .brand { display:none } }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="brand"><i class="fa-solid fa-building-user"></i> <span>Gemini Estate</span></div>
        <a href="?p=dashboard" class="nav-item <?= $p=='dashboard'?'active':'' ?>"><i class="fa-solid fa-chart-pie"></i> <span>الرئيسية</span></a>
        <a href="?p=units" class="nav-item <?= $p=='units'?'active':'' ?>"><i class="fa-solid fa-door-open"></i> <span>الوحدات</span></a>
        <a href="?p=contracts" class="nav-item <?= $p=='contracts'?'active':'' ?>"><i class="fa-solid fa-file-contract"></i> <span>العقود والمالية</span></a>
        <a href="?p=meters" class="nav-item <?= $p=='meters'?'active':'' ?>"><i class="fa-solid fa-bolt"></i> <span>العدادات</span></a>
        <a href="?p=tenants" class="nav-item <?= $p=='tenants'?'active':'' ?>"><i class="fa-solid fa-users"></i> <span>المستأجرين</span></a>
        <a href="logout.php" class="nav-item" style="margin-top:auto; color:#ef4444"><i class="fa-solid fa-right-from-bracket"></i> <span>خروج</span></a>
    </div>

    <div class="main">
        
        <?php if($p == 'dashboard'): ?>
            <div class="header">
                <h2>لوحة القيادة</h2>
                <button onclick="openModal('addContractModal')" class="btn-main"><i class="fa-solid fa-plus"></i> عقد جديد</button>
            </div>

            <?php if(count($alerts) > 0): ?>
                <div class="alert-box">
                    <i class="fa-solid fa-bell fa-shake"></i>
                    <div>
                        <strong>تنبيهات هامة:</strong> يوجد <?= count($alerts) ?> عقود ستنتهي قريباً. يرجى مراجعة قسم العقود.
                    </div>
                </div>
            <?php endif; ?>

            <div class="stats-grid">
                <div class="card">
                    <h3>إجمالي العقود</h3>
                    <div class="val"><?= number_format($total_revenue) ?> <small>ريال</small></div>
                    <i class="fa-solid fa-file-invoice-dollar icon" style="color:var(--primary)"></i>
                </div>
                <div class="card">
                    <h3>المحصل الفعلي</h3>
                    <div class="val" style="color:var(--success)"><?= number_format($collected) ?> <small>ريال</small></div>
                    <i class="fa-solid fa-hand-holding-dollar icon" style="color:var(--success)"></i>
                </div>
                <div class="card">
                    <h3>المستحقات المتبقية</h3>
                    <div class="val" style="color:var(--danger)"><?= number_format($pending) ?> <small>ريال</small></div>
                    <i class="fa-solid fa-piggy-bank icon" style="color:var(--danger)"></i>
                </div>
            </div>

            <div style="display:grid; grid-template-columns: 2fr 1fr; gap:20px">
                <div class="card">
                    <h3 style="margin-bottom:20px">آخر العمليات المالية</h3>
                    <table>
                        <thead><tr><th>التاريخ</th><th>المبلغ</th><th>البيان</th></tr></thead>
                        <tbody>
                            <?php $q=$pdo->query("SELECT * FROM payments ORDER BY id DESC LIMIT 5"); while($r=$q->fetch()): ?>
                            <tr>
                                <td><?= $r['payment_date'] ?></td>
                                <td style="color:var(--success); font-weight:bold">+<?= number_format($r['amount']) ?></td>
                                <td><?= $r['note'] ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <div class="card">
                    <h3>نسبة الإشغال</h3>
                    <canvas id="chart1"></canvas>
                </div>
            </div>
            <script>
                // Chart Logic
                <?php 
                   $rented = $pdo->query("SELECT count(*) FROM units WHERE status='rented'")->fetchColumn(); 
                   $avail = $pdo->query("SELECT count(*) FROM units WHERE status='available'")->fetchColumn(); 
                ?>
                new Chart(document.getElementById('chart1'), {
                    type: 'doughnut',
                    data: { labels: ['مؤجر', 'شاغر'], datasets: [{ data: [<?= $rented ?>, <?= $avail ?>], backgroundColor: ['#6366f1', '#e2e8f0'] }] },
                    options: { plugins: { legend: { position: 'bottom' } } }
                });
            </script>
        <?php endif; ?>

        <?php if($p == 'units'): ?>
            <div class="header"><h2>الوحدات العقارية</h2><button onclick="openModal('addUnitModal')" class="btn-main">إضافة وحدة</button></div>
            <div class="stats-grid">
                <?php $q = $pdo->query("SELECT * FROM units"); while($r = $q->fetch()): ?>
                <div class="card" style="padding:0">
                    <div style="height:150px; background: #eee; background-image: url('<?= $r['photo_url'] ?>'); background-size:cover;"></div>
                    <div style="padding:15px">
                        <div style="display:flex; justify-content:space-between">
                            <h4><?= $r['unit_name'] ?></h4>
                            <span style="font-size:12px; padding:2px 8px; border-radius:10px; background:<?= $r['status']=='rented'?'#fee2e2':'#dcfce7' ?>; color:<?= $r['status']=='rented'?'red':'green' ?>">
                                <?= $r['status']=='rented'?'مؤجر':'متاح' ?>
                            </span>
                        </div>
                        <p style="color:#64748b; font-size:14px"><i class="fa-solid fa-list-ol"></i> رقم: <?= $r['unit_number'] ?> | <i class="fa-solid fa-layer-group"></i> دور: <?= $r['floor_number'] ?></p>
                        <div style="margin-top:10px; font-weight:bold"><?= number_format($r['yearly_price']) ?> ريال/سنة</div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>

        <?php if($p == 'contracts'): ?>
            <div class="header"><h2>العقود والمالية</h2><button onclick="openModal('addContractModal')" class="btn-main">عقد جديد</button></div>
            
            <?php $contracts = $pdo->query("SELECT c.*, t.full_name, u.unit_name FROM contracts c JOIN tenants t ON c.tenant_id=t.id JOIN units u ON c.unit_id=u.id ORDER BY c.id DESC"); ?>
            <?php while($c = $contracts->fetch()): 
                $paid = $pdo->prepare("SELECT SUM(amount) FROM payments WHERE contract_id=?"); $paid->execute([$c['id']]); $paid_total = $paid->fetchColumn() ?: 0;
                $remain = $c['total_amount'] - $paid_total;
                $progress = ($c['total_amount'] > 0) ? ($paid_total / $c['total_amount']) * 100 : 0;
            ?>
            <div class="card" style="margin-bottom:15px; border-left: 5px solid <?= $remain > 0 ? 'var(--warning)' : 'var(--success)' ?>">
                <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap">
                    <div>
                        <h4>#<?= $c['id'] ?> - <?= $c['full_name'] ?> (<?= $c['unit_name'] ?>)</h4>
                        <small style="color:#64748b">من <?= $c['start_date'] ?> إلى <?= $c['end_date'] ?></small>
                    </div>
                    <div style="text-align:left">
                        <div style="font-weight:bold; font-size:18px"><?= number_format($c['total_amount']) ?> ريال</div>
                        <small style="color:<?= $remain > 0 ? 'orange' : 'green' ?>">المتبقي: <?= number_format($remain) ?></small>
                    </div>
                    <div>
                         <button onclick="openPaymentModal(<?= $c['id']
