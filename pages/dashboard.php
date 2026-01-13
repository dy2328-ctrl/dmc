<?php
// جلب البيانات مع حماية من القيم الفارغة
$contracts_count = $pdo->query("SELECT COUNT(*) FROM contracts")->fetchColumn() ?: 0;
$units_count = $pdo->query("SELECT COUNT(*) FROM units")->fetchColumn() ?: 0;
$maintenance_hours = 10; // قيمة افتراضية أو يمكن حسابها لاحقاً
$total_units = $pdo->query("SELECT COUNT(*) FROM units")->fetchColumn() ?: 0;
$tenants_count = $pdo->query("SELECT COUNT(*) FROM tenants")->fetchColumn() ?: 0;
$income = $pdo->query("SELECT COALESCE(SUM(amount),0) FROM payments WHERE status='paid'")->fetchColumn();
$late_payments = $pdo->query("SELECT COUNT(*) FROM payments WHERE status='pending' AND due_date < CURRENT_DATE")->fetchColumn() ?: 0;
?>

<div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap:20px; margin-bottom:30px;">
    
    <div class="card" style="padding:20px; display:flex; justify-content:space-between; align-items:center;">
        <div>
            <h2 style="margin:0; font-size:28px; font-weight:800"><?= $contracts_count ?></h2>
            <span style="color:#888; font-size:14px">عقود نشطة</span>
        </div>
        <div style="width:50px; height:50px; background:#6366f1; border-radius:12px; display:flex; align-items:center; justify-content:center; color:white; font-size:24px;">
            <i class="fa-solid fa-file-contract"></i>
        </div>
    </div>

    <div class="card" style="padding:20px; display:flex; justify-content:space-between; align-items:center;">
        <div>
            <h2 style="margin:0; font-size:28px; font-weight:800"><?= $units_count ?></h2>
            <span style="color:#888; font-size:14px">وحدات مؤجرة</span>
        </div>
        <div style="width:50px; height:50px; background:#0ea5e9; border-radius:12px; display:flex; align-items:center; justify-content:center; color:white; font-size:24px;">
            <i class="fa-solid fa-key"></i>
        </div>
    </div>

    <div class="card" style="padding:20px; display:flex; justify-content:space-between; align-items:center;">
        <div>
            <h2 style="margin:0; font-size:28px; font-weight:800"><?= $maintenance_hours ?></h2>
            <span style="color:#888; font-size:14px">ساعات صيانة</span>
            <span style="display:block; font-size:11px; color:#10b981">✔ مكتملة بنجاح</span>
        </div>
        <div style="width:50px; height:50px; background:#10b981; border-radius:12px; display:flex; align-items:center; justify-content:center; color:white; font-size:24px;">
            <i class="fa-solid fa-check-circle"></i>
        </div>
    </div>

    <div class="card" style="padding:20px; display:flex; justify-content:space-between; align-items:center;">
        <div>
            <h2 style="margin:0; font-size:28px; font-weight:800"><?= $total_units ?></h2>
            <span style="color:#888; font-size:14px">إجمالي الوحدات</span>
        </div>
        <div style="width:50px; height:50px; background:#4f46e5; border-radius:12px; display:flex; align-items:center; justify-content:center; color:white; font-size:24px;">
            <i class="fa-solid fa-building"></i>
        </div>
    </div>
</div>

<div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap:20px; margin-bottom:30px;">
    <div class="card" style="padding:20px; display:flex; justify-content:space-between; align-items:center;">
        <div>
            <h2 style="margin:0; font-size:28px; font-weight:800"><?= number_format($income) ?></h2>
            <span style="color:#888; font-size:14px">إيرادات الشهر</span>
            <span style="display:block; font-size:11px; color:#10b981">✔ محصلة بالكامل</span>
        </div>
        <div style="width:50px; height:50px; background:#10b981; border-radius:12px; display:flex; align-items:center; justify-content:center; color:white; font-size:24px;">
            <i class="fa-solid fa-wallet"></i>
        </div>
    </div>

    <div class="card" style="padding:20px; display:flex; justify-content:space-between; align-items:center;">
        <div>
            <h2 style="margin:0; font-size:28px; font-weight:800"><?= $late_payments ?></h2>
            <span style="color:#888; font-size:14px">مطالبات متأخرة</span>
            <span style="display:block; font-size:11px; color:#ef4444">⚠ تحتاج متابعة</span>
        </div>
        <div style="width:50px; height:50px; background:#ef4444; border-radius:12px; display:flex; align-items:center; justify-content:center; color:white; font-size:24px;">
            <i class="fa-solid fa-triangle-exclamation"></i>
        </div>
    </div>

    <div class="card" style="padding:20px; display:flex; justify-content:space-between; align-items:center;">
        <div>
            <h2 style="margin:0; font-size:28px; font-weight:800"><?= $tenants_count ?></h2>
            <span style="color:#888; font-size:14px">المستأجرين</span>
        </div>
        <div style="width:50px; height:50px; background:#f59e0b; border-radius:12px; display:flex; align-items:center; justify-content:center; color:white; font-size:24px;">
            <i class="fa-solid fa-users"></i>
        </div>
    </div>
</div>

<div style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap:20px;">
    <div class="card">
        <div style="border-bottom:1px solid #333; padding-bottom:10px; margin-bottom:15px; display:flex; justify-content:space-between;">
            <h4 style="margin:0"><i class="fa-solid fa-clock-rotate-left"></i> آخر النشاطات</h4>
        </div>
        <div style="font-size:13px; color:#aaa; text-align:center; padding:20px;">
            لا توجد نشاطات حديثة لعرضها
        </div>
    </div>

    <div class="card">
        <div style="border-bottom:1px solid #333; padding-bottom:10px; margin-bottom:15px; display:flex; justify-content:space-between;">
            <h4 style="margin:0"><i class="fa-solid fa-clock"></i> عقود تنتهي قريباً</h4>
            <span style="font-size:12px; background:#333; padding:2px 8px; border-radius:5px">عرض الكل</span>
        </div>
        <div style="text-align:center; padding:30px; color:#666">
            <i class="fa-solid fa-check-circle" style="font-size:40px; margin-bottom:10px; display:block"></i>
            لا توجد عقود تنتهي قريباً
        </div>
    </div>

    <div class="card">
        <div style="border-bottom:1px solid #333; padding-bottom:10px; margin-bottom:15px; display:flex; justify-content:space-between;">
            <h4 style="margin:0"><i class="fa-solid fa-calendar-days"></i> دفعات قادمة</h4>
            <span style="font-size:12px; background:#333; padding:2px 8px; border-radius:5px">عرض الكل</span>
        </div>
        <div style="text-align:center; padding:30px; color:#666">
            <i class="fa-solid fa-check-circle" style="font-size:40px; margin-bottom:10px; display:block"></i>
            لا توجد دفعات قادمة خلال 30 يوم
        </div>
    </div>
</div>
