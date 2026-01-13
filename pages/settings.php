<?php
if(isset($_POST['save_settings'])){
    check_csrf();
    // تم حذف sms_enabled من القائمة
    $keys = ['company_name','cr_no','tax_no','vat_percent','address','phone','email', 'invoice_terms', 'currency'];
    foreach($keys as $k){ if(isset($_POST[$k])) { $pdo->prepare("REPLACE INTO settings (k,v) VALUES (?,?)")->execute([$k, $_POST[$k]]); } }
    
    if(!empty($_FILES['logo']['name'])){
        $path = upload($_FILES['logo']);
        $pdo->prepare("REPLACE INTO settings (k,v) VALUES ('logo',?)")->execute([$path]);
    }
    echo "<script>alert('تم الحفظ'); window.location='index.php?p=settings';</script>";
}
// جلب القيم
$sets=[]; $q=$pdo->query("SELECT * FROM settings"); while($r=$q->fetch()) $sets[$r['k']]=$r['v'];
?>

<div class="card">
    <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid #222; padding-bottom:20px; margin-bottom:30px">
        <h2>⚙️ إعدادات النظام</h2>
        <button onclick="document.getElementById('settingsForm').submit()" class="btn btn-primary">
            <i class="fa-solid fa-save"></i> حفظ كافة التغييرات
        </button>
    </div>

    <form id="settingsForm" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <input type="hidden" name="save_settings" value="1">

        <h4 style="color:var(--primary); margin-bottom:15px; border-bottom:1px dashed #333; padding-bottom:10px">1. بيانات الهوية والضريبة</h4>
        <div class="inp-grid">
            <div><label class="inp-label">اسم المنشأة</label><input type="text" name="company_name" class="inp" value="<?= $sets['company_name']??'' ?>"></div>
            <div><label class="inp-label">السجل التجاري</label><input type="text" name="cr_no" class="inp" value="<?= $sets['cr_no']??'' ?>"></div>
            <div><label class="inp-label">الرقم الضريبي</label><input type="text" name="tax_no" class="inp" value="<?= $sets['tax_no']??'' ?>"></div>
            <div><label class="inp-label">نسبة الضريبة (%)</label><input type="number" name="vat_percent" class="inp" value="<?= $sets['vat_percent']??'15' ?>"></div>
        </div>

        <h4 style="color:var(--primary); margin:30px 0 15px; border-bottom:1px dashed #333; padding-bottom:10px">2. بيانات التواصل (للفواتير)</h4>
        <div class="inp-grid">
            <div><label class="inp-label">العنوان الوطني</label><input type="text" name="address" class="inp" value="<?= $sets['address']??'' ?>"></div>
            <div><label class="inp-label">رقم الهاتف</label><input type="text" name="phone" class="inp" value="<?= $sets['phone']??'' ?>"></div>
            <div style="grid-column:span 2"><label class="inp-label">البريد الإلكتروني</label><input type="email" name="email" class="inp" value="<?= $sets['email']??'' ?>"></div>
        </div>

        <h4 style="color:var(--primary); margin:30px 0 15px; border-bottom:1px dashed #333; padding-bottom:10px">3. إعدادات مالية</h4>
        <div class="inp-grid">
            <div><label class="inp-label">العملة</label><input type="text" name="currency" class="inp" value="<?= $sets['currency']??'SAR' ?>"></div>
            <div><label class="inp-label">تغيير الشعار</label><input type="file" name="logo" class="inp" style="padding:15px"></div>
            <div style="grid-column:span 2">
                <label class="inp-label">شروط وأحكام الفاتورة</label>
                <textarea name="invoice_terms" class="inp" rows="3" placeholder="مثال: المبالغ المحولة لا ترد..."><?= $sets['invoice_terms']??'' ?></textarea>
            </div>
        </div>
    </form>
</div>
