<?php
// معالجة الإضافة
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_prop'])) {
    check_csrf();
    $stmt = $pdo->prepare("INSERT INTO properties (name, manager, phone, address) VALUES (?,?,?,?)");
    $stmt->execute([$_POST['name'], $_POST['manager'], $_POST['phone'], $_POST['address']]);
    echo "<script>window.location='index.php?p=properties';</script>";
}
?>
<div class="card">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px">
        <h3>🏙️ إدارة العقارات</h3>
        <button onclick="document.getElementById('addPropModal').style.display='flex'" class="btn"><i class="fa-solid fa-plus"></i> إضافة عقار</button>
    </div>
    <table>
        <thead><tr><th>اسم العقار</th><th>العنوان</th><th>المدير</th><th>الجوال</th><th>إجراء</th></tr></thead>
        <tbody>
            <?php 
            $props = $pdo->query("SELECT * FROM properties ORDER BY id DESC");
            while($r = $props->fetch()): ?>
            <tr>
                <td style="font-weight:bold; color:white"><?= $r['name'] ?></td>
                <td><i class="fa-solid fa-location-dot" style="color:#6366f1"></i> <?= $r['address'] ?></td>
                <td><?= $r['manager'] ?></td>
                <td><?= $r['phone'] ?></td>
                <td><button class="btn" style="padding:5px 15px; font-size:12px; background:#222">تعديل</button></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<div id="addPropModal" class="modal">
    <div class="modal-content">
        <button class="btn-close" style="position:absolute; left:20px; top:20px; background:none; border:none; color:red; cursor:pointer" onclick="this.parentElement.parentElement.style.display='none'">✕</button>
        <h2>إضافة عقار جديد</h2>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="add_prop" value="1">
            <label>اسم العقار</label><input type="text" name="name" class="inp" required>
            <label>العنوان</label><input type="text" name="address" class="inp">
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px">
                <div><label>اسم المدير</label><input type="text" name="manager" class="inp"></div>
                <div><label>رقم الجوال</label><input type="text" name="phone" class="inp"></div>
            </div>
            <button class="btn" style="width:100%">حفظ البيانات</button>
        </form>
    </div>
</div>
