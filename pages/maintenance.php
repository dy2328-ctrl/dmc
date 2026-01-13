<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_maint'])) {
    $u = $pdo->query("SELECT property_id FROM units WHERE id=".$_POST['uid'])->fetch();
    $pid = $u ? $u['property_id'] : 0;
    $pdo->prepare("INSERT INTO maintenance (property_id, unit_id, vendor_id, description, cost, request_date, status) VALUES (?,?,?,?,?, CURDATE(), 'pending')")->execute([$pid, $_POST['uid'], $_POST['vid'], $_POST['desc'], $_POST['cost']]);
    echo "<script>window.location='index.php?p=maintenance';</script>";
}
?>

<style>
    #MaintModalUnique { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.9); z-index:999999; justify-content:center; align-items:center; }
    .maint-content { background:#1a1a1a; padding:30px; border-radius:15px; width:500px; border:1px solid #444; }
</style>

<div class="card">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px">
        <h3>🛠️ طلبات الصيانة</h3>
        <button type="button" onclick="document.getElementById('MaintModalUnique').style.display='flex'" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i> طلب جديد
        </button>
    </div>
    
    <table style="width:100%; border-collapse:collapse">
        <thead><tr style="background:#222; text-align:right"><th style="padding:10px">الوحدة</th><th style="padding:10px">الوصف</th><th style="padding:10px">الحالة</th></tr></thead>
        <tbody>
            <?php $reqs=$pdo->query("SELECT m.*, u.unit_name FROM maintenance m JOIN units u ON m.unit_id=u.id ORDER BY id DESC"); 
            while($r=$reqs->fetch()): ?>
            <tr style="border-bottom:1px solid #333">
                <td style="padding:10px"><?= $r['unit_name'] ?></td>
                <td style="padding:10px"><?= $r['description'] ?></td>
                <td style="padding:10px"><?= $r['status'] ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<div id="MaintModalUnique">
    <div class="maint-content">
        <div style="display:flex; justify-content:space-between; margin-bottom:20px;">
            <h3 style="margin:0; color:white">طلب صيانة جديد</h3>
            <div onclick="document.getElementById('MaintModalUnique').style.display='none'" style="cursor:pointer; color:white; font-size:20px;"><i class="fa-solid fa-xmark"></i></div>
        </div>
        
        <form method="POST">
            <input type="hidden" name="save_maint" value="1">
            
            <label style="color:#aaa; display:block; margin-bottom:5px">الوحدة</label>
            <select name="uid" class="inp" style="width:100%; margin-bottom:10px; padding:10px; background:#333; color:white; border:none" required>
                <option value="">-- اختر --</option>
                <?php $us=$pdo->query("SELECT * FROM units"); while($u=$us->fetch()) echo "<option value='{$u['id']}'>{$u['unit_name']}</option>"; ?>
            </select>
            
            <label style="color:#aaa; display:block; margin-bottom:5px">المقاول</label>
            <select name="vid" class="inp" style="width:100%; margin-bottom:10px; padding:10px; background:#333; color:white; border:none">
                <option value="0">-- اختر --</option>
                <?php $vs=$pdo->query("SELECT * FROM vendors"); while($v=$vs->fetch()) echo "<option value='{$v['id']}'>{$v['name']}</option>"; ?>
            </select>
            
            <label style="color:#aaa; display:block; margin-bottom:5px">الوصف</label>
            <textarea name="desc" class="inp" style="width:100%; margin-bottom:10px; padding:10px; background:#333; color:white; border:none; height:80px" required></textarea>
            
            <label style="color:#aaa; display:block; margin-bottom:5px">التكلفة</label>
            <input type="number" name="cost" class="inp" style="width:100%; margin-bottom:20px; padding:10px; background:#333; color:white; border:none">
            
            <button class="btn btn-primary" style="width:100%; justify-content:center; padding:10px">حفظ الطلب</button>
        </form>
    </div>
</div>
