<?php
// معالجة إضافة مستأجر جديد باستخدام AI
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_tenant'])) {
    check_csrf(); // حماية CSRF
    
    $name = $_POST['name'];
    $id_num = $_POST['nid'];
    
    // إذا تم رفع صورة هوية، استخدم المحلل الذكي
    if (!empty($_FILES['id_photo']['tmp_name'])) {
        $analysis = $AI->analyzeIDCard($_FILES['id_photo']['tmp_name']);
        if ($analysis['success']) {
            // تحديث البيانات تلقائياً من الصورة إذا كانت الحقول فارغة
            if(empty($name)) $name = $analysis['data']['name'];
            if(empty($id_num)) $id_num = $analysis['data']['id_number'];
        }
        $photoPath = upload($_FILES['id_photo']); // دالة الرفع (تأكد من وجودها)
    }

    $stmt = $pdo->prepare("INSERT INTO tenants (full_name, phone, id_number, id_photo) VALUES (?,?,?,?)");
    $stmt->execute([$name, $_POST['phone'], $id_num, $photoPath ?? null]);
    
    // إرسال ترحيب عبر واتساب تلقائياً
    $AI->sendWhatsApp($_POST['phone'], "مرحباً $name، تم تسجيلك بنجاح في نظام دار الميار.");
    
    echo "<script>alert('تمت الإضافة وتحليل الهوية بنجاح!'); window.location='?p=tenants';</script>";
}
?>

<div class="card">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>👥 إدارة المستأجرين (الجيل الذكي)</h2>
        <button onclick="document.getElementById('addTenantModal').style.display='block'" class="btn btn-primary">
            <i class="fa-solid fa-magic"></i> إضافة ذكية
        </button>
    </div>

    <table class="table">
        <thead><tr><th>الاسم</th><th>الهوية</th><th>الذكاء</th><th>إجراءات</th></tr></thead>
        <tbody>
            <?php 
            $tenants = $pdo->query("SELECT * FROM tenants ORDER BY id DESC");
            while($t = $tenants->fetch()): 
            ?>
            <tr>
                <td><?= htmlspecialchars($t['full_name']) ?></td>
                <td><?= htmlspecialchars($t['id_number']) ?></td>
                <td>
                    <?php if($t['document_data']): ?>
                        <span class="badge bg-success"><i class="fa-solid fa-check"></i> موثق بالذكاء</span>
                    <?php else: ?>
                        <span class="badge bg-secondary">يدوي</span>
                    <?php endif; ?>
                </td>
                <td><a href="?p=tenant_view&id=<?= $t['id'] ?>" class="btn btn-sm btn-info">عرض</a></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<div id="addTenantModal" class="modal" tabindex="-1" style="background:rgba(0,0,0,0.5)">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">إضافة مستأجر (AI Scan)</h5>
        <button type="button" class="btn-close" onclick="document.getElementById('addTenantModal').style.display='none'"></button>
      </div>
      <form method="POST" enctype="multipart/form-data">
          <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
          <input type="hidden" name="add_tenant" value="1">
          <div class="modal-body">
              <div class="alert alert-info">
                  <i class="fa-solid fa-robot"></i> نصيحة: ارفع صورة الهوية فقط، وسيقوم النظام باستخراج الاسم ورقم الهوية تلقائياً.
              </div>
              <div class="mb-3">
                  <label>صورة الهوية (للتحليل التلقائي)</label>
                  <input type="file" name="id_photo" class="form-control" accept="image/*">
              </div>
              <hr>
              <div class="mb-3"><input type="text" name="name" class="form-control" placeholder="الاسم الكامل (اختياري)"></div>
              <div class="mb-3"><input type="text" name="phone" class="form-control" placeholder="رقم الجوال" required></div>
              <div class="mb-3"><input type="text" name="nid" class="form-control" placeholder="رقم الهوية (اختياري)"></div>
          </div>
          <div class="modal-footer">
              <button type="submit" class="btn btn-primary">حفظ وتحليل</button>
          </div>
      </form>
    </div>
  </div>
</div>
