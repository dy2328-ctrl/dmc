<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_tenant'])) {
    check_csrf();
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $id_num = $_POST['id_number'];
    $ai_data = null;

    // معالجة الذكاء الاصطناعي
    if (!empty($_FILES['id_photo']['tmp_name'])) {
        // رفع الصورة (لم نعد نحفظ مسار الصورة في عمود خاص لأن الجدول لا يحتوي عليه، سنحفظ البيانات في JSON)
        // ملاحظة: الجدول المرفق لا يحتوي على عمود 'photo'، لذا سنكتفي بتحليل البيانات
        $analysis = $AI->analyzeIDCard($_FILES['id_photo']['tmp_name']);
        
        if ($analysis['success']) {
            if(empty($name)) $name = $analysis['data']['extracted_name'];
            if(empty($id_num)) $id_num = $analysis['data']['id_number'];
            $ai_data = json_encode($analysis['data'], JSON_UNESCAPED_UNICODE);
        }
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO tenants (name, phone, id_number, document_data) VALUES (?,?,?,?)");
        $stmt->execute([$name, $phone, $id_num, $ai_data]);
        
        $AI->sendWhatsApp($phone, "مرحباً $name، تم تسجيلك في نظام دار الميار.");
        echo "<script>window.location='index.php?p=tenants';</script>";
    } catch (PDOException $e) {
        echo "<div class='alert alert-danger'>خطأ: " . $e->getMessage() . "</div>";
    }
}
?>

<div class="card p-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>👥 إدارة المستأجرين</h3>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTenantModal">
            <i class="fa-solid fa-magic"></i> إضافة ذكية
        </button>
    </div>
    <table class="table table-hover">
        <thead><tr><th>الاسم</th><th>الجوال</th><th>الهوية</th><th>حالة التوثيق</th></tr></thead>
        <tbody>
            <?php 
            // استخدام العمود 'name' حسب ملف SQL
            $ts=$pdo->query("SELECT * FROM tenants ORDER BY id DESC"); 
            while($t=$ts->fetch()): 
                $is_verified = !empty($t['document_data']);
            ?>
            <tr>
                <td><?= htmlspecialchars($t['name']) ?></td>
                <td><?= htmlspecialchars($t['phone']) ?></td>
                <td><?= htmlspecialchars($t['id_number']) ?></td>
                <td>
                    <?php if($is_verified): ?>
                        <span class="badge bg-success"><i class="fa-solid fa-check-circle"></i> AI Verified</span>
                    <?php else: ?>
                        <span class="badge bg-secondary">يدوي</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<div class="modal fade" id="addTenantModal">
    <div class="modal-dialog"><div class="modal-content">
        <form method="POST" enctype="multipart/form-data">
            <div class="modal-header"><h5>إضافة مستأجر (AI Scan)</h5></div>
            <div class="modal-body">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="add_tenant" value="1">
                <div class="alert alert-info py-2"><small>ارفع الهوية لملء البيانات تلقائياً</small></div>
                
                <div class="mb-3">
                    <label>صورة الهوية</label>
                    <input type="file" name="id_photo" class="form-control">
                </div>
                <input type="text" name="name" class="form-control mb-2" placeholder="الاسم (يملأ تلقائياً)">
                <input type="text" name="phone" class="form-control mb-2" placeholder="الجوال (مثال: 9665...)" required>
                <input type="text" name="id_number" class="form-control mb-2" placeholder="رقم الهوية">
            </div>
            <div class="modal-footer"><button class="btn btn-primary w-100">حفظ وتوثيق</button></div>
        </form>
    </div></div>
</div>
