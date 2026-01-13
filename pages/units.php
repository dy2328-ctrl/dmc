<?php
// معالجة إضافة وحدة جديدة
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_unit'])) {
    check_csrf();
    try {
        // التأكد من الحقول
        $stmt = $pdo->prepare("INSERT INTO units (property_id, unit_name, type, yearly_price, elec_meter_no, water_meter_no, status) VALUES (?,?,?,?,?,?, 'available')");
        $stmt->execute([
            $_POST['pid'], 
            $_POST['name'], 
            $_POST['type'], 
            $_POST['price'], 
            $_POST['elec'], 
            $_POST['water']
        ]);
        echo "<script>window.location='index.php?p=units';</script>";
    } catch(Exception $e) {
        echo "<script>alert('خطأ: لم يتم الحفظ. تأكد من البيانات.');</script>";
    }
}
?>

<div class="card">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px">
        <h3><i class="fa-solid fa-door-open" style="color:var(--primary)"></i> إدارة الوحدات السكنية</h3>
        
        <button onclick="document.getElementById('unitModal').style.display='flex'" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i> إضافة وحدة جديدة
        </button>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>اسم/رقم الوحدة</th>
                <th>العقار التابع لها</th>
                <th>النوع</th>
                <th>عداد الكهرباء</th>
                <th>السعر السنوي</th>
                <th>الحالة</th>
                <th>إجراء</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            // جلب الوحدات مع اسم العقار
            $units = $pdo->query("SELECT u.*, p.name as pname FROM units u JOIN properties p ON u.property_id=p.id ORDER BY u.id DESC");
            while($u = $units->fetch()): ?>
            <tr>
                <td style="font-weight:bold; color:white"><?= $u['unit_name'] ?></td>
                <td><i class="fa-solid fa-building" style="color:#666; font-size:12px"></i> <?= $u['pname'] ?></td>
                <td><span class="badge" style="background:#222; border:1px solid #333"><?= $u['type'] ?></span></td>
                <td style="font-family:monospace; color:#aaa"><?= $u['elec_meter_no'] ?: '-' ?></td>
                <td style="font-weight:bold"><?= number_format($u['yearly_price']) ?></td>
                <td>
                    <?php if($u['status']=='rented'): ?>
                        <span class="badge" style="background:rgba(239,68,68,0.2); color:#f87171">مؤجر</span>
                    <?php else: ?>
                        <span class="badge" style="background:rgba(16,185,129,0.2); color:#34d399">شاغر</span>
                    <?php endif; ?>
                </td>
                <td>
                    <button class="btn btn-dark" style="padding:5px 10px; font-size:12px"><i class="fa-solid fa-pen"></i></button>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<div id="unitModal" class="modal">
    <div class="modal-content">
        <div class="close-icon" onclick="document.getElementById('unitModal').style.display='none'">
            <i class="fa-solid fa-xmark"></i>
        </div>
        
        <div class="modal-header">
            <div class="modal-title">تسجيل وحدة جديدة</div>
        </div>
        
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="add_unit" value="1">
            
            <div class="inp-group">
                <label class="inp-label">العقار التابع له</label>
                <select name="pid" class="inp" required>
                    <option value="">-- اختر العقار --</option>
                    <?php $ps=$pdo->query("SELECT * FROM properties"); while($p=$ps->fetch()) echo "<option value='{$p['id']}'>{$p['name']}</option>"; ?>
                </select>
            </div>
            
            <div class="inp-grid">
                <div>
                    <label class="inp-label">اسم / رقم الوحدة</label>
                    <input type="text" name="name" class="inp" placeholder="مثال: شقة 5" required>
                </div>
                <div>
                    <label class="inp-label">نوع الوحدة</label>
                    <select name="type" class="inp">
                        <option value="apartment">شقة سكنية</option>
                        <option value="shop">محل تجاري</option>
                        <option value="villa">فيلا / دبلوكس</option>
                        <option value="office">مكتب</option>
                        <option value="warehouse">مستودع</option>
                    </select>
                </div>
            </div>

            <div class="inp-group">
                <label class="inp-label">السعر السنوي المتوقع</label>
                <input type="number" name="price" class="inp" placeholder="0.00" required>
            </div>

            <div class="inp-grid">
                <div>
                    <label class="inp-label">رقم عداد الكهرباء</label>
                    <input type="text" name="elec" class="inp" placeholder="اختياري">
                </div>
                <div>
                    <label class="inp-label">رقم عداد المياه</label>
                    <input type="text" name="water" class="inp" placeholder="اختياري">
                </div>
            </div>

            <button class="btn btn-primary" style="width:100%; justify-content:center; margin-top:15px">
                <i class="fa-solid fa-check"></i> حفظ الوحدة
            </button>
        </form>
    </div>
</div>
