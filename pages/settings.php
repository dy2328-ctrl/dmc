<?php
if(isset($_POST['save_settings'])){
    check_csrf();
    $keys = ['company_name','cr_no','tax_no','vat_percent','address','phone','email'];
    foreach($keys as $k){ if(isset($_POST[$k])) { $pdo->prepare("REPLACE INTO settings (k,v) VALUES (?,?)")->execute([$k, $_POST[$k]]); } }
    
    if(!empty($_FILES['logo']['name'])){
        $path = upload($_FILES['logo']);
        $pdo->prepare("REPLACE INTO settings (k,v) VALUES ('logo',?)")->execute([$path]);
    }
    echo "<script>alert('تم حفظ الإعدادات بنجاح'); window.location='index.php?p=settings';</script>";
}
// جلب القيم
$sets=[]; $q=$pdo->query("SELECT * FROM settings"); while($r=$q->fetch()) $sets[$r['k']]=$r['v'];
?>

<div class="card">
    <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid #222; padding-bottom:20px; margin-bottom:30px">
        <h2>⚙️ إعدادات المنشأة والنظام</h2>
        <button onclick="document.getElementById('settingsForm').submit()" class="btn btn-primary"><i class="fa-solid fa-save"></i> حفظ التغييرات</button>
    </div>

    <form id="settingsForm" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <input type="hidden" name="save_settings" value="1">

        <h4 style="color:var(--primary); margin-bottom:20px">1. البيانات الأساسية</h4>
        <div class="inp-grid">
            <div><label class="inp-label">اسم المنشأة</label><input type="text" name="company_name" class="inp" value="<?= $sets['company_name']??'' ?>"></div>
            <div><label class="inp-label">رقم السجل التجاري</label><input type="text" name="cr_no" class="inp" value="<?= $sets['cr_no']??'' ?>"></div>
            <div><label class="inp-label">الرقم الضريبي</label><input type="text" name="tax_no" class="inp" value="<?= $sets['tax_no']??'' ?>"></div>
            <div><label class="inp-label">نسبة الضريبة (%)</label><input type="number" name="vat_percent" class="inp" value="<?= $sets['vat_percent']??'15' ?>"></div>
        </div>

        <h4 style="color:var(--primary); margin:30px 0 20px">2. بيانات التواصل (تظهر في الفاتورة)</h4>
        <div class="inp-grid">
            <div><label class="inp-label">العنوان الوطني</label><input type="text" name="address" class="inp" value="<?= $sets['address']??'' ?>"></div>
            <div><label class="inp-label">رقم الهاتف</label><input type="text" name="phone" class="inp" value="<?= $sets['phone']??'' ?>"></div>
            <div style="grid-column:span 2"><label class="inp-label">البريد الإلكتروني</label><input type="email" name="email" class="inp" value="<?= $sets['email']??'' ?>"></div>
        </div>

        <h4 style="color:var(--primary); margin:30px 0 20px">3. المظهر والشعار</h4>
        <div style="background:#151515; padding:20px; border-radius:15px; display:flex; align-items:center; gap:30px; border:1px solid #333">
            <img src="<?= $logo_src ?>" style="width:100px; height:100px; object-fit:contain; background:black; border-radius:50%; border:1px solid #333">
            <div style="flex:1">
                <label class="inp-label">تحديث الشعار (يفضل PNG شفاف)</label>
                <input type="file" name="logo" class="inp">
            </div>
        </div>
    </form>
</div>
