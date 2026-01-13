<?php
$id = $_GET['id'];
$t = $pdo->query("SELECT * FROM tenants WHERE id=$id")->fetch();

// إحصائيات المستأجر
$active_contracts = $pdo->query("SELECT COUNT(*) FROM contracts WHERE tenant_id=$id AND status='active'")->fetchColumn();
$total_paid = $pdo->query("SELECT COALESCE(SUM(paid_amount),0) FROM contracts c JOIN payments p ON c.id=p.contract_id WHERE c.tenant_id=$id")->fetchColumn();
$total_due = $pdo->query("SELECT COALESCE(SUM(amount),0) FROM contracts c JOIN payments p ON c.id=p.contract_id WHERE c.tenant_id=$id")->fetchColumn();
$remaining = $total_due - $total_paid;
?>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px">
    <a href="index.php?p=tenants" class="btn btn-dark"><i class="fa-solid fa-arrow-right"></i> رجوع</a>
    <button class="btn" style="background:#6366f1">تعديل البيانات <i class="fa-solid fa-pen"></i></button>
</div>

<div class="card" style="background:#10b981; border:none; color:white; padding:30px; margin-bottom:20px;">
    <div style="display:flex; justify-content:space-between; align-items:center;">
        <div style="display:flex; align-items:center; gap:20px;">
            <div style="width:80px; height:80px; background:rgba(255,255,255,0.25); border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:35px;">
                <i class="fa-solid fa-user"></i>
            </div>
            <div>
                <h2 style="margin:0; font-size:24px"><?= $t['name'] ?></h2>
                <div style="margin-top:5px; opacity:0.9">
                    <i class="fa-solid fa-phone"></i> <?= $t['phone'] ?> &nbsp;|&nbsp; 
                    <i class="fa-solid fa-id-card"></i> <?= $t['id_number'] ?>
                </div>
            </div>
        </div>
        <div style="display:flex; gap:10px;">
            <button class="btn" style="background:rgba(255,255,255,0.2); border:none">اتصال <i class="fa-solid fa-phone"></i></button>
            <button class="btn" style="background:rgba(255,255,255,0.2); border:none">واتساب <i class="fa-brands fa-whatsapp"></i></button>
        </div>
    </div>
</div>

<div style="display:grid; grid-template-columns: repeat(4, 1fr); gap:15px; margin-bottom:20px;">
    <div class="card" style="text-align:center; padding:20px; background:white; color:#000;">
        <div style="color:#6366f1; background:#e0e7ff; width:40px; height:40px; border-radius:10px; display:flex; align-items:center; justify-content:center; margin:0 auto 10px"><i class="fa-solid fa-file-contract"></i></div>
        <h3 style="margin:0; font-size:22px"><?= $active_contracts ?></h3>
        <span style="color:#888; font-size:12px">عقود نشطة</span>
    </div>
    
    <div class="card" style="text-align:center; padding:20px; background:white; color:#000;">
        <div style="color:#10b981; background:#dcfce7; width:40px; height:40px; border-radius:10px; display:flex; align-items:center; justify-content:center; margin:0 auto 10px"><i class="fa-solid fa-check"></i></div>
        <h3 style="margin:0; font-size:22px"><?= number_format($total_paid) ?></h3>
        <span style="color:#888; font-size:12px">إجمالي المدفوع</span>
    </div>

    <div class="card" style="text-align:center; padding:20px; background:white; color:#000;">
        <div style="color:#f59e0b; background:#fef3c7; width:40px; height:40px; border-radius:10px; display:flex; align-items:center; justify-content:center; margin:0 auto 10px"><i class="fa-solid fa-coins"></i></div>
        <h3 style="margin:0; font-size:22px"><?= number_format($remaining) ?></h3>
        <span style="color:#888; font-size:12px">الرصيد المتبقي</span>
    </div>
    
    <div class="card" style="text-align:center; padding:20px; background:white; color:#000;">
        <div style="color:#ef4444; background:#fee2e2; width:40px; height:40px; border-radius:10px; display:flex; align-items:center; justify-content:center; margin:0 auto 10px"><i class="fa-solid fa-triangle-exclamation"></i></div>
        <h3 style="margin:0; font-size:22px">1</h3>
        <span style="color:#888; font-size:12px">دفعات متأخرة</span>
    </div>
</div>

<div class="card" style="background:white; color:black;">
    <h4 style="color:#6366f1; border-bottom:1px solid #eee; padding-bottom:10px; margin-bottom:20px"><i class="fa-solid fa-user"></i> البيانات الشخصية</h4>
    
    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;">
        <div style="display:flex; justify-content:space-between; border-bottom:1px solid #f3f4f6; padding-bottom:10px;">
            <span style="color:#888">الاسم الكامل</span>
            <span style="font-weight:bold"><?= $t['name'] ?></span>
        </div>
        <div style="display:flex; justify-content:space-between; border-bottom:1px solid #f3f4f6; padding-bottom:10px;">
            <span style="color:#888">رقم الهاتف</span>
            <span style="font-weight:bold"><?= $t['phone'] ?></span>
        </div>
        <div style="display:flex; justify-content:space-between; border-bottom:1px solid #f3f4f6; padding-bottom:10px;">
            <span style="color:#888">البريد الإلكتروني</span>
            <span style="font-weight:bold">user@example.com</span>
        </div>
        <div style="display:flex; justify-content:space-between; border-bottom:1px solid #f3f4f6; padding-bottom:10px;">
            <span style="color:#888">رقم الهوية</span>
            <span style="font-weight:bold"><?= $t['id_number'] ?></span>
        </div>
    </div>
</div>
