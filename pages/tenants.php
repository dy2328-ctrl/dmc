<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_tenant'])) {
    check_csrf();
    $name = $_POST['name'];
    $id_num = $_POST['nid'];
    $photo = null;

    if (!empty($_FILES['id_photo']['tmp_name'])) {
        $photo = upload($_FILES['id_photo']);
        $analysis = $AI->analyzeIDCard($_FILES['id_photo']['tmp_name']);
        if ($analysis['success']) {
            if(empty($name)) $name = $analysis['data']['name'];
            if(empty($id_num)) $id_num = $analysis['data']['id_number'];
        }
    }

    $stmt = $pdo->prepare("INSERT INTO tenants (full_name, phone, id_number, id_photo) VALUES (?,?,?,?)");
    $stmt->execute([$name, $_POST['phone'], $id_num, $photo]);
    
    // إرسال رسالة ترحيب ذكية
    $AI->sendWhatsApp($_POST['phone'], "مرحباً $name، تم فتح ملفك في دار الميار بنجاح.");
    header("Location: index.php?p=tenants"); exit;
}
?>

<div class="card p-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>👥 المستأجرين</h3>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTenantModal">
            <i class="fa-solid fa-magic"></i> إضافة ذكية
        </button>
    </div>
    <table class="table">
        <thead><tr><th>الاسم</th><th>الجوال</th><th>الهوية</th><th>ملف</th></tr></thead>
        <tbody>
            <?php $ts=$pdo->query("SELECT * FROM tenants ORDER BY id DESC"); while($t=$ts->fetch()): ?>
            <tr>
                <td><?= $t['full_name'] ?></td>
                <td><?= $t['phone'] ?></td>
                <td><?= $t['id_number'] ?></td>
                <td><a href="?p=tenant_view&id=<?= $t['id'] ?>" class="btn btn-sm btn-outline-primary">عرض</a></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<div class="modal fade" id="addTenantModal">
    <div class="modal-dialog"><div class="modal-content">
        <form method="POST" enctype="multipart/form-data">
            <div class="modal-header"><h5>إضافة مستأجر (AI)</h5></div>
            <div class="modal-body">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="add_tenant" value="1">
                <div class="mb-3">
                    <label>صورة الهوية (تعبئة تلقائية)</label>
                    <input type="file" name="id_photo" class="form-control">
                </div>
                <input type="text" name="name" class="form-control mb-2" placeholder="الاسم (اختياري)">
                <input type="text" name="phone" class="form-control mb-2" placeholder="الجوال" required>
                <input type="text" name="nid" class="form-control mb-2" placeholder="رقم الهوية (اختياري)">
            </div>
            <div class="modal-footer"><button class="btn btn-primary">حفظ</button></div>
        </form>
    </div></div>
</div>
