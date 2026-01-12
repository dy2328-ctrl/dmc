<?php
$income = $pdo->query("SELECT SUM(amount) FROM payments")->fetchColumn() ?: 0;
$total_con = $pdo->query("SELECT SUM(total_amount) FROM contracts")->fetchColumn() ?: 0;
$expense = 0; // يمكن ربطه بجدول الصيانة لاحقاً
$units_rented = $pdo->query("SELECT count(*) FROM units WHERE status='rented'")->fetchColumn();
$units_total = $pdo->query("SELECT count(*) FROM units")->fetchColumn() ?: 1;
?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-val" style="color:#10b981"><?= number_format($income) ?></div>
        <div class="stat-label">إجمالي التحصيل</div>
        <i class="fa-solid fa-wallet" style="margin-top:10px; font-size:24px; color:#10b981"></i>
    </div>
    <div class="stat-card">
        <div class="stat-val" style="color:#6366f1"><?= number_format($total_con) ?></div>
        <div class="stat-label">قيمة العقود</div>
        <i class="fa-solid fa-file-invoice-dollar" style="margin-top:10px; font-size:24px; color:#6366f1"></i>
    </div>
    <div class="stat-card">
        <div class="stat-val" style="color:#ef4444"><?= number_format($expense) ?></div>
        <div class="stat-label">المصروفات</div>
        <i class="fa-solid fa-tools" style="margin-top:10px; font-size:24px; color:#ef4444"></i>
    </div>
    <div class="stat-card">
        <div class="stat-val"><?= $units_rented ?> / <?= $units_total ?></div>
        <div class="stat-label">الوحدات المؤجرة</div>
        <i class="fa-solid fa-building" style="margin-top:10px; font-size:24px; color:white"></i>
    </div>
</div>

<div class="stats-grid" style="grid-template-columns: 2fr 1fr;">
    <div class="card">
        <h3>الأداء المالي</h3>
        <canvas id="financeChart" height="100"></canvas>
    </div>
    <div class="card">
        <h3>نسب الإشغال</h3>
        <canvas id="occupancyChart" height="200"></canvas>
    </div>
</div>

<script>
    new Chart(document.getElementById('financeChart'), {
        type:'bar',
        data:{
            labels:['قيمة العقود','المحصل','المصروفات'],
            datasets:[{
                label:'ريال',
                data:[<?=$total_con?>,<?=$income?>,<?=$expense?>],
                backgroundColor:['#6366f1','#10b981','#ef4444'],
                borderRadius:10
            }]
        },
        options:{
            scales:{y:{grid:{color:'#333'}},x:{grid:{display:false}}},
            plugins:{legend:{display:false}}
        }
    });
    
    new Chart(document.getElementById('occupancyChart'), {
        type:'doughnut',
        data:{
            labels:['مؤجر','شاغر'],
            datasets:[{
                data:[<?=$units_rented?>,<?=$units_total-$units_rented?>],
                backgroundColor:['#10b981','#222'],
                borderWidth:0
            }]
        },
        options:{cutout:'70%'}
    });
</script>
