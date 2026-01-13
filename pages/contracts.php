<?php
if(isset($_POST['add_contract'])){
    check_csrf();
    try {
        $pdo->beginTransaction();
        // إدخال العقد
        $stmt = $pdo->prepare("INSERT INTO contracts (tenant_id, unit_id, start_date, end_date, total_amount, payment_cycle, status) VALUES (?,?,?,?,?,?,'active')");
        $stmt->execute([$_POST['tid'], $_POST['uid'], $_POST['start'], $_POST['end'], $_POST['total'], $_POST['cycle']]);
        $cid = $pdo->lastInsertId();
        
        // تحديث الوحدة
        $pdo->prepare("UPDATE units SET status='rented' WHERE id=?")->execute([$_POST['uid']]);
        
        // توليد دفعة واحدة تجريبية (للتأكد)
        $pdo->prepare("INSERT INTO payments (contract_id, title, amount, due_date, status) VALUES (?,?,?,?,'pending')")
            ->execute([$cid, 'الدفعة الأولى', $_POST['total'], $_POST['start']]);
            
        $pdo->commit();
        echo "<script>window.location='index.php?p=contracts';</script>";
    } catch(Exception $e) {
        $pdo->rollBack();
        echo "<script>alert('خطأ: ".$e->getMessage()."');</script>";
    }
}
?>

<div class="card">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px">
        <h3>📄 قائمة العقود</h3>
        <button onclick="document.getElementById('addContractModal').style.display='flex'" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i> إضافة عقد جديد
        </button>
    </div>
    
    <table>
        <thead>
            <tr style="background:#1a1a1a;">
                <th>#</th><th>المستأجر</th><th>العقار</th><th>تاريخ البداية</th><th>تاريخ النهاية</th><th>القيمة</th><th>الحالة</th><th>الإجراءات</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $q = $pdo->query("SELECT c.*, t.name as tname, u.unit_name FROM contracts c JOIN tenants t ON c.tenant_id=t.id JOIN units u ON c.unit_id=u.id ORDER BY id DESC");
            while($r=$q->fetch()): ?>
            <tr>
                <td><?= $r['id'] ?></td>
                <td style="font-weight:bold"><?= $r['tname'] ?></td>
                <td><?= $r['unit_name'] ?></td>
                <td><?= $r['start_date'] ?></td>
                <td><?= $r['end_date'] ?></td>
                <td><?= number_format($r['total_amount']) ?></td>
                <td><span class="badge" style="background:rgba(16,185,129,0.2); color:#10b981">نشط</span></td>
                <td>
                    <a href="index.php?p=contract_view&id=<?= $r['id'] ?>" class="btn" style="padding:5px 10px; background:#4f46e5"><i class="fa-solid fa-eye"></i></a>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<div id="addContractModal" class="modal">
    <div class="modal-content">
        <div class="close-icon" onclick="document.getElementById('addContractModal').style.display='none'"><i class="fa-solid fa-xmark"></i></div>
        <div class="modal-header"><div class="modal-title">إنشاء عقد جديد</div></div>
        
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="add_contract" value="1">
            
            <div class="inp-grid">
                <div><label class="inp-label">المستأجر</label>
                <select name="tid" class="inp">
                    <?php $ts=$pdo->query("SELECT * FROM tenants"); while($t=$ts->fetch()) echo "<option value='{$t['id']}'>{$t['name']}</option>"; ?>
                </select></div>
                <div><label class="inp-label">الوحدة</label>
                <select name="uid" class="inp">
                    <?php $us=$pdo->query("SELECT * FROM units WHERE status='available'"); while($u=$us->fetch()) echo "<option value='{$u['id']}'>{$u['unit_name']}</option>"; ?>
                </select></div>
            </div>

            <div class="inp-grid">
                <div><label class="inp-label">تاريخ البدء</label><input type="date" name="start" class="inp" required></div>
                <div><label class="inp-label">تاريخ الانتهاء</label><input type="date" name="end" class="inp" required></div>
            </div>
            
            <div class="inp-grid">
                <div><label class="inp-label">القيمة الإجمالية</label><input type="number" name="total" class="inp" required></div>
                <div><label class="inp-label">طريقة السداد</label>
                <select name="cycle" class="inp">
                    <option value="yearly">سنوي</option>
                    <option value="biannual">نصف سنوي</option>
                    <option value="quarterly">ربع سنوي</option>
                    <option value="monthly">شهري</option>
                </select></div>
            </div>

            <button class="btn btn-primary" style="width:100%; justify-content:center; margin-top:10px">حفظ العقد</button>
        </form>
    </div>
</div>
