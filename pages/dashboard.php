<?php
$income = $pdo->query("SELECT SUM(paid_amount) FROM payments")->fetchColumn() ?: 0;
$total_con = $pdo->query("SELECT SUM(total_amount) FROM contracts")->fetchColumn() ?: 0;
$units_count = $pdo->query("SELECT count(*) FROM units")->fetchColumn();
$rented_count = $pdo->query("SELECT count(*) FROM units WHERE status='rented'")->fetchColumn();
?>
<div class="row g-3">
    <div class="col-md-3">
        <div class="card p-3 bg-primary text-white">
            <h3><?= number_format($income) ?> ريال</h3>
            <span>إجمالي التحصيل</span>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card p-3 bg-success text-white">
            <h3><?= number_format($total_con) ?> ريال</h3>
            <span>قيمة العقود</span>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card p-3 bg-warning text-dark">
            <h3><?= $rented_count ?> / <?= $units_count ?></h3>
            <span>الوحدات المؤجرة</span>
        </div>
    </div>
</div>
