<?php
if(isset($_POST['add_v'])){
    check_csrf();
    $pdo->prepare("INSERT INTO vendors (name, service_type, phone) VALUES (?,?,?)")->execute([$_POST['name'], $_POST['type'], $_POST['phone']]);
    echo "<script>window.location='index.php?p=vendors';</script>";
}
?>

<div class="card">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px">
        <h3><i class="fa-solid fa-helmet-safety" style="color:var(--primary)"></i> المقاولين ومزودي الخدمة</h3>
        <button onclick="document.getElementById('venModal').style.display='flex'" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i> إضافة مقاول
        </button>
    </div>
    
    <table>
        <thead><tr><th>الاسم</th><th>التخصص</th><th>الجوال</th><th>الرصيد</th></tr></thead>
        <tbody>
            <?php $vs=$pdo->query("SELECT * FROM vendors"); while($v=$vs->fetch()): ?>
            <tr>
                <td style="font-weight:bold"><?= $v['name'] ?></td>
                <td><span class="badge" style="background:#333"><?= $v['service_type'] ?></span></td>
                <td><?= $v['phone'] ?></td>
                <td><?= number_format($v['balance']??0) ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<div id="venModal" class="modal">
    <div class="modal-content">
        <div class="close-icon" onclick="document.getElementById('venModal').style.display='none'"><i class="fa-solid fa-xmark"></i></div>
        <div class="modal-header"><div class="modal-title">تسجيل مقاول جديد</div></div>
        
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="add_v" value="1">
            
            <div class="inp-group">
                <label class="inp-label">اسم المقاول / الشركة</label>
                <input type="text" name="name" class="inp" required>
            </div>
            
            <div class="inp-grid">
                <div><label class="inp-label">التخصص (سباكة، كهرباء..)</label><input type="text" name="type" class="inp"></div>
                <div><label class="inp-label">رقم الجوال</label><input type="text" name="phone" class="inp"></div>
            </div>

            <button class="btn btn-primary" style="width:100%; justify-content:center; margin-top:10px">
                <i class="fa-solid fa-check"></i> حفظ المقاول
            </button>
        </form>
    </div>
</div>
