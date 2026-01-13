<?php
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
        <button onclick="document.getElementById('propModal').style.display='flex'" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i> إضافة عقار جديد
        </button>
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

<div id="propModal" class="modal">
    <div class="modal-content">
        <div class="close-icon" onclick="document.getElementById('propModal').style.display='none'"><i class="fa-solid fa-xmark"></i></div>
        <div class="modal-header"><div class="modal-title">إضافة عقار جديد</div></div>
        
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="add_prop" value="1">
            
            <div class="inp-group">
                <label class="inp-label">اسم العقار</label>
                <input type="text" name="name" class="inp" placeholder="مثال: عمارة النخيل" required>
            </div>
            
            <div class="inp-group">
                <label class="inp-label">العنوان</label>
                <input type="text" name="address" class="inp" placeholder="المدينة، الحي، الشارع">
            </div>

            <div class="inp-grid">
                <div><label class="inp-label">مدير العقار</label><input type="text" name="manager" class="inp"></div>
                <div><label class="inp-label">رقم التواصل</label><input type="text" name="phone" class="inp"></div>
            </div>

            <button class="btn btn-primary" style="width:100%; justify-content:center; margin-top:10px">
                <i class="fa-solid fa-check"></i> حفظ العقار
            </button>
        </form>
    </div>
</div>
