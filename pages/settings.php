<?php
if(isset($_POST['save_settings'])){
    check_csrf();
    $keys = ['company_name','cr_no','vat_no','vat_percent','address','phone','email', 'invoice_terms', 'currency', 'bank_info'];
    foreach($keys as $k){ if(isset($_POST[$k])) { $pdo->prepare("REPLACE INTO settings (k,v) VALUES (?,?)")->execute([$k, $_POST[$k]]); } }
    
    if(!empty($_FILES['logo']['name'])){
        $path = upload($_FILES['logo']);
        $pdo->prepare("REPLACE INTO settings (k,v) VALUES ('logo',?)")->execute([$path]);
    }
    echo "<script>alert('تم حفظ الإعدادات'); window.location='index.php?p=settings';</script>";
}
$sets=[]; $q=$pdo->query("SELECT * FROM settings"); while($r=$q->fetch()) $sets[$r['k']]=$r['v'];
?>

<div class="card">
    <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid #222; padding-bottom:20px; margin-bottom:30px">
        <h2>⚙️ إعدادات النظام المتقدمة</h2>
        <button onclick="document.getElementById('settingsForm').submit()" class="btn btn-primary">
            <i class="fa-solid fa-save"></i> حفظ التغييرات
        </button>
    </div>

    <form id="settingsForm" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <input type="hidden" name="save_settings" value="1">

        <h4 style="color:var(--primary); margin-bottom:15px; border-bottom:1px dashed #333; padding-bottom:10px">1. هوية المنشأة</h4>
        <div style="display:flex; gap:30px; margin-bottom:30px">
            <div style="width:120px; text-align:center">
                <div style="width:100px; height:100px; background:#111; border:1px solid #333; border-radius:50%; margin-bottom:10px; overflow:hidden; display:flex; align-items:center; justify-content:center">
                    <img src="<?= $sets['logo'] ?? 'logo.png' ?>" style="max-width:100%; max-height:100%">
                </div>
                <input type="file" name="logo" id="logoUpload" style="display:none">
                <label for="logoUpload" class="btn btn-dark" style="padding:5px 10px; font-size:12px; cursor:pointer">رفع شعار</label>
            </div>
            <div style="flex:1" class="inp-grid">
                <div><label class="inp-label">اسم المنشأة</label><input type="text" name="company_name" class="inp" value="<?= $sets['company_name']??'' ?>"></div>
                <div><label class="inp-label">رقم السجل التجاري</label><input type="text" name="cr_no" class="inp" value="<?= $sets['cr_no']??'' ?>"></div>
                <div><label class="inp-label">رقم الجوال</label><input type="text" name="phone" class="inp" value="<?= $sets['phone']??'' ?>"></div>
                <div><label class="inp-label">العنوان الوطني</label><input type="text" name="address" class="inp" value="<?= $sets['address']??'' ?>"></div>
            </div>
        </div>

        <h4 style="color:var(--primary); margin-bottom:15px; border-bottom:1px dashed #333; padding-bottom:10px">2. بيانات الفاتورة الإلكترونية (ZATCA)</h4>
        <div class="inp-grid">
            <div><label class="inp-label">الرقم الضريبي (VAT ID)</label><input type="text" name="vat_no" class="inp" placeholder="3xxxxxxxxxxxxxx" value="<?= $sets['vat_no']??'' ?>"></div>
            <div><label class="inp-label">نسبة الضريبة (%)</label><input type="number" name="vat_percent" class="inp" value="<?= $sets['vat_percent']??'15' ?>"></div>
            <div><label class="inp-label">عملة النظام</label><input type="text" name="currency" class="inp" value="<?= $sets['currency']??'SAR' ?>"></div>
        </div>

        <h4 style="color:var(--primary); margin:30px 0 15px; border-bottom:1px dashed #333; padding-bottom:10px">3. تذييل الفاتورة والحسابات البنكية</h4>
        <div class="inp-group">
            <label class="inp-label">بيانات الحساب البنكي (تظهر في العقد والفاتورة)</label>
            <textarea name="bank_info" class="inp" rows="2" placeholder="اسم البنك: ... الايبان: ..."><?= $sets['bank_info']??'' ?></textarea>
        </div>
        <div class="inp-group">
            <label class="inp-label">شروط وأحكام (أسفل الفاتورة)</label>
            <textarea name="invoice_terms" class="inp" rows="3"><?= $sets['invoice_terms']??'' ?></textarea>
        </div>
    </form>
</div>
