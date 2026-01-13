<?php
$id = $_GET['id'];
$t = $pdo->query("SELECT * FROM tenants WHERE id=$id")->fetch();
// حساب الإحصائيات الحقيقية
$contracts_active = $pdo->query("SELECT COUNT(*) FROM contracts WHERE tenant_id=$id AND status='active'")->fetchColumn();
$total_paid = $pdo->query("SELECT COALESCE(SUM(paid_amount),0) FROM contracts c JOIN payments p ON c.id=p.contract_id WHERE c.tenant_id=$id")->fetchColumn();
$total_due = $pdo->query("SELECT COALESCE(SUM(amount),0) FROM contracts c JOIN payments p ON c.id=p.contract_id WHERE c.tenant_id=$id")->fetchColumn();
$remaining = $total_due - $total_paid;
?>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px">
    <h2 style="font-weight:800">بيانات المستأجر</h2>
    <a href="index.php?p=tenants" class="btn btn-dark">رجوع <i class="fa-solid fa-arrow-left"></i></a>
</div>

<div class="card" style="background:#10b981; border:none; color:white; display:flex; justify-content:space-between; align-items:center; padding:30px;">
    <div style="display:flex; align-items:center; gap:20px;">
        <div style="width:70px; height:70px; background:rgba(255,255,255,0.2); border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:30px">
            <i class="fa-solid fa-user"></i>
        </div>
        <div>
            <h2 style="margin:0"><?= $t['name'] ?></h2>
            <div style="opacity:0.9; margin-top:5px">
                <i class="fa-solid fa-phone"></i> <?= $t['phone'] ?> &nbsp;|&nbsp; 
                <i class="fa-solid fa-id-card"></i> <?= $t['id_number'] ?>
            </div>
        </div>
    </div>
    <div style="display:flex; gap:10px">
        <button class="btn" style="background:rgba(255,255,255,0.2); border:none"><i class="fa-solid fa-phone"></i> اتصال</button>
        <button class="btn" style="background:rgba(255,255,255,0.2); border:none"><i class="fa-brands fa-whatsapp"></i> واتساب</button>
    </div>
</div>

<div style="display:grid; grid-template-columns: repeat(4, 1fr); gap:20px; margin-bottom:30px;">
    <div class="card" style="text-align:center; padding:20px">
        <div style="color:#6366f1; font-size:24px; margin-bottom:10px"><i class="fa-solid fa-file-contract"></i></div>
        <h3 style="margin:0"><?= $contracts_active ?></h3>
        <span style="color:#888; font-size:12px">عقود نشطة</span>
    </div>
    <div class="card" style="text-align:center; padding:20px">
        <div style="color:#10b981; font-size:24px; margin-bottom:10px"><i class="fa-solid fa-check-circle"></i></div>
        <h3 style="margin:0"><?= $contracts_active ?></h3>
        <span style="color:#888; font-size:12px">إجمالي العقود</span>
    </div>
    <div class="card" style="text-align:center; padding:20px">
        <div style="color:#f59e0b; font-size:24px; margin-bottom:10px"><i class="fa-solid fa-coins"></i></div>
        <h3 style="margin:0"><?= number_format($total_paid) ?></h3>
        <span style="color:#888; font-size:12px">إجمالي المدفوع</span>
    </div>
    <div class="card" style="text-align:center; padding:20px">
        <div style="color:#ef4444; font-size:24px; margin-bottom:10px"><i class="fa-solid fa-clock"></i></div>
        <h3 style="margin:0"><?= number_format($remaining) ?></h3>
        <span style="color:#888; font-size:12px">الرصيد المتبقي</span>
    </div>
</div>

<div class="card">
    <h4 style="border-bottom:1px solid #333; padding-bottom:15px; margin-bottom:20px"><i class="fa-solid fa-user"></i> البيانات الشخصية</h4>
    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;">
        <div style="display:flex; justify-content:space-between; border-bottom:1px dashed #333; padding-bottom:10px">
            <span style="color:#888">الاسم</span>
            <span><?= $t['name'] ?></span>
        </div>
        <div style="display:flex; justify-content:space-between; border-bottom:1px dashed #333; padding-bottom:10px">
            <span style="color:#888">الهاتف</span>
            <span><?= $t['phone'] ?></span>
        </div>
        <div style="display:flex; justify-content:space-between; border-bottom:1px dashed #333; padding-bottom:10px">
            <span style="color:#888">رقم الهوية</span>
            <span><?= $t['id_number'] ?></span>
        </div>
        <div style="display:flex; justify-content:space-between; border-bottom:1px dashed #333; padding-bottom:10px">
            <span style="color:#888">تاريخ التسجيل</span>
            <span><?= date('Y-m-d') ?></span>
        </div>
    </div>
</div>
