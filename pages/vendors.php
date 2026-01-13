<?php
// نفس هيكلية الصفحات الناجحة

// 1. كود الحذف
if (isset($_POST['delete_id'])) {
    $stmt = $pdo->prepare("DELETE FROM vendors WHERE id = ?");
    $stmt->execute([$_POST['delete_id']]);
    echo "<script>window.location='index.php?p=vendors';</script>";
}

// 2. كود الحفظ (إضافة أو تعديل)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_vendor'])) {
    
    if(!empty($_POST['vendor_id'])){
        // تحديث (Edit)
        $stmt = $pdo->prepare("UPDATE vendors SET name=?, service_type=?, phone=? WHERE id=?");
        $stmt->execute([$_POST['name'], $_POST['type'], $_POST['phone'], $_POST['vendor_id']]);
    } else {
        // إضافة جديد (New)
        $stmt = $pdo->prepare("INSERT INTO vendors (name, service_type, phone) VALUES (?,?,?)");
        $stmt->execute([$_POST['name'], $_POST['type'], $_POST['phone']]);
    }
    echo "<script>window.location='index.php?p=vendors';</script>";
}
?>

<style>
    .modal { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:9999; justify-content:center; align-items:center; }
    .modal-content { background:#1a1a1a; padding:25px; border-radius:15px; width:500px; max-width:90%; border:1px solid #444; }
    .close-icon { float:left; cursor:pointer; font-size:20px; color:#aaa; }
</style>

<div class="card">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px">
        <h3>👷 إدارة المقاولين</h3>
        <button onclick="openVendorModal()" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i> إضافة مقاول
        </button>
    </div>
    
    <?php 
    $vendors = $pdo->query("SELECT * FROM vendors ORDER BY id DESC");
    if($vendors->rowCount() == 0):
    ?>
        <div style="text-align:center; padding:40px; color:#666">
            <p>لا يوجد مقاولين حتى الآن</p>
        </div>
    <?php else: ?>
        <table>
            <thead><tr><th>الاسم</th><th>التخصص</th><th>الجوال</th><th>إجراءات</th></tr></thead>
            <tbody>
                <?php while($r = $vendors->fetch()): ?>
                <tr>
                    <td style="font-weight:bold"><?= $r['name'] ?></td>
                    <td><span class="badge" style="background:#333"><?= $r['service_type'] ?></span></td>
                    <td><?= $r['phone'] ?></td>
                    <td style="display:flex; gap:5px">
                        <button onclick='editVendor(<?= json_encode($r) ?>)' class="btn btn-dark" style="padding:5px 10px"><i class="fa-solid fa-pen"></i></button>
                        
                        <form method="POST" onsubmit="return confirm('هل أنت متأكد من الحذف؟');" style="margin:0">
                            <input type="hidden" name="delete_id" value="<?= $r['id'] ?>">
                            <button class="btn btn-danger" style="padding:5px 10px"><i class="fa-solid fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<div id="vendorModal" class="modal">
    <div class="modal-content">
        <div class="close-icon" onclick="document.getElementById('vendorModal').style.display='none'"><i class="fa-solid fa-xmark"></i></div>
        <div class="modal-header"><div class="modal-title" id="vModalTitle">إضافة مقاول جديد</div></div>
        
        <form method="POST">
            <input type="hidden" name="save_vendor" value="1">
            <input type="hidden" name="vendor_id" id="vendor_id"> <div class="inp-group">
                <label class="inp-label">اسم المقاول / الشركة</label>
                <input type="text" name="name" id="v_name" class="inp" required>
            </div>
            
            <div class="inp-group">
                <label class="inp-label">التخصص (سباكة، كهرباء...)</label>
                <input type="text" name="type" id="v_type" class="inp" required>
            </div>

            <div class="inp-group">
                <label class="inp-label">رقم الجوال</label>
                <input type="text" name="phone" id="v_phone" class="inp" required>
            </div>

            <button class="btn btn-primary" style="width:100%; justify-content:center; margin-top:15px">
                <i class="fa-solid fa-check"></i> حفظ البيانات
            </button>
        </form>
    </div>
</div>

<script>
    // دوال الجافاسكربت البسيطة (مثل العقارات)
    function openVendorModal() {
        document.getElementById('vendorModal').style.display='flex';
        document.getElementById('vModalTitle').innerText = 'إضافة مقاول جديد';
        document.getElementById('vendor_id').value = '';
        document.getElementById('v_name').value = '';
        document.getElementById('v_type').value = '';
        document.getElementById('v_phone').value = '';
    }

    function editVendor(data) {
        document.getElementById('vendorModal').style.display='flex';
        document.getElementById('vModalTitle').innerText = 'تعديل بيانات المقاول';
        document.getElementById('vendor_id').value = data.id;
        document.getElementById('v_name').value = data.name;
        document.getElementById('v_type').value = data.service_type;
        document.getElementById('v_phone').value = data.phone;
    }
</script>
