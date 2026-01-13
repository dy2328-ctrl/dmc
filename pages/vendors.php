<?php
// 1. الحذف
if (isset($_POST['delete_id'])) {
    $pdo->prepare("DELETE FROM vendors WHERE id=?")->execute([$_POST['delete_id']]);
    echo "<script>window.location='index.php?p=vendors';</script>";
}

// 2. الحفظ
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_vendor'])) {
    if(!empty($_POST['vid'])){
        $stmt = $pdo->prepare("UPDATE vendors SET name=?, service_type=?, phone=? WHERE id=?");
        $stmt->execute([$_POST['name'], $_POST['type'], $_POST['phone'], $_POST['vid']]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO vendors (name, service_type, phone) VALUES (?,?,?)");
        $stmt->execute([$_POST['name'], $_POST['type'], $_POST['phone']]);
    }
    echo "<script>window.location='index.php?p=vendors';</script>";
}
?>

<style>
    /* تصميم النافذة */
    .modal-overlay {
        display: none; /* مخفية افتراضياً */
        position: fixed; top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(0,0,0,0.85); z-index: 9999999; /* رقم عالي جداً */
        justify-content: center; align-items: center;
        backdrop-filter: blur(5px);
    }
    
    .modal-box {
        background: #1f1f1f; padding: 30px; border-radius: 15px;
        width: 450px; border: 1px solid #444; position: relative;
        box-shadow: 0 20px 50px rgba(0,0,0,0.5);
    }
    .close-btn {
        position: absolute; top: 15px; left: 15px;
        color: #aaa; font-size: 20px; text-decoration: none; cursor: pointer;
    }
    .close-btn:hover { color: white; }
</style>

<div class="card">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px">
        <h3>👷 إدارة المقاولين</h3>
        <button onclick="openVendorModal()" class="btn btn-primary" style="border:none; cursor:pointer">
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
            <?php $vendors = $pdo->query("SELECT * FROM vendors ORDER BY id DESC"); while($v = $vendors->fetch()): ?>
            <tr style="border-bottom:1px solid #333">
                <td style="padding:15px"><?= $v['name'] ?></td>
                <td style="padding:15px"><?= $v['service_type'] ?></td>
                <td style="padding:15px"><?= $v['phone'] ?></td>
                <td style="padding:15px; display:flex; gap:10px">
                    <a href="index.php?p=vendors&edit=1&id=<?= $v['id'] ?>" class="btn btn-dark btn-sm"><i class="fa-solid fa-pen"></i></a>
                    
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

<div id="vendorModal" class="modal-overlay">
    <div class="modal-box">
        <a onclick="closeVendorModal()" class="close-btn"><i class="fa-solid fa-xmark"></i></a>
        
        <?php
        // تعبئة البيانات إذا كان تعديل
        $e_id = ''; $e_name = ''; $e_type = ''; $e_phone = '';
        $title = 'إضافة مقاول جديد';
        
        // التحقق مما إذا كنا في وضع التعديل
        $is_edit_mode = false;
        if(isset($_GET['edit']) && isset($_GET['id'])) {
            $e = $pdo->query("SELECT * FROM vendors WHERE id=".$_GET['id'])->fetch();
            if($e) {
                $e_id = $e['id']; $e_name = $e['name']; $e_type = $e['service_type']; $e_phone = $e['phone'];
                $title = 'تعديل البيانات';
                $is_edit_mode = true;
            }
        }
        ?>
        
        <h3 style="margin-top:0; color:white; margin-bottom:20px"><?= $title ?></h3>
        
        <form method="POST" action="index.php?p=vendors">
            <input type="hidden" name="save_vendor" value="1">
            <input type="hidden" name="vid" value="<?= $e_id ?>">
            
            <div style="margin-bottom:15px">
                <label style="color:#aaa; display:block; margin-bottom:5px">الاسم</label>
                <input type="text" name="name" value="<?= $e_name ?>" class="inp" style="width:100%; padding:10px; background:#333; color:white; border:1px solid #555" required>
            </div>
            
            <div style="margin-bottom:15px">
                <label style="color:#aaa; display:block; margin-bottom:5px">التخصص</label>
                <input type="text" name="type" value="<?= $e_type ?>" class="inp" style="width:100%; padding:10px; background:#333; color:white; border:1px solid #555" required>
            </div>
            
            <div style="margin-bottom:25px">
                <label style="color:#aaa; display:block; margin-bottom:5px">الجوال</label>
                <input type="text" name="phone" value="<?= $e_phone ?>" class="inp" style="width:100%; padding:10px; background:#333; color:white; border:1px solid #555" required>
            </div>
            
            <button class="btn btn-primary" style="width:100%; justify-content:center; padding:12px">حفظ</button>
        </form>
    </div>
</div>

<script>
    // دوال الفتح والإغلاق المضمونة
    function openVendorModal() {
        document.getElementById('vendorModal').style.display = 'flex';
    }
    
    function closeVendorModal() {
        document.getElementById('vendorModal').style.display = 'none';
        // تنظيف الرابط لإزالة بارامترات التعديل عند الإغلاق
        window.history.pushState({}, document.title, "index.php?p=vendors");
    }

    // إذا كانت الصفحة تحتوي على طلب تعديل، افتح النافذة تلقائياً
    <?php if($is_edit_mode): ?>
    openVendorModal();
    <?php endif; ?>
</script>
