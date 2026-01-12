<?php
// تأكد من وجود قيم افتراضية حتى لو كانت الجداول فارغة
$income = $pdo->query("SELECT SUM(amount) FROM payments WHERE status='paid'")->fetchColumn() ?: 0;
$pending = $pdo->query("SELECT SUM(amount) FROM payments WHERE status!='paid'")->fetchColumn() ?: 0;
$con_count = $pdo->query("SELECT count(*) FROM contracts WHERE status='active'")->fetchColumn() ?: 0;
$total_units = $pdo->query("SELECT count(*) FROM units")->fetchColumn() ?: 1;
$rented_units = $pdo->query("SELECT count(*) FROM units WHERE status='rented'")->fetchColumn() ?: 0;
$occ_rate = ($rented_units / $total_units) * 100;
?>

<div class="card" style="background:linear-gradient(135deg, rgba(99,102,241,0.15), rgba(0,0,0,0)); border-color:var(--primary)">
    <div style="display:flex; align-items:center; gap:20px">
        <div style="width:50px; height:50px; background:var(--primary); border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:20px; color:white; box-shadow:0 0 20px var(--primary)">
            <i class="fa-solid fa-robot"></i>
        </div>
        <div>
            <h3 style="margin:0 0 5px; color:white">ملخص النظام</h3>
            <p style="margin:0; color:#ccc;">
                نسبة الإشغال الحالية <b><?= round($occ_rate) ?>%</b>. 
                لديك عقود نشطة بعدد <b><?= $con_count ?></b>، وتحصيلات معلقة بقيمة <span style="color:#ef4444; font-weight:bold"><?= number_format($pending) ?></span> ريال.
            </p>
        </div>
    </div>
</div>

<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(240px, 1fr)); gap:25px; margin-bottom:30px">
    <div class="card" style="padding:25px; display:flex; align-items:center; gap:20px; margin:0">
        <div style="width:60px; height:60px; background:rgba(16,185,129,0.1); border-radius:18px; display:flex; align-items:center; justify-content:center; color:#10b981; font-size:24px"><i class="fa-solid fa-wallet"></i></div>
        <div><div style="font-size:14px; color:#888">إجمالي التحصيل</div><div style="font-size:24px; font-weight:800"><?= number_format($income) ?></div></div>
    </div>
    <div class="card" style="padding:25px; display:flex; align-items:center; gap:20px; margin:0">
        <div style="width:60px; height:60px; background:rgba(239,68,68,0.1); border-radius:18px; display:flex; align-items:center; justify-content:center; color:#ef4444; font-size:24px"><i class="fa-solid fa-file-invoice"></i></div>
        <div><div style="font-size:14px; color:#888">مبالغ معلقة</div><div style="font-size:24px; font-weight:800"><?= number_format($pending) ?></div></div>
    </div>
    <div class="card" style="padding:25px; display:flex; align-items:center; gap:20px; margin:0">
        <div style="width:60px; height:60px; background:rgba(99,102,241,0.1); border-radius:18px; display:flex; align-items:center; justify-content:center; color:#6366f1; font-size:24px"><i class="fa-solid fa-users"></i></div>
        <div><div style="font-size:14px; color:#888">عقود نشطة</div><div style="font-size:24px; font-weight:800"><?= $con_count ?></div></div>
    </div>
</div>

<div style="display:grid; grid-template-columns: 2fr 1fr; gap:30px">
    <div class="card">
        <h3>الأداء المالي</h3>
        <canvas id="chart1" height="120"></canvas>
    </div>
    <div class="card">
        <h3>حالة الوحدات</h3>
        <canvas id="chart2" height="200"></canvas>
    </div>
</div>

<script>
    new Chart(document.getElementById('chart1'), {
        type: 'line',
        data: {
            labels: ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو'],
            datasets: [{ label: 'الدخل', data: [5000, 15000, 10000, 20000, 25000, 30000], borderColor: '#6366f1', backgroundColor: 'rgba(99,102,241,0.1)', fill: true, tension: 0.4 }]
        }, options: { scales: { y: { grid: { color: '#222' } }, x: { grid: { display: false } } }, plugins: { legend: { display: false } } }
    });
    new Chart(document.getElementById('chart2'), {
        type: 'doughnut',
        data: { labels: ['مؤجر', 'شاغر'], datasets: [{ data: [<?= $rented_units ?>, <?= $total_units - $rented_units ?>], backgroundColor: ['#10b981', '#222'], borderWidth: 0 }] }, options: { cutout: '75%' }
    });
</script>
