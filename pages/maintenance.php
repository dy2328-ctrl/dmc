<?php
// حفظ طلب صيانة
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_maint'])) {
    $stmt = $pdo->prepare("INSERT INTO maintenance (property_id, unit_id, vendor_id, description, cost, request_date, status) VALUES (?,?,?,?,?, CURDATE(), 'pending')");
    $stmt->execute([$_POST['pid'], $_POST['uid'], $_POST['vid'], $_POST['desc'], $_POST['cost']]);
    echo "<script>window.location='index.php?p=maintenance';</script>";
}
?>

<div class="card">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px">
        <h3>🛠️ طلبات الصيانة</h3>
        <button onclick="document.getElementById('maintModal').style.display='flex'" class="btn btn-primary"><i class="fa-solid fa-plus"></i> طلب صيانة جديد</button>
    </div>
    
    <table style="width:100%; border-collapse:collapse">
        <thead>
            <tr style="background:#222; text-align:right">
                <th style="padding:10px">الوحدة</th>
                <th style="padding:10px">الوصف</th>
                <th style="padding:10px">المقاول</th>
                <th style="padding:10px">التكلفة</th>
                <th style="padding:10px">الحالة</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $reqs = $pdo->query("SELECT m.*, u.unit_name, v.name as vname FROM maintenance m JOIN units u ON m.unit_id=u.id LEFT JOIN vendors v ON m.vendor_id=v.id ORDER BY id DESC");
            while($r=$reqs->fetch()): ?>
            <tr style="border-bottom:1px solid #333">
                <td style="padding:10px"><?= $r['unit_name'] ?></td>
                <td style="padding:10px"><?= $r['description'] ?></td>
                <td style="padding:10px"><?= $r['vname'] ?></td>
                <td style="padding:10px"><?= $r['cost'] ?></td>
                <td style="padding:10px"><?= $r['status'] ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<div id="maintModal" class="modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:999; justify-content:center; align-items:center;">
    <div class="modal-content" style="background:#1a1a1a; padding:25px; border-radius:15px; width:500px;">
        <div style="display:flex; justify-content:space-between; margin-bottom:20px;">
            <h3 style="margin:0">طلب صيانة جديد</h3>
            <div style="cursor:pointer" onclick="document.getElementById('maintModal').style.display='none'"><i class="fa-solid fa-xmark"></i></div>
        </div>
        <form method="POST">
            <input type="hidden" name="save_maint" value="1">
            
            <label class="inp-label">الوحدة</label>
            <select name="uid" class="inp" required style="width:100%; margin-bottom:10px">
                <?php $us=$pdo->query("SELECT * FROM units"); while($u=$us->fetch()) echo "<option value='{$u['id']}'>{$u['unit_name']}</option>"; ?>
            </select>
            
            <label class="inp-label">المقاول</label>
            <select name="vid" class="inp" required style="width:100%; margin-bottom:10px">
                <?php $vs=$pdo->query("SELECT * FROM vendors"); while($v=$vs->fetch()) echo "<option value='{$v['id']}'>{$v['name']}</option>"; ?>
            </select>
            
            <label class="inp-label">وصف المشكلة</label>
            <textarea name="desc" class="inp" required style="width:100%; margin-bottom:10px"></textarea>
            
            <label class="inp-label">التكلفة المتوقعة</label>
            <input type="number" name="cost" class="inp" style="width:100%; margin-bottom:10px">
            
            <button class="btn btn-primary" style="width:100%; justify-content:center; padding:10px">إرسال الطلب</button>
        </form>
    </div>
</div>
