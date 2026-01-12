<?php
// install.php - Gemini Ultimate Pro Max
require 'db.php';

echo "<body style='background:#050505; color:#fff; font-family:tahoma; padding:40px; direction:rtl; text-align:center'>";
echo "<h2>💎 جاري ترقية النظام للمواصفات الكاملة...</h2>";

$sqls = [
    // المستخدمين
    "CREATE TABLE IF NOT EXISTS users (id INT AUTO_INCREMENT PRIMARY KEY, username VARCHAR(50), password VARCHAR(255), full_name VARCHAR(100), phone VARCHAR(20), role VARCHAR(20), photo VARCHAR(255), created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)",
    // الإعدادات (تخزين مرن)
    "CREATE TABLE IF NOT EXISTS settings (k VARCHAR(50) PRIMARY KEY, v LONGTEXT)",
    // سجل النشاطات (للداشبورد)
    "CREATE TABLE IF NOT EXISTS activity_log (id INT AUTO_INCREMENT PRIMARY KEY, description VARCHAR(255), type VARCHAR(50), created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)",
    // العقارات
    "CREATE TABLE IF NOT EXISTS properties (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(255), type VARCHAR(100), address TEXT, manager_name VARCHAR(100), manager_phone VARCHAR(50), photo VARCHAR(255))",
    // الوحدات
    "CREATE TABLE IF NOT EXISTS units (id INT AUTO_INCREMENT PRIMARY KEY, property_id INT, unit_name VARCHAR(100), type VARCHAR(50), yearly_price DECIMAL(15,2), elec_meter_no VARCHAR(50), water_meter_no VARCHAR(50), status VARCHAR(50) DEFAULT 'available', notes TEXT, photo VARCHAR(255), FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE)",
    // المستأجرين (بيانات كاملة حسب الصورة)
    "CREATE TABLE IF NOT EXISTS tenants (id INT AUTO_INCREMENT PRIMARY KEY, full_name VARCHAR(255), phone VARCHAR(20), email VARCHAR(100), id_number VARCHAR(50), id_type VARCHAR(50), cr_number VARCHAR(50), address TEXT, id_photo VARCHAR(255), personal_photo VARCHAR(255), created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)",
    // العقود (مع الخدمات)
    "CREATE TABLE IF NOT EXISTS contracts (id INT AUTO_INCREMENT PRIMARY KEY, tenant_id INT, unit_id INT, start_date DATE, end_date DATE, total_amount DECIMAL(15,2), services_fee DECIMAL(15,2) DEFAULT 0, payment_cycle VARCHAR(50), signature_img LONGTEXT, status VARCHAR(20) DEFAULT 'active', notes TEXT, attachment VARCHAR(255), created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (tenant_id) REFERENCES tenants(id), FOREIGN KEY (unit_id) REFERENCES units(id))",
    // الدفعات
    "CREATE TABLE IF NOT EXISTS payments (id INT AUTO_INCREMENT PRIMARY KEY, contract_id INT, title VARCHAR(100), amount DECIMAL(15,2), paid_amount DECIMAL(15,2) DEFAULT 0, due_date DATE, status VARCHAR(20) DEFAULT 'pending', paid_date DATE, FOREIGN KEY (contract_id) REFERENCES contracts(id) ON DELETE CASCADE)",
    // المعاملات المالية
    "CREATE TABLE IF NOT EXISTS transactions (id INT AUTO_INCREMENT PRIMARY KEY, payment_id INT, amount_paid DECIMAL(15,2), payment_method VARCHAR(50), transaction_date DATE, notes TEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE CASCADE)",
    // الصيانة
    "CREATE TABLE IF NOT EXISTS maintenance (id INT AUTO_INCREMENT PRIMARY KEY, property_id INT, unit_id INT, vendor_id INT, description TEXT, cost DECIMAL(15,2), status VARCHAR(20) DEFAULT 'pending', request_date DATE, FOREIGN KEY (property_id) REFERENCES properties(id))",
    // الموردين
    "CREATE TABLE IF NOT EXISTS vendors (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100), service_type VARCHAR(50), phone VARCHAR(20), email VARCHAR(100))"
];

try {
    foreach($sqls as $s) $pdo->exec($s);
    
    // إعدادات افتراضية (حسب الصور)
    $defaults = [
        'company_name'=>'دار الميار للمقاولات', 'vat_no'=>'3000000000', 'cr_no'=>'1010101010', 
        'vat_enabled'=>'1', 'vat_percent'=>'15', 'currency'=>'SAR', 
        'alert_before'=>'30', 'invoice_terms'=>'المبالغ المدفوعة غير مستردة'
    ];
    foreach($defaults as $k=>$v) $pdo->prepare("INSERT IGNORE INTO settings (k,v) VALUES (?,?)")->execute([$k,$v]);

    // Admin
    if($pdo->query("SELECT count(*) FROM users")->fetchColumn() == 0){
        $pdo->prepare("INSERT INTO users (username, password, full_name, role) VALUES (?,?,?,?)")->execute(['admin', password_hash('123456', PASSWORD_DEFAULT), 'مدير النظام', 'admin']);
    }

    echo "<h1 style='color:#10b981'>✅ تم تحديث النظام بنجاح</h1><a href='index.php' style='color:#fff; text-decoration:underline'>الدخول للنظام</a>";
} catch(PDOException $e){ die("Error: ".$e->getMessage()); }
?>
