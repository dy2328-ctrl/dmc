<?php
// 1. معالجة الحذف
if (isset($_POST['delete_id'])) {
    $stmt = $pdo->prepare("DELETE FROM vendors WHERE id = ?");
    $stmt->execute([$_POST['delete_id']]);
    echo "<script>window.location='index.php?p=vendors';</script>";
}

// 2. معالجة الحفظ (جديد / تعديل)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_vendor'])) {
    if (!empty($_POST['vendor_id'])) {
        // تعديل
        $stmt = $pdo->prepare("UPDATE vendors SET name=?, service_type=?, phone=? WHERE id=?");
        $stmt->execute([$_POST['name'], $_POST['type'], $_POST['phone'], $_POST['vendor_id']]);
    } else {
        // جديد
        $stmt = $pdo->prepare("INSERT INTO vendors (name, service_type, phone) VALUES (?,?,?)");
        $stmt->execute([$_POST['name'], $_POST['type'], $_POST['phone']]);
    }
    echo "<script>window.location='index.php?p=vendors';</script>";
}
?>

<div class="card">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px">
        <h3>👷 إدارة المقاولين</h3>
        <button onclick="openVendorModal()" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i> إضافة مقاول جديد
        </button>
    </div>
    
    <?php 
    $vendors = $pdo->query("SELECT * FROM vendors ORDER BY id DESC");
    if($vendors->rowCount() == 0): 
    ?>
        <div style="text-align:center; padding:40px; border:2px dashed #333; color:#777; border-radius:10px;">
            لا يوجد مقاولين مسجلين. أضف أول مقاول للبدء.
        </div>
    <?php else: ?>
        <table style="width:100%; border-collapse:collapse">
            <thead>
                <tr style="background:#222; text-align:right">
                    <th style="padding:10px">الاسم / الشركة</th>
                    <th style="padding:10px">التخصص</th>
                    <th style="padding:10px">رقم الجوال</th>
                    <th style="padding:10px">إجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php while($v = $vendors->fetch()): ?>
                <tr style="border-bottom:1px solid #333">
                    <td style="padding:10px; font-weight:bold"><?= $v['name'] ?></td>
                    <td style="padding:10px"><span class="badge" style="background:#333"><?= $v['service_type'] ?></span></td>
                    <td style="padding:10px; font-family:monospace"><?= $v['phone'] ?></td>
                    <td style="padding:10px; display:flex; gap:5px">
                        <button onclick="editVendor(this)" 
                            data-id="<?= $v['id'] ?>"
                            data-name="<?= $v['name'] ?>"
                            data-type="<?= $v['service_type'] ?>"
                            data-phone="<?= $v['phone'] ?>"
                            class="btn btn-dark btn-sm"><i class="fa-solid fa-pen"></i></button>
                        
                        <form method="POST" onsubmit="return confirm('حذف هذا المقاول؟');" style="margin:0">
                            <input type="hidden" name="delete_id" value="<?= $v['id'] ?>">
                            <button class="btn btn-danger btn-sm"><i class="fa-solid fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<div id="vendorModal" class="modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:9999; justify-content:center; align-items:center;">
    <div class="modal-content" style="background:#1a1a1a; padding:25px; border-radius:15px; width:400px; position:relative;">
        
        <div style="display:flex; justify-content:space-between; margin-bottom:20px;">
            <h3 id="vModalTitle" style="margin:0">إضافة مقاول</h3>
            <div style="cursor:pointer; font-size:20px;" onclick="document.getElementById('vendorModal').style.display='none'">
                <i class="fa-solid fa-xmark"></i>
            </div>
        </div>
        
        <form method="POST">
            <input type="hidden" name="save_vendor" value="1">
            <input type="hidden" name="vendor_id" id="v_id">
            
            <div style="margin-bottom:15px">
                <label class="inp-label">اسم المقاول / الشركة</label>
                <input type="text" name="name" id="v_name" class="inp" required style="width:100%">
            </div>
            
            <div style="margin-bottom:15px">
                <label class="inp-label">التخصص (مثال: سباكة، كهرباء)</label>
                <input type="text" name="type" id="v_type" class="inp" required style="width:100%">
            </div>
            
            <div style="margin-bottom:20px">
                <label class="inp-label">رقم الجوال</label>
                <input type="text" name="phone" id="v_phone" class="inp" required style="width:100%">
            </div>
            
            <button class="btn btn-primary" style="width:100%; justify-content:center; padding:12px">حفظ البيانات</button>
        </form>
    </div>
</div>

<script>
    function openVendorModal() {
        document.getElementById('vendorModal').style.display = 'flex';
        document.getElementById('vModalTitle').innerText = 'إضافة مقاول جديد';
        document.getElementById('v_id').value = '';
        document.getElementById('v_name').value = '';
        document.getElementById('v_type').value = '';
        document.getElementById('v_phone').value = '';
    }

    function editVendor(btn) {
        document.getElementById('vendorModal').style.display = 'flex';
        document.getElementById('vModalTitle').innerText = 'تعديل بيانات المقاول';
        document.getElementById('v_id').value = btn.getAttribute('data-id');
        document.getElementById('v_name').value = btn.getAttribute('data-name');
        document.getElementById('v_type').value = btn.getAttribute('data-type');
        document.getElementById('v_phone').value = btn.getAttribute('data-phone');
    }
</script>
