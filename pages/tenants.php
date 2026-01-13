<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_tenant'])) {
    check_csrf();
    $stmt = $pdo->prepare("INSERT INTO tenants (name, phone, id_number) VALUES (?,?,?)");
    $stmt->execute([$_POST['name'], $_POST['phone'], $_POST['nid']]);
    echo "<script>window.location='index.php?p=tenants';</script>";
}
?>

<div class="card">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px">
        <h3>👥 إدارة المستأجرين</h3>
        <button onclick="document.getElementById('tenantModal').style.display='flex'" class="btn btn-primary">
            <i class="fa-solid fa-user-plus"></i> إضافة مستأجر جديد
        </button>
    </div>
    
    <table>
        <thead><tr><th>الاسم</th><th>الجوال</th><th>رقم الهوية</th><th>ملف</th></tr></thead>
        <tbody>
            <?php 
            $ts=$pdo->query("SELECT * FROM tenants ORDER BY id DESC"); 
            while($t=$ts->fetch()): ?>
            <tr>
                <td style="font-weight:bold"><?= $t['name'] ?></td>
                <td><?= $t['phone'] ?></td>
                <td><?= $t['id_number'] ?></td>
                <td><a href="#" class="btn btn-dark" style="padding:5px 15px; font-size:12px">عرض</a></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<div id="tenantModal" class="modal">
    <div class="modal-content">
        <div class="close-icon" onclick="document.getElementById('tenantModal').style.display='none'"><i class="fa-solid fa-xmark"></i></div>
        <div class="modal-header"><div class="modal-title">إضافة مستأجر جديد</div></div>
        
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="add_tenant" value="1">
            
            <div class="inp-group">
                <label class="inp-label">الاسم الكامل</label>
                <input type="text" name="name" class="inp" placeholder="الاسم كما في الهوية" required>
            </div>
            
            <div class="inp-grid">
                <div><label class="inp-label">رقم الجوال</label><input type="text" name="phone" class="inp" placeholder="05xxxxxxxx" required></div>
                <div><label class="inp-label">رقم الهوية / الإقامة</label><input type="text" name="nid" class="inp"></div>
            </div>

            <button class="btn btn-primary" style="width:100%; justify-content:center; margin-top:10px">
                <i class="fa-solid fa-check"></i> حفظ البيانات
            </button>
        </form>
    </div>
</div>
