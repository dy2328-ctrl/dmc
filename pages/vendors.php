<?php
// معالجة الحذف والحفظ
if (isset($_POST['delete_id'])) {
    $pdo->prepare("DELETE FROM vendors WHERE id=?")->execute([$_POST['delete_id']]);
    echo "<script>window.location='index.php?p=vendors';</script>";
}
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
    #VendorModalUnique { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.9); z-index:999999; justify-content:center; align-items:center; }
    .ven-content { background:#1a1a1a; padding:30px; border-radius:15px; width:450px; border:1px solid #444; }
</style>

<div class="card">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px">
        <h3>👷 إدارة المقاولين</h3>
        <button type="button" onclick="document.getElementById('VendorModalUnique').style.display='flex'; document.getElementById('ven_form').reset(); document.getElementById('ven_vid').value='';" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i> إضافة مقاول
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
            while($v = $vendors->fetch()): 
            ?>
            <tr style="border-bottom:1px solid #333">
                <td style="padding:10px"><?= $v['name'] ?></td>
                <td style="padding:10px"><?= $v['service_type'] ?></td>
                <td style="padding:10px"><?= $v['phone'] ?></td>
                <td style="padding:10px">
                    <button type="button" 
                        onclick="
                            document.getElementById('VendorModalUnique').style.display='flex';
                            document.getElementById('ven_vid').value='<?= $v['id'] ?>';
                            document.getElementById('ven_name').value='<?= $v['name'] ?>';
                            document.getElementById('ven_type').value='<?= $v['service_type'] ?>';
                            document.getElementById('ven_phone').value='<?= $v['phone'] ?>';
                        " 
                        class="btn btn-dark btn-sm"><i class="fa-solid fa-pen"></i></button>
                    
                    <form method="POST" onsubmit="return confirm('حذف؟')" style="display:inline">
                        <input type="hidden" name="delete_id" value="<?= $v['id'] ?>">
                        <button class="btn btn-danger btn-sm"><i class="fa-solid fa-trash"></i></button>
                    </form>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<div id="VendorModalUnique">
    <div class="ven-content">
        <div style="display:flex; justify-content:space-between; margin-bottom:20px;">
            <h3 style="margin:0; color:white">بيانات المقاول</h3>
            <div onclick="document.getElementById('VendorModalUnique').style.display='none'" style="cursor:pointer; color:white; font-size:20px;"><i class="fa-solid fa-xmark"></i></div>
        </div>
        
        <form method="POST" id="ven_form">
            <input type="hidden" name="save_vendor" value="1">
            <input type="hidden" name="vid" id="ven_vid">
            
            <label style="color:#aaa; display:block; margin-bottom:5px">الاسم</label>
            <input type="text" name="name" id="ven_name" class="inp" style="width:100%; margin-bottom:10px; padding:10px; background:#333; color:white; border:none" required>
            
            <label style="color:#aaa; display:block; margin-bottom:5px">التخصص</label>
            <input type="text" name="type" id="ven_type" class="inp" style="width:100%; margin-bottom:10px; padding:10px; background:#333; color:white; border:none" required>
            
            <label style="color:#aaa; display:block; margin-bottom:5px">الجوال</label>
            <input type="text" name="phone" id="ven_phone" class="inp" style="width:100%; margin-bottom:20px; padding:10px; background:#333; color:white; border:none" required>
            
            <button class="btn btn-primary" style="width:100%; justify-content:center; padding:10px">حفظ</button>
        </form>
    </div>
</div>
