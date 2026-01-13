<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_vendor'])) {
    $stmt = $pdo->prepare("INSERT INTO vendors (name, service_type, phone) VALUES (?,?,?)");
    $stmt->execute([$_POST['name'], $_POST['type'], $_POST['phone']]);
    echo "<script>window.location='index.php?p=vendors';</script>";
}
?>

<div class="card">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px">
        <h3>👷 إدارة المقاولين</h3>
        <button onclick="document.getElementById('vendorModal').style.display='flex'" class="btn btn-primary"><i class="fa-solid fa-plus"></i> إضافة مقاول</button>
    </div>
    
    <table style="width:100%; border-collapse:collapse">
        <thead>
            <tr style="background:#222; text-align:right">
                <th style="padding:10px">الاسم</th>
                <th style="padding:10px">التخصص</th>
                <th style="padding:10px">الجوال</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $vens = $pdo->query("SELECT * FROM vendors");
            while($v=$vens->fetch()): ?>
            <tr style="border-bottom:1px solid #333">
                <td style="padding:10px"><?= $v['name'] ?></td>
                <td style="padding:10px"><?= $v['service_type'] ?></td>
                <td style="padding:10px"><?= $v['phone'] ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<div id="vendorModal" class="modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:999; justify-content:center; align-items:center;">
    <div class="modal-content" style="background:#1a1a1a; padding:25px; border-radius:15px; width:400px;">
        <div style="display:flex; justify-content:space-between; margin-bottom:20px;">
            <h3 style="margin:0">إضافة مقاول</h3>
            <div style="cursor:pointer" onclick="document.getElementById('vendorModal').style.display='none'"><i class="fa-solid fa-xmark"></i></div>
        </div>
        <form method="POST">
            <input type="hidden" name="save_vendor" value="1">
            <label class="inp-label">اسم المقاول / الشركة</label>
            <input type="text" name="name" class="inp" required style="width:100%; margin-bottom:10px">
            <label class="inp-label">التخصص (سباكة، كهرباء...)</label>
            <input type="text" name="type" class="inp" required style="width:100%; margin-bottom:10px">
            <label class="inp-label">رقم الجوال</label>
            <input type="text" name="phone" class="inp" required style="width:100%; margin-bottom:10px">
            <button class="btn btn-primary" style="width:100%; justify-content:center; padding:10px">حفظ</button>
        </form>
    </div>
</div>
