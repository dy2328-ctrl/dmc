<?php
// معالجة الحذف
if (isset($_POST['delete_id'])) {
    $pdo->prepare("DELETE FROM vendors WHERE id=?")->execute([$_POST['delete_id']]);
    echo "<script>window.location='index.php?p=vendors';</script>";
}
// معالجة الحفظ
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_vendor'])) {
    if (!empty($_POST['vid'])) {
        $stmt = $pdo->prepare("UPDATE vendors SET name=?, service_type=?, phone=? WHERE id=?");
        $stmt->execute([$_POST['name'], $_POST['type'], $_POST['phone'], $_POST['vid']]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO vendors (name, service_type, phone) VALUES (?,?,?)");
        $stmt->execute([$_POST['name'], $_POST['type'], $_POST['phone']]);
    }
    echo "<script>window.location='index.php?p=vendors';</script>";
}
?>

<div class="card">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px">
        <h3>👷 إدارة المقاولين</h3>
        <button type="button" onclick="openVenModal()" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i> إضافة مقاول
        </button>
    </div>
    
    <table style="width:100%; border-collapse:collapse">
        <thead>
            <tr style="background:#222; text-align:right">
                <th style="padding:15px">الاسم</th>
                <th style="padding:15px">التخصص</th>
                <th style="padding:15px">الجوال</th>
                <th style="padding:15px">إجراءات</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $vendors = $pdo->query("SELECT * FROM vendors ORDER BY id DESC");
            while($v = $vendors->fetch()): 
            ?>
            <tr style="border-bottom:1px solid #333">
                <td style="padding:15px"><?= $v['name'] ?></td>
                <td style="padding:15px"><?= $v['service_type'] ?></td>
                <td style="padding:15px"><?= $v['phone'] ?></td>
                <td style="padding:15px; display:flex; gap:10px">
                    <button type="button" onclick='editVen(<?= json_encode($v) ?>)' class="btn btn-dark btn-sm"><i class="fa-solid fa-pen"></i></button>
                    <form method="POST" onsubmit="return confirm('حذف؟')" style="margin:0">
                        <input type="hidden" name="delete_id" value="<?= $v['id'] ?>">
                        <button class="btn btn-danger btn-sm"><i class="fa-solid fa-trash"></i></button>
                    </form>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<div id="venModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.9); z-index:999999; justify-content:center; align-items:center;">
    <div style="background:#1f1f1f; padding:30px; border-radius:15px; width:450px; border:1px solid #444; box-shadow: 0 0 50px rgba(0,0,0,0.8);">
        <div style="display:flex; justify-content:space-between; margin-bottom:20px;">
            <h3 id="venTitle" style="margin:0; color:#fff">إضافة مقاول</h3>
            <button type="button" onclick="closeVenModal()" style="background:none; border:none; color:#fff; font-size:20px; cursor:pointer;"><i class="fa-solid fa-xmark"></i></button>
        </div>
        
        <form method="POST">
            <input type="hidden" name="save_vendor" value="1">
            <input type="hidden" name="vid" id="v_id">
            
            <div style="margin-bottom:15px">
                <label style="color:#bbb; display:block; margin-bottom:5px">الاسم</label>
                <input type="text" name="name" id="v_name" class="inp" style="width:100%; padding:10px; background:#333; color:white; border:1px solid #555" required>
            </div>
            <div style="margin-bottom:15px">
                <label style="color:#bbb; display:block; margin-bottom:5px">التخصص</label>
                <input type="text" name="type" id="v_type" class="inp" style="width:100%; padding:10px; background:#333; color:white; border:1px solid #555" required>
            </div>
            <div style="margin-bottom:25px">
                <label style="color:#bbb; display:block; margin-bottom:5px">الجوال</label>
                <input type="text" name="phone" id="v_phone" class="inp" style="width:100%; padding:10px; background:#333; color:white; border:1px solid #555" required>
            </div>
            <button class="btn btn-primary" style="width:100%; justify-content:center; padding:12px">حفظ</button>
        </form>
    </div>
</div>

<script>
    // هذا الكود هو السر: ينقل النافذة إلى خارج حدود الصفحة المحبوسة
    function moveModalToBody() {
        var modal = document.getElementById('venModal');
        if (modal.parentNode !== document.body) {
            document.body.appendChild(modal);
        }
    }

    function openVenModal() {
        moveModalToBody(); // نقل النافذة للجسم الرئيسي
        document.getElementById('venModal').style.display = 'flex';
        document.getElementById('venTitle').innerText = 'إضافة مقاول جديد';
        document.getElementById('v_id').value = '';
        document.getElementById('v_name').value = '';
        document.getElementById('v_type').value = '';
        document.getElementById('v_phone').value = '';
    }
    
    function editVen(data) {
        moveModalToBody(); // نقل النافذة للجسم الرئيسي
        document.getElementById('venModal').style.display = 'flex';
        document.getElementById('venTitle').innerText = 'تعديل بيانات المقاول';
        document.getElementById('v_id').value = data.id;
        document.getElementById('v_name').value = data.name;
        document.getElementById('v_type').value = data.service_type;
        document.getElementById('v_phone').value = data.phone;
    }

    function closeVenModal() {
        document.getElementById('venModal').style.display = 'none';
    }
</script>
