<?php
if(isset($_POST['add_maint'])){
    check_csrf();
    $pdo->prepare("INSERT INTO maintenance (property_id, unit_id, vendor_id, description, cost, request_date, status) VALUES (?,?,?,?,?,CURRENT_DATE,'pending')")
        ->execute([$_POST['pid'], $_POST['uid'], $_POST['vid'], $_POST['desc'], $_POST['cost']]);
    echo "<script>window.location='index.php?p=maintenance';</script>";
}
?>
<div class="card">
    <div style="display:flex; justify-content:space-between; margin-bottom:20px">
        <h3>🛠️ طلبات الصيانة</h3>
        <button onclick="document.getElementById('maintModal').style.display='flex'" class="btn"><i class="fa-solid fa-plus"></i> طلب جديد</button>
    </div>
    <table>
        <thead><tr><th>الوحدة</th><th>الوصف</th><th>المقاول</th><th>التكلفة</th><th>الحالة</th></tr></thead>
        <tbody>
            <?php 
            $qs = $pdo->query("SELECT m.*, u.unit_name, v.name as vname FROM maintenance m JOIN units u ON m.unit_id=u.id LEFT JOIN vendors v ON m.vendor_id=v.id ORDER BY id DESC");
            while($r=$qs->fetch()): ?>
            <tr>
                <td><?= $r['unit_name'] ?></td>
                <td><?= $r['description'] ?></td>
                <td><?= $r['vname'] ?></td>
                <td style="color:#ef4444; font-weight:bold"><?= number_format($r['cost']) ?></td>
                <td><span class="badge" style="background:#333; padding:5px; border-radius:5px"><?= $r['status'] ?></span></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<div id="maintModal" class="modal">
    <div class="modal-content">
        <span onclick="this.parentElement.parentElement.style.display='none'" style="cursor:pointer; color:red; position:absolute; left:20px">✕</span>
        <h3>تسجيل صيانة</h3>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="add_maint" value="1">
            <select name="pid" class="inp"><option value="">اختر العقار...</option>
                <?php $ps=$pdo->query("SELECT * FROM properties"); while($p=$ps->fetch()) echo "<option value='{$p['id']}'>{$p['name']}</option>"; ?>
            </select>
            <select name="uid" class="inp"><option value="">اختر الوحدة...</option>
                <?php $us=$pdo->query("SELECT * FROM units"); while($u=$us->fetch()) echo "<option value='{$u['id']}'>{$u['unit_name']}</option>"; ?>
            </select>
            <select name="vid" class="inp"><option value="">اختر المقاول...</option>
                <?php $vs=$pdo->query("SELECT * FROM vendors"); while($v=$vs->fetch()) echo "<option value='{$v['id']}'>{$v['name']}</option>"; ?>
            </select>
            <input type="text" name="desc" class="inp" placeholder="وصف العطل">
            <input type="number" name="cost" class="inp" placeholder="التكلفة التقديرية">
            <button class="btn" style="width:100%">حفظ</button>
        </form>
    </div>
</div>
