<?php
if(isset($_POST['save_set'])){
    check_csrf();
    if(!empty($_FILES['logo_file']['name'])){
        $path = upload($_FILES['logo_file']);
        if($path) {
            $pdo->prepare("REPLACE INTO settings (k, v) VALUES ('logo', ?)")->execute([$path]);
        }
    }
    // يمكن إضافة المزيد من الإعدادات هنا
    echo "<script>alert('تم الحفظ'); window.location='index.php?p=settings';</script>";
}
?>
<div class="card" style="max-width:600px; margin:auto">
    <h3>⚙️ إعدادات النظام</h3>
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <input type="hidden" name="save_set" value="1">
        
        <div style="background:#222; padding:20px; border-radius:15px; text-align:center; margin-bottom:20px">
            <img src="<?= $logo_src ?>" style="height:80px; margin-bottom:10px">
            <p style="color:#888">الشعار الحالي</p>
        </div>
        
        <label>تغيير الشعار (PNG/JPG)</label>
        <input type="file" name="logo_file" class="inp">
        
        <button class="btn btn-green" style="width:100%">حفظ التغييرات</button>
    </form>
</div>
