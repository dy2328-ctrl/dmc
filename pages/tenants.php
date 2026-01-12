<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_tenant'])) {
    check_csrf();
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $id_num = $_POST['id_number'];
    $ai_data = null;

    if (!empty($_FILES['id_photo']['tmp_name'])) {
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
        $AI->sendWhatsApp($phone, "تم تسجيلك بنجاح في نظام دار الميار.");
        echo "<script>window.location='index.php?p=tenants';</script>";
    } catch(Exception $e) { echo "<p style='color:red'>خطأ: ".$e->getMessage()."</p>"; }
}
?>

<div class="card">
    <div style="display:flex; justify-content:space-between; margin-bottom:20px;">
        <h3 style="margin:0">👥 إدارة المستأجرين</h3>
        <button onclick="document.getElementById('addTenantModal').style.display='flex'" class="btn">
            <i class="fa-solid fa-magic"></i> إضافة ذكية
        </button>
    </div>
    
    <table>
        <thead><tr><th>الاسم</th><th>الجوال</th><th>الهوية</th><th>الذكاء</th><th>إجراء</th></tr></thead>
        <tbody>
            <?php 
            $ts=$pdo->query("SELECT * FROM tenants ORDER BY id DESC"); 
            while($t=$ts->fetch()): 
                $ai = !empty($t['document_data']);
            ?>
            <tr>
                <td><?= htmlspecialchars($t['name']) ?></td>
                <td><?= htmlspecialchars($t['phone']) ?></td>
                <td><?= htmlspecialchars($t['id_number']) ?></td>
                <td>
                    <?php if($ai): ?><span class="badge bg-success">AI Verified</span>
                    <?php else: ?><span class="badge bg-secondary">يدوي</span><?php endif; ?>
                </td>
                <td><a href="#" class="btn" style="padding:5px 15px; font-size:12px">عرض</a></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<div id="addTenantModal" class="modal">
    <div class="modal-dialog">
        <div class="modal-content">
            <button class="btn-close" onclick="document.getElementById('addTenantModal').style.display='none'">✕</button>
            <h2 style="margin-top:0">إضافة مستأجر (AI)</h2>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="add_tenant" value="1">
                
                <div style="background:#222; padding:15px; border-radius:10px; margin-bottom:20px; color:#10b981; font-size:13px">
                    <i class="fa-solid fa-robot"></i> قم برفع الهوية وسيقوم النظام بتعبئة البيانات.
                </div>
                
                <label>صورة الهوية</label>
                <input type="file" name="id_photo" class="inp">
                
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px">
                    <input type="text" name="name" class="inp" placeholder="الاسم (اختياري)">
                    <input type="text" name="phone" class="inp" placeholder="الجوال" required>
                </div>
                <input type="text" name="id_number" class="inp" placeholder="رقم الهوية">
                
                <button class="btn btn-green" style="width:100%; margin-top:10px">حفظ وتحليل</button>
            </form>
        </div>
    </div>
</div>
