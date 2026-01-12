<?php
if(isset($_POST['add_contract'])){
    check_csrf();
    
    $start = $_POST['start'];
    $end = $_POST['end'];
    $total = $_POST['total'];
    
    // إدخال العقد (مع حساب المبلغ المتبقي افتراضياً يساوي الإجمالي)
    $stmt = $pdo->prepare("INSERT INTO contracts 
    (tenant_id, unit_id, start_date, end_date, total_amount, remaining_amount, status, payment_status) 
    VALUES (?, ?, ?, ?, ?, ?, 'active', 'unpaid')");
    
    $stmt->execute([$_POST['tid'], $_POST['uid'], $start, $end, $total, $total]);
    $cid = $pdo->lastInsertId();

    // تحديث حالة الوحدة
    $pdo->prepare("UPDATE units SET status='rented' WHERE id=?")->execute([$_POST['uid']]);

    // يمكنك إضافة منطق تقسيم الدفعات هنا في جدول payments
    
    echo "<script>window.location='index.php?p=contracts';</script>";
}
?>

<div class="card p-4">
    <div class="d-flex justify-content-between mb-3">
        <h3>📝 العقود</h3>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newContractModal">
            <i class="fa-solid fa-plus"></i> عقد جديد
        </button>
    </div>
    </div>

<div class="modal fade" id="newContractModal">
    <div class="modal-dialog"><div class="modal-content">
        <form method="POST">
            <div class="modal-header"><h5>إنشاء عقد جديد</h5></div>
            <div class="modal-body">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="add_contract" value="1">
                
                <label>المستأجر</label>
                <select name="tid" class="form-control mb-2">
                    <?php $ts=$pdo->query("SELECT * FROM tenants"); while($t=$ts->fetch()) echo "<option value='{$t['id']}'>{$t['name']}</option>"; ?>
                </select>
                
                <label>الوحدة</label>
                <select name="uid" class="form-control mb-2">
                    <?php $us=$pdo->query("SELECT * FROM units WHERE status='available'"); while($u=$us->fetch()) echo "<option value='{$u['id']}'>{$u['unit_name']} ({$u['type']})</option>"; ?>
                </select>
                
                <div class="row">
                    <div class="col"><label>تاريخ البدء</label><input type="date" name="start" class="form-control"></div>
                    <div class="col"><label>تاريخ الانتهاء</label><input type="date" name="end" class="form-control"></div>
                </div>
                
                <label class="mt-2">قيمة العقد الإجمالية</label>
                <input type="number" name="total" class="form-control" placeholder="0.00">
            </div>
            <div class="modal-footer"><button class="btn btn-primary w-100">حفظ العقد</button></div>
        </form>
    </div></div>
</div>
