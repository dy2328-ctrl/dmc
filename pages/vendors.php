<?php
if(isset($_POST['add_v'])){
    check_csrf();
    $pdo->prepare("INSERT INTO vendors (name, service_type, phone) VALUES (?,?,?)")->execute([$_POST['name'], $_POST['type'], $_POST['phone']]);
    echo "<script>window.location='index.php?p=vendors';</script>";
}
?>
<div class="card">
    <div style="display:flex; justify-content:space-between; margin-bottom:20px">
        <h3>👷 المقاولين ومزودي الخدمة</h3>
        <button onclick="document.getElementById('venModal').style.display='flex'" class="btn"><i class="fa-solid fa-plus"></i> إضافة مقاول</button>
    </div>
    <table>
        <thead><tr><th>الاسم</th><th>نوع الخدمة</th><th>الجوال</th><th>الرصيد</th></tr></thead>
        <tbody>
            <?php $vs=$pdo->query("SELECT * FROM vendors"); while($v=$vs->fetch()): ?>
            <tr>
                <td><?= $v['name'] ?></td>
                <td><?= $v['service_type'] ?></td>
                <td><?= $v['phone'] ?></td>
                <td><?= number_format($v['balance']??0) ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<div id="venModal" class="modal">
    <div class="modal-content">
        <span onclick="this.parentElement.parentElement.style.display='none'" style="cursor:pointer; color:red; position:absolute; left:20px">✕</span>
        <h3>إضافة مقاول</h3>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="add_v" value="1">
            <input type="text" name="name" class="inp" placeholder="الاسم">
            <input type="text" name="type" class="inp" placeholder="نوع الخدمة (سباكة، كهرباء...)">
            <input type="text" name="phone" class="inp" placeholder="الجوال">
            <button class="btn" style="width:100%">حفظ</button>
        </form>
    </div>
</div>
