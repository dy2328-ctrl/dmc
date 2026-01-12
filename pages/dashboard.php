<?php
// إحصائيات من قاعدة البيانات المرفقة
$income = $pdo->query("SELECT SUM(amount) FROM payments")->fetchColumn() ?: 0; // الجدول payments عمود amount
$total_con = $pdo->query("SELECT SUM(total_amount) FROM contracts")->fetchColumn() ?: 0;
$units_avail = $pdo->query("SELECT count(*) FROM units WHERE status='available'")->fetchColumn();
$units_rented = $pdo->query("SELECT count(*) FROM units WHERE status='rented'")->fetchColumn();
?>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card p-3 text-white bg-primary bg-gradient">
            <h4 class="mb-0"><?= number_format($income) ?> SAR</h4>
            <small>إجمالي التحصيل</small>
            <i class="fa-solid fa-wallet position-absolute end-0 bottom-0 m-3 opacity-25 fa-2x"></i>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card p-3 text-white bg-success bg-gradient">
            <h4 class="mb-0"><?= number_format($total_con) ?> SAR</h4>
            <small>قيمة العقود النشطة</small>
            <i class="fa-solid fa-file-contract position-absolute end-0 bottom-0 m-3 opacity-25 fa-2x"></i>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card p-3 text-dark bg-warning bg-gradient">
            <h4 class="mb-0"><?= $units_rented ?></h4>
            <small>وحدات مؤجرة</small>
            <i class="fa-solid fa-building-user position-absolute end-0 bottom-0 m-3 opacity-25 fa-2x"></i>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card p-3 text-white bg-danger bg-gradient">
            <h4 class="mb-0"><?= $units_avail ?></h4>
            <small>وحدات شاغرة</small>
            <i class="fa-solid fa-door-open position-absolute end-0 bottom-0 m-3 opacity-25 fa-2x"></i>
        </div>
    </div>
</div>

<div class="card p-4">
    <h5>📄 آخر العقود المضافة</h5>
    <table class="table table-sm">
        <thead><tr><th>#</th><th>الوحدة</th><th>القيمة</th><th>الحالة</th></tr></thead>
        <tbody>
            <?php
            $last_con = $pdo->query("SELECT c.*, u.unit_name 
                                     FROM contracts c 
                                     JOIN units u ON c.unit_id = u.id 
                                     ORDER BY c.id DESC LIMIT 5");
            while($row = $last_con->fetch()): ?>
            <tr>
                <td><?= $row['id'] ?></td>
                <td><?= $row['unit_name'] ?></td>
                <td><?= number_format($row['total_amount']) ?></td>
                <td><span class="badge bg-<?= $row['status']=='active'?'success':'secondary' ?>"><?= $row['status'] ?></span></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>
