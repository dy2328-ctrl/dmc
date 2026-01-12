<?php
// cron_alerts.php - يعمل في الخلفية يومياً
require 'SmartSystem.php';

// 1. البحث عن الدفعات المستحقة غداً
$tomorrow = date('Y-m-d', strtotime('+1 day'));
$stmt = $pdo->prepare("SELECT p.*, t.full_name, t.phone, u.unit_name 
                       FROM payments p 
                       JOIN contracts c ON p.contract_id = c.id
                       JOIN tenants t ON c.tenant_id = t.id
                       JOIN units u ON c.unit_id = u.id
                       WHERE p.due_date = ? AND p.status = 'pending'");
$stmt->execute([$tomorrow]);

while ($row = $stmt->fetch()) {
    $msg = "مرحباً {$row['full_name']}، نود تذكيرك بأن إيجار الوحدة ({$row['unit_name']}) بقيمة {$row['amount']} ريال يستحق السداد غداً.";
    
    // إرسال واتساب تلقائي
    $AI->sendWhatsApp($row['phone'], $msg);
    
    echo "Sent alert to {$row['full_name']}<br>";
}

// 2. تحديث حالة العقود المنتهية
$pdo->exec("UPDATE contracts SET status='expired' WHERE end_date < CURRENT_DATE AND status='active'");

echo "تم تنفيذ المهام اليومية بنجاح.";
?>
