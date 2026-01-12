<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_unit'])) {
    check_csrf();
    $stmt = $pdo->prepare("INSERT INTO units (property_id, unit_name, type, yearly_price, status) VALUES (?,?,?,?,'available')");
    $stmt->execute([$_POST['pid'], $_POST['name'], $_POST['type'], $_POST['price']]);
    echo "<script>window.location='index.php?p=units';</script>";
}
?>
<div class="card">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px">
        <h3>🚪 إدارة الوحدات</h3>
        <button onclick="document.getElementById('addUnitModal').style.display='flex'" class="btn"><i class="fa-solid fa-plus"></i> إضافة وحدة</button>
    </div>
    <table>
        <thead><tr><th>الوحدة</th><th>العقار التابع لها</th><th>النوع</th><th>السعر السنوي</th><th>الحالة</th></tr></thead>
        <tbody>
            <?php 
            $units = $pdo->query("SELECT u.*, p.name as pname FROM units u JOIN properties p ON u.property_id=p.id ORDER BY u.id DESC");
            while($u = $units->fetch()): ?>
            <tr>
                <td style="font-weight:bold"><?= $u['unit_name'] ?></td>
                <td><?= $u['pname'] ?></td>
                <td><span style="background:#222; padding:3px 10px; border-radius:10px; font-size:12px"><?= $u['type'] ?></span></td>
                <td><?= number_format($u['yearly_price']) ?> SAR</td>
                <td>
                    <?php if($u['status']=='rented'): ?>
                        <span style="color:#ef4444; background:rgba(239,68,68,0.1); padding:5px 10px; border-radius:10px">مؤجر</span>
                    <?php else: ?>
                        <span style="color:#10b981; background:rgba(16,185,129,0.1); padding:5px 10px; border-radius:10px">شاغر</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<div id="addUnitModal" class="modal">
    <div class="modal-content">
        <span onclick="this.parentElement.parentElement.style.display='none'" style="cursor:pointer; position:absolute; left:20px; top:20px; color:red">✕</span>
        <h2>إضافة وحدة سكنية</h2>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="add_unit" value="1">
            <label>العقار</label>
            <select name="pid" class="inp">
                <?php $ps=$pdo->query("SELECT * FROM properties"); while($p=$ps->fetch()) echo "<option value='{$p['id']}'>{$p['name']}</option>"; ?>
            </select>
            <label>رقم/اسم الوحدة</label><input type="text" name="name" class="inp" required>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px">
                <div><label>النوع</label>
                    <select name="type" class="inp">
                        <option value="apartment">شقة</option><option value="shop">محل</option><option value="villa">فيلا</option>
                    </select>
                </div>
                <div><label>السعر السنوي</label><input type="number" name="price" class="inp"></div>
            </div>
            <button class="btn" style="width:100%">حفظ الوحدة</button>
        </form>
    </div>
</div>
