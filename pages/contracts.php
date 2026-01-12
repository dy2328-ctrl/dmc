<?php
if(isset($_POST['add_contract'])){
    check_csrf();
    $pdo->prepare("INSERT INTO contracts (tenant_id,unit_id,start_date,end_date,total_amount,payment_cycle)VALUES(?,?,?,?,?,?)")->execute([$_POST['tid'],$_POST['uid'],$_POST['start'],$_POST['end'],$_POST['total'],$_POST['cycle']]);
    $cid = $pdo->lastInsertId();
    $pdo->prepare("UPDATE units SET status='rented' WHERE id=?")->execute([$_POST['uid']]);
    
    // توليد الدفعات
    $start = new DateTime($_POST['start']); $end = new DateTime($_POST['end']);
    $amount = $_POST['total']; $cycle = $_POST['cycle'];
    $div = ($cycle=='monthly')?12:($cycle=='quarterly'?4:1);
    $inst = $amount / $div;
    $interval = ($cycle=='monthly')?'P1M':($cycle=='quarterly'?'P3M':'P1Y');
    
    $curr = clone $start; $i=1;
    while($curr < $end){
        $pdo->prepare("INSERT INTO payments (contract_id,title,amount,due_date)VALUES(?,?,?,?)")->execute([$cid,"دفعة #$i",$inst,$curr->format('Y-m-d')]);
        $curr->add(new DateInterval($interval)); $i++;
    }
    header("Location: index.php?p=contract_view&id=$cid"); exit;
}
?>

<div class="card p-4">
    <div class="d-flex justify-content-between mb-3">
        <h3>📄 العقود</h3>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addContractModal">إنشاء عقد</button>
    </div>
    <table class="table">
        <thead><tr><th>#</th><th>المستأجر</th><th>الوحدة</th><th>القيمة</th><th>عرض</th></tr></thead>
        <tbody>
            <?php 
            $q=$pdo->query("SELECT c.*, t.full_name, u.unit_name FROM contracts c JOIN tenants t ON c.tenant_id=t.id JOIN units u ON c.unit_id=u.id ORDER BY id DESC");
            while($r=$q->fetch()): ?>
            <tr><td><?= $r['id'] ?></td><td><?= $r['full_name'] ?></td><td><?= $r['unit_name'] ?></td><td><?= number_format($r['total_amount']) ?></td>
            <td><a href="?p=contract_view&id=<?= $r['id'] ?>" class="btn btn-sm btn-info">التفاصيل</a></td></tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<div class="modal fade" id="addContractModal">
    <div class="modal-dialog"><div class="modal-content">
        <form method="POST">
            <div class="modal-header"><h5>عقد جديد</h5></div>
            <div class="modal-body">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="add_contract" value="1">
                <label>المستأجر</label>
                <select name="tid" class="form-control mb-2">
                    <?php $ts=$pdo->query("SELECT * FROM tenants"); while($t=$ts->fetch()) echo "<option value='{$t['id']}'>{$t['full_name']}</option>"; ?>
                </select>
                <label>الوحدة</label>
                <select name="uid" class="form-control mb-2">
                    <?php $us=$pdo->query("SELECT * FROM units WHERE status='available'"); while($u=$us->fetch()) echo "<option value='{$u['id']}'>{$u['unit_name']}</option>"; ?>
                </select>
                <div class="row">
                    <div class="col"><label>من</label><input type="date" name="start" class="form-control"></div>
                    <div class="col"><label>إلى</label><input type="date" name="end" class="form-control"></div>
                </div>
                <label class="mt-2">القيمة الإجمالية</label><input type="number" name="total" class="form-control">
                <label>نظام الدفع</label>
                <select name="cycle" class="form-control"><option value="monthly">شهري</option><option value="quarterly">ربع سنوي</option><option value="yearly">سنوي</option></select>
            </div>
            <div class="modal-footer"><button class="btn btn-primary">حفظ وإنشاء</button></div>
        </form>
    </div></div>
</div>
