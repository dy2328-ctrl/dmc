<?php
// 1. كود الحذف
if (isset($_POST['delete_id'])) {
    $stmt = $pdo->prepare("DELETE FROM vendors WHERE id = ?");
    $stmt->execute([$_POST['delete_id']]);
    echo "<script>window.location='index.php?p=vendors';</script>";
}

// 2. كود الحفظ والإضافة
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
        <button type="button" onclick="showVendorModal()" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i> إضافة مقاول جديد
        </button>
    </div>
    
    <table style="width:100%; border-collapse:collapse">
        <thead>
            <tr style="background:#222; text-align:right">
                <th style="padding:10px">الاسم</th>
                <th style="padding:10px">التخصص</th>
                <th style="padding:10px">الجوال</th>
                <th style="padding:10px">إجراءات</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $vendors = $pdo->query("SELECT * FROM vendors ORDER BY id DESC");
            if($vendors->rowCount() == 0): 
                echo "<tr><td colspan='4' style='text-align:center; padding:20px'>لا يوجد مقاولين. أضف أول مقاول.</td></tr>";
            else:
                while($v = $vendors->fetch()): 
            ?>
            <tr style="border-bottom:1px solid #333">
                <td style="padding:10px; font-weight:bold"><?= $v['name'] ?></td>
                <td style="padding:10px"><?= $v['service_type'] ?></td>
                <td style="padding:10px"><?= $v['phone'] ?></td>
                <td style="padding:10px; display:flex; gap:5px">
                    <button type="button" onclick='editVendor(<?= json_encode($v) ?>)' class="btn btn-dark btn-sm"><i class="fa-solid fa-pen"></i></button>
                    <form method="POST" onsubmit="return confirm('حذف؟')" style="margin:0">
                        <input type="hidden" name="delete_id" value="<?= $v['id'] ?>">
                        <button class="btn btn-danger btn-sm"><i class="fa-solid fa-trash"></i></button>
                    </form>
                </td>
            </tr>
            <?php endwhile; endif; ?>
        </tbody>
    </table>
</div>

<div id="forceVendorModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.9); z-index:99999; justify-content:center; align-items:center;">
    <div style="background:#1a1a1a; padding:30px; border-radius:10px; width:400px; border:1px solid #444;">
        <h3 style="margin-top:0; color:#fff">بيانات المقاول</h3>
        <form method="POST">
            <input type="hidden" name="save_vendor" value="1">
            <input type="hidden" name="vendor_id" id="fv_id">
            
            <div style="margin-bottom:15px">
                <label style="color:#aaa; display:block; margin-bottom:5px">الاسم</label>
                <input type="text" name="name" id="fv_name" class="inp" required style="width:100%; padding:10px;">
            </div>
            <div style="margin-bottom:15px">
                <label style="color:#aaa; display:block; margin-bottom:5px">التخصص</label>
                <input type="text" name="type" id="fv_type" class="inp" required style="width:100%; padding:10px;">
            </div>
            <div style="margin-bottom:15px">
                <label style="color:#aaa; display:block; margin-bottom:5px">الجوال</label>
                <input type="text" name="phone" id="fv_phone" class="inp" required style="width:100%; padding:10px;">
            </div>
            
            <div style="display:flex; gap:10px; margin-top:20px">
                <button class="btn btn-primary" style="flex:1">حفظ</button>
                <button type="button" onclick="document.getElementById('forceVendorModal').style.display='none'" class="btn btn-danger">إلغاء</button>
            </div>
        </form>
    </div>
</div>

<script>
    function showVendorModal() {
        document.getElementById('forceVendorModal').style.display = 'flex';
        document.getElementById('fv_id').value = '';
        document.getElementById('fv_name').value = '';
        document.getElementById('fv_type').value = '';
        document.getElementById('fv_phone').value = '';
    }
    
    function editVendor(data) {
        document.getElementById('forceVendorModal').style.display = 'flex';
        document.getElementById('fv_id').value = data.id;
        document.getElementById('fv_name').value = data.name;
        document.getElementById('fv_type').value = data.service_type;
        document.getElementById('fv_phone').value = data.phone;
    }
</script>
