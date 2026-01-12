<?php
$income = $pdo->query("SELECT SUM(amount) FROM payments WHERE status='paid'")->fetchColumn() ?: 0;
$pending = $pdo->query("SELECT SUM(amount) FROM payments WHERE status!='paid'")->fetchColumn() ?: 0;
$con_count = $pdo->query("SELECT count(*) FROM contracts WHERE status='active'")->fetchColumn();
$occ_rate = $pdo->query("SELECT (count(*)/(SELECT count(*) FROM units))*100 FROM units WHERE status='rented'")->fetchColumn() ?: 0;
?>

<div class="card" style="background:linear-gradient(135deg, rgba(99,102,241,0.1), rgba(168,85,247,0.05)); border-color:var(--primary)">
    <div style="display:flex; align-items:center; gap:20px">
        <div style="width:60px; height:60px; background:var(--primary); border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:24px; color:white; box-shadow:0 0 20px var(--primary)">
            <i class="fa-solid fa-robot"></i>
        </div>
        <div>
            <h3 style="margin:0 0 5px; color:white">تحليل النظام الذكي</h3>
            <p style="margin:0; color:#aaa; line-height:1.6">
                الوضع المالي مستقر. نسبة الإشغال بلغت <b><?= round($occ_rate) ?>%</b> وهي نسبة ممتازة.
                لديك تحصيلات معلقة بقيمة <b style="color:#ef4444"><?= number_format($pending) ?></b> ريال تحتاج للمتابعة.
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
        <div style="width:60px; height:60px; background:rgba(239,68,68,0.1); border-radius:18px; display:flex; align-items:center; justify-content:center; color:#ef4444; font-size:24px"><i class="fa-solid fa-hand-holding-dollar"></i></div>
        <div><div style="font-size:14px; color:#888">مبالغ معلقة</div><div style="font-size:24px; font-weight:800"><?= number_format($pending) ?></div></div>
    </div>
    <div class="card" style="padding:25px; display:flex; align-items:center; gap:20px; margin:0">
        <div style="width:60px; height:60px; background:rgba(99,102,241,0.1); border-radius:18px; display:flex; align-items:center; justify-content:center; color:#6366f1; font-size:24px"><i class="fa-solid fa-file-contract"></i></div>
        <div><div style="font-size:14px; color:#888">عقود نشطة</div><div style="font-size:24px; font-weight:800"><?= $con_count ?></div></div>
    </div>
    <div class="card" style="padding:25px; display:flex; align-items:center; gap:20px; margin:0">
        <div style="width:60px; height:60px; background:rgba(245,158,11,0.1); border-radius:18px; display:flex; align-items:center; justify-content:center; color:#f59e0b; font-size:24px"><i class="fa-solid fa-chart-pie"></i></div>
        <div><div style="font-size:14px; color:#888">نسبة الإشغال</div><div style="font-size:24px; font-weight:800"><?= round($occ_rate) ?>%</div></div>
    </div>
</div>

<div style="display:grid; grid-template-columns: 2fr 1fr; gap:30px">
    <div class="card">
        <div style="display:flex; justify-content:space-between; margin-bottom:20px">
            <h3>التحليل المالي</h3>
            <span style="color:#666">آخر 6 أشهر</span>
        </div>
        <canvas id="mainChart" height="120"></canvas>
    </div>
    <div class="card">
        <h3>توزيع العقارات</h3>
        <canvas id="pieChart" height="200"></canvas>
    </div>
</div>

<script>
    new Chart(document.getElementById('mainChart'), {
        type: 'line',
        data: {
            labels: ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو'],
            datasets: [{
                label: 'الدخل',
                data: [12000, 19000, 15000, 25000, 22000, 30000],
                borderColor: '#6366f1',
                backgroundColor: 'rgba(99,102,241,0.1)',
                fill: true, tension: 0.4
            }]
        },
        options: { scales: { y: { grid: { color: '#222' } }, x: { grid: { display: false } } }, plugins: { legend: { display: false } } }
    });
    new Chart(document.getElementById('pieChart'), {
        type: 'doughnut',
        data: {
            labels: ['سكني', 'تجاري'],
            datasets: [{ data: [70, 30], backgroundColor: ['#10b981', '#333'], borderWidth: 0 }]
        },
        options: { cutout: '70%' }
    });
</script>
