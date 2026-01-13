<?php
// جلب الإحصائيات الحقيقية
$contracts_count = $pdo->query("SELECT COUNT(*) FROM contracts WHERE status='active'")->fetchColumn() ?: 0;
$units_count = $pdo->query("SELECT COUNT(*) FROM units")->fetchColumn() ?: 0;
$rented_units = $pdo->query("SELECT COUNT(*) FROM units WHERE status='rented'")->fetchColumn() ?: 0;
$tenants_count = $pdo->query("SELECT COUNT(*) FROM tenants")->fetchColumn() ?: 0;

// جلب القوائم السفلية (العقود المنتهية، والدفعات القادمة)
$ending_soon = $pdo->query("SELECT c.*, t.name as tenant_name FROM contracts c JOIN tenants t ON c.tenant_id=t.id WHERE c.end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) LIMIT 5")->fetchAll();
$upcoming_payments = $pdo->query("SELECT p.*, c.id as contract_id FROM payments p JOIN contracts c ON p.contract_id=c.id WHERE p.due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) AND p.status='pending' LIMIT 5")->fetchAll();
?>

<div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap:20px; margin-bottom:30px;">
    
    <div class="card" style="padding:20px; display:flex; justify-content:space-between; align-items:center;">
        <div>
            <h2 style="margin:0; font-size:28px; font-weight:800"><?= $units_count ?></h2>
            <span style="color:#888; font-size:13px">إجمالي الوحدات</span>
        </div>
        <div style="width:50px; height:50px; background:#4f46e5; border-radius:12px; display:flex; align-items:center; justify-content:center; color:white; font-size:24px;">
            <i class="fa-solid fa-building"></i>
        </div>
    </div>

    <div class="card" style="padding:20px; display:flex; justify-content:space-between; align-items:center;">
        <div>
            <h2 style="margin:0; font-size:28px; font-weight:800"><?= $rented_units ?></h2>
            <span style="color:#888; font-size:13px">وحدات مؤجرة</span>
        </div>
        <div style="width:50px; height:50px; background:#0ea5e9; border-radius:12px; display:flex; align-items:center; justify-content:center; color:white; font-size:24px;">
            <i class="fa-solid fa-key"></i>
        </div>
    </div>

    <div class="card" style="padding:20px; display:flex; justify-content:space-between; align-items:center;">
        <div>
            <h2 style="margin:0; font-size:28px; font-weight:800"><?= $contracts_count ?></h2>
            <span style="color:#888; font-size:13px">عقود نشطة</span>
        </div>
        <div style="width:50px; height:50px; background:#10b981; border-radius:12px; display:flex; align-items:center; justify-content:center; color:white; font-size:24px;">
            <i class="fa-solid fa-check-circle"></i>
        </div>
    </div>

    <div class="card" style="padding:20px; display:flex; justify-content:space-between; align-items:center;">
        <div>
            <h2 style="margin:0; font-size:28px; font-weight:800"><?= $tenants_count ?></h2>
            <span style="color:#888; font-size:13px">المستأجرين</span>
        </div>
        <div style="width:50px; height:50px; background:#f59e0b; border-radius:12px; display:flex; align-items:center; justify-content:center; color:white; font-size:24px;">
            <i class="fa-solid fa-users"></i>
        </div>
    </div>
</div>

<div style="display:grid; grid-template-columns: repeat(3, 1fr); gap:20px;">
    
    <div class="card">
        <div style="display:flex; justify-content:space-between; margin-bottom:15px; border-bottom:1px solid #333; padding-bottom:10px;">
            <h4 style="margin:0; color:#6366f1"><i class="fa-solid fa-clock-rotate-left"></i> آخر النشاطات</h4>
        </div>
        <div style="font-size:13px; color:#aaa;">
            <div style="padding:10px; border-bottom:1px dashed #333">
                <i class="fa-solid fa-circle-check" style="color:#10b981"></i> تسجيل دخول ناجح للنظام
                <div style="font-size:11px; color:#666; margin-top:2px"><?= date('Y-m-d H:i') ?></div>
            </div>
            <div style="padding:10px; border-bottom:1px dashed #333">
                <i class="fa-solid fa-file-signature" style="color:#f59e0b"></i> تم تحديث حالة العقد #23
                <div style="font-size:11px; color:#666; margin-top:2px"><?= date('Y-m-d H:i', strtotime('-2 hours')) ?></div>
            </div>
        </div>
    </div>

    <div class="card">
        <div style="display:flex; justify-content:space-between; margin-bottom:15px; border-bottom:1px solid #333; padding-bottom:10px;">
            <h4 style="margin:0; color:#6366f1"><i class="fa-solid fa-clock"></i> عقود تنتهي قريباً</h4>
            <span style="font-size:11px; background:#333; padding:2px 8px; border-radius:4px">عرض الكل</span>
        </div>
        
        <?php if(empty($ending_soon)): ?>
            <div style="text-align:center; padding:30px; color:#666">
                <i class="fa-solid fa-check-circle" style="font-size:30px; margin-bottom:10px; display:block"></i>
                لا توجد عقود تنتهي قريباً
            </div>
        <?php else: ?>
            <?php foreach($ending_soon as $c): ?>
                <div style="padding:10px; border-bottom:1px dashed #333; display:flex; justify-content:space-between">
                    <span><?= $c['tenant_name'] ?></span>
                    <span style="color:#ef4444; font-size:12px"><?= $c['end_date'] ?></span>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="card">
        <div style="display:flex; justify-content:space-between; margin-bottom:15px; border-bottom:1px solid #333; padding-bottom:10px;">
            <h4 style="margin:0; color:#6366f1"><i class="fa-solid fa-calendar-days"></i> دفعات قادمة</h4>
            <span style="font-size:11px; background:#333; padding:2px 8px; border-radius:4px">عرض الكل</span>
        </div>

        <?php if(empty($upcoming_payments)): ?>
            <div style="text-align:center; padding:30px; color:#666">
                <i class="fa-solid fa-check-circle" style="font-size:30px; margin-bottom:10px; display:block"></i>
                لا توجد دفعات قادمة خلال 30 يوم
            </div>
        <?php else: ?>
            <?php foreach($upcoming_payments as $p): ?>
                <div style="padding:10px; border-bottom:1px dashed #333; display:flex; justify-content:space-between">
                    <span>دفعة عقد #<?= $p['contract_id'] ?></span>
                    <span style="color:#10b981; font-weight:bold"><?= number_format($p['amount']) ?></span>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
