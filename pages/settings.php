<?php
if(isset($_POST['save_settings'])){
    check_csrf();
    $keys = ['company_name','cr_no','tax_no','vat_percent','address','phone','email', 'invoice_terms', 'currency', 'sms_enabled'];
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
        <button onclick="document.getElementById('settingsForm').submit()" class="btn btn-primary"><i class="fa-solid fa-save"></i> حفظ الكل</button>
    </div>

    <form id="settingsForm" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <input type="hidden" name="save_settings" value="1">

        <h4 style="color:var(--primary); margin-bottom:15px">1. هوية المنشأة</h4>
        <div class="inp-grid">
            <div><label class="inp-label">اسم المنشأة</label><input type="text" name="company_name" class="inp" value="<?= $sets['company_name']??'' ?>"></div>
            <div><label class="inp-label">السجل التجاري</label><input type="text" name="cr_no" class="inp" value="<?= $sets['cr_no']??'' ?>"></div>
            <div><label class="inp-label">الرقم الضريبي</label><input type="text" name="tax_no" class="inp" value="<?= $sets['tax_no']??'' ?>"></div>
            <div><label class="inp-label">نسبة الضريبة (%)</label><input type="number" name="vat_percent" class="inp" value="<?= $sets['vat_percent']??'15' ?>"></div>
        </div>

        <h4 style="color:var(--primary); margin:30px 0 15px">2. إعدادات الفواتير والعقود</h4>
        <div class="inp-grid">
            <div><label class="inp-label">العملة</label><input type="text" name="currency" class="inp" value="<?= $sets['currency']??'SAR' ?>"></div>
            <div><label class="inp-label">تفعيل رسائل SMS</label>
                <select name="sms_enabled" class="inp">
                    <option value="1" <?= ($sets['sms_enabled']??0)==1?'selected':'' ?>>مفعل</option>
                    <option value="0" <?= ($sets['sms_enabled']??0)==0?'selected':'' ?>>معطل</option>
                </select>
            </div>
            <div style="grid-column:span 2"><label class="inp-label">شروط الفاتورة (تظهر أسفل الفاتورة)</label><textarea name="invoice_terms" class="inp" rows="3"><?= $sets['invoice_terms']??'' ?></textarea></div>
        </div>

        <h4 style="color:var(--primary); margin:30px 0 15px">3. الشعار</h4>
        <div style="background:#151515; padding:20px; border-radius:15px; display:flex; align-items:center; gap:30px; border:1px solid #333">
            <img src="<?= $logo_src ?>" style="width:80px; height:80px; object-fit:contain; border-radius:50%; background:black; border:1px solid #333">
            <div style="flex:1">
                <label class="inp-label">رفع شعار جديد</label>
                <input type="file" name="logo" class="inp">
            </div>
        </div>
    </form>
</div>
