<?php
// 1. كود الحذف
if (isset($_POST['delete_id'])) {
    check_csrf();
    $stmt = $pdo->prepare("DELETE FROM properties WHERE id = ?");
    $stmt->execute([$_POST['delete_id']]);
    echo "<script>window.location='index.php?p=properties';</script>";
}

// 2. كود الحفظ (إضافة أو تعديل)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_prop'])) {
    check_csrf();
    
    if(!empty($_POST['prop_id'])){
        // تحديث (Edit)
        $stmt = $pdo->prepare("UPDATE properties SET name=?, manager=?, phone=?, address=? WHERE id=?");
        $stmt->execute([$_POST['name'], $_POST['manager'], $_POST['phone'], $_POST['address'], $_POST['prop_id']]);
    } else {
        // إضافة جديد (New)
        $stmt = $pdo->prepare("INSERT INTO properties (name, manager, phone, address) VALUES (?,?,?,?)");
        $stmt->execute([$_POST['name'], $_POST['manager'], $_POST['phone'], $_POST['address']]);
    }
    echo "<script>window.location='index.php?p=properties';</script>";
}
?>

<div class="card">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px">
        <h3>🏙️ إدارة العقارات</h3>
        <button onclick="openModal()" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i> إضافة عقار جديد
        </button>
    </div>
    
    <?php 
    $props = $pdo->query("SELECT * FROM properties ORDER BY id DESC");
    if($props->rowCount() == 0):
    ?>
        <div style="text-align:center; padding:40px; color:#666">
            <i class="fa-solid fa-city" style="font-size:40px; margin-bottom:10px"></i>
            <p>لا توجد عقارات مضافة حتى الآن</p>
        </div>
    <?php else: ?>
        <table>
            <thead><tr><th>اسم العقار</th><th>العنوان</th><th>المدير</th><th>الجوال</th><th>إجراءات</th></tr></thead>
            <tbody>
                <?php while($r = $props->fetch()): ?>
                <tr>
                    <td style="font-weight:bold; color:white"><?= $r['name'] ?></td>
                    <td><i class="fa-solid fa-location-dot" style="color:#6366f1"></i> <?= $r['address'] ?></td>
                    <td><?= $r['manager'] ?></td>
                    <td><?= $r['phone'] ?></td>
                    <td style="display:flex; gap:5px">
                        <button onclick='editProp(<?= json_encode($r) ?>)' class="btn btn-dark" style="padding:5px 10px; font-size:12px"><i class="fa-solid fa-pen"></i></button>
                        
                        <form method="POST" onsubmit="return confirm('هل أنت متأكد من حذف هذا العقار؟ سيتم حذف جميع الوحدات المرتبطة به!');" style="margin:0">
                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                            <input type="hidden" name="delete_id" value="<?= $r['id'] ?>">
                            <button class="btn btn-danger" style="padding:5px 10px; font-size:12px"><i class="fa-solid fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<div id="propModal" class="modal">
    <div class="modal-content">
        <div class="close-icon" onclick="document.getElementById('propModal').style.display='none'"><i class="fa-solid fa-xmark"></i></div>
        <div class="modal-header"><div class="modal-title" id="modalTitle">إضافة عقار جديد</div></div>
        
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="save_prop" value="1">
            <input type="hidden" name="prop_id" id="prop_id"> <div class="inp-group">
                <label class="inp-label">اسم العقار</label>
                <input type="text" name="name" id="p_name" class="inp" placeholder="مثال: عمارة النخيل" required>
            </div>
            
            <div class="inp-group">
                <label class="inp-label">العنوان</label>
                <input type="text" name="address" id="p_address" class="inp" placeholder="المدينة، الحي، الشارع">
            </div>

            <div class="inp-grid">
                <div><label class="inp-label">مدير العقار</label><input type="text" name="manager" id="p_manager" class="inp"></div>
                <div><label class="inp-label">رقم التواصل</label><input type="text" name="phone" id="p_phone" class="inp"></div>
            </div>

            <button class="btn btn-primary" style="width:100%; justify-content:center; margin-top:10px">
                <i class="fa-solid fa-check"></i> حفظ البيانات
            </button>
        </form>
    </div>
</div>

<script>
    function openModal() {
        document.getElementById('propModal').style.display='flex';
        document.getElementById('modalTitle').innerText = 'إضافة عقار جديد';
        document.getElementById('prop_id').value = '';
        document.getElementById('p_name').value = '';
        document.getElementById('p_address').value = '';
        document.getElementById('p_manager').value = '';
        document.getElementById('p_phone').value = '';
    }

    function editProp(data) {
        document.getElementById('propModal').style.display='flex';
        document.getElementById('modalTitle').innerText = 'تعديل بيانات العقار';
        document.getElementById('prop_id').value = data.id;
        document.getElementById('p_name').value = data.name;
        document.getElementById('p_address').value = data.address;
        document.getElementById('p_manager').value = data.manager;
        document.getElementById('p_phone').value = data.phone;
    }
</script>
