<?php
// === المنطق البرمجي (PHP Logic) ===

// 1. تحديد حالة النافذة (هل يجب أن تظهر؟)
// إذا كان الرابط يحتوي على op=add أو op=edit، ستكون القيمة true
$show_modal = false;
$modal_title = "إضافة مقاول جديد";
$v_data = ['id'=>'', 'name'=>'', 'service_type'=>'', 'phone'=>''];

if (isset($_GET['op'])) {
    $show_modal = true;
    if ($_GET['op'] == 'edit' && isset($_GET['id'])) {
        $modal_title = "تعديل بيانات المقاول";
        $stmt = $pdo->prepare("SELECT * FROM vendors WHERE id=?");
        $stmt->execute([$_GET['id']]);
        $v_data = $stmt->fetch();
    }
}

// 2. معالجة الحفظ
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_vendor'])) {
    if (!empty($_POST['vid'])) {
        $stmt = $pdo->prepare("UPDATE vendors SET name=?, service_type=?, phone=? WHERE id=?");
        $stmt->execute([$_POST['name'], $_POST['type'], $_POST['phone'], $_POST['vid']]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO vendors (name, service_type, phone) VALUES (?,?,?)");
        $stmt->execute([$_POST['name'], $_POST['type'], $_POST['phone']]);
    }
    // بعد الحفظ، ارجع للصفحة الرئيسية بدون نافذة
    echo "<script>window.location='index.php?p=vendors';</script>";
    exit;
}

// 3. معالجة الحذف
if (isset($_POST['delete_id'])) {
    $pdo->prepare("DELETE FROM vendors WHERE id=?")->execute([$_POST['delete_id']]);
    echo "<script>window.location='index.php?p=vendors';</script>";
    exit;
}
?>

<div class="card">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px">
        <h3>👷 إدارة المقاولين</h3>
        <a href="index.php?p=vendors&op=add" class="btn btn-primary" style="text-decoration:none">
            <i class="fa-solid fa-plus"></i> إضافة مقاول
        </a>
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
                    <a href="index.php?p=vendors&op=edit&id=<?= $v['id'] ?>" class="btn btn-dark btn-sm"><i class="fa-solid fa-pen"></i></a>
                    
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

<?php if($show_modal): ?>
<div style="position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.9); z-index:999999; display:flex; justify-content:center; align-items:center;">
    <div style="background:#1f1f1f; padding:30px; border-radius:15px; width:450px; border:1px solid #444; box-shadow: 0 0 50px rgba(0,0,0,0.8); animation: fadeIn 0.3s">
        
        <div style="display:flex; justify-content:space-between; margin-bottom:20px;">
            <h3 style="margin:0; color:#fff"><?= $modal_title ?></h3>
            <a href="index.php?p=vendors" style="color:#fff; font-size:20px; text-decoration:none;">
                <i class="fa-solid fa-xmark"></i>
            </a>
        </div>
        
        <form method="POST" action="index.php?p=vendors">
            <input type="hidden" name="save_vendor" value="1">
            <input type="hidden" name="vid" value="<?= $v_data['id'] ?>">
            
            <div style="margin-bottom:15px">
                <label style="color:#bbb; display:block; margin-bottom:5px">الاسم</label>
                <input type="text" name="name" value="<?= $v_data['name'] ?>" class="inp" style="width:100%; padding:10px; background:#333; color:white; border:1px solid #555" required>
            </div>
            
            <div style="margin-bottom:15px">
                <label style="color:#bbb; display:block; margin-bottom:5px">التخصص</label>
                <input type="text" name="type" value="<?= $v_data['service_type'] ?>" class="inp" style="width:100%; padding:10px; background:#333; color:white; border:1px solid #555" required>
            </div>
            
            <div style="margin-bottom:25px">
                <label style="color:#bbb; display:block; margin-bottom:5px">الجوال</label>
                <input type="text" name="phone" value="<?= $v_data['phone'] ?>" class="inp" style="width:100%; padding:10px; background:#333; color:white; border:1px solid #555" required>
            </div>
            
            <button class="btn btn-primary" style="width:100%; justify-content:center; padding:12px">حفظ</button>
        </form>
    </div>
</div>
<?php endif; ?>
