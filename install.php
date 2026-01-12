<?php
// install.php - Gemini Ultimate v2
require 'db.php';

$sql = "
-- 1. المستخدمين (الموظفين والأدمن)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE, password VARCHAR(255),
    full_name VARCHAR(100), phone VARCHAR(20), email VARCHAR(100),
    role ENUM('admin','staff') DEFAULT 'staff',
    permissions TEXT, -- لتخزين الصلاحيات
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. الإعدادات العامة
CREATE TABLE IF NOT EXISTS settings (k VARCHAR(50) PRIMARY KEY, v LONGTEXT);

-- 3. العقارات
CREATE TABLE IF NOT EXISTS properties (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255), type VARCHAR(100), address TEXT, 
    manager_name VARCHAR(100), manager_phone VARCHAR(50)
);

-- 4. الوحدات (مع العدادات والأنواع)
CREATE TABLE IF NOT EXISTS units (
    id INT AUTO_INCREMENT PRIMARY KEY,
    property_id INT, unit_name VARCHAR(100), unit_number VARCHAR(50),
    type ENUM('apartment','shop','villa','land','office','warehouse') DEFAULT 'apartment',
    floor_number VARCHAR(50), yearly_price DECIMAL(15,2),
    elec_meter_no VARCHAR(50), water_meter_no VARCHAR(50),
    status ENUM('available','rented','maintenance') DEFAULT 'available',
    notes TEXT,
    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
);

-- 5. المستأجرين
CREATE TABLE IF NOT EXISTS tenants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(255), phone VARCHAR(20), email VARCHAR(100),
    id_type ENUM('national','iqama','commercial') DEFAULT 'national',
    id_number VARCHAR(50)
);

-- 6. العقود (مع التوقيع الإلكتروني)
CREATE TABLE IF NOT EXISTS contracts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT, unit_id INT, start_date DATE, end_date DATE,
    total_amount DECIMAL(15,2), payment_cycle ENUM('monthly','quarterly','yearly'),
    signature_img LONGTEXT, -- صورة التوقيع
    status ENUM('active','expired','cancelled') DEFAULT 'active',
    FOREIGN KEY (tenant_id) REFERENCES tenants(id),
    FOREIGN KEY (unit_id) REFERENCES units(id)
);

-- 7. الدفعات والفواتير
CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    contract_id INT, amount DECIMAL(15,2), payment_date DATE,
    payment_method VARCHAR(50), note TEXT, uuid VARCHAR(50) UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (contract_id) REFERENCES contracts(id) ON DELETE CASCADE
);
";

try {
    $pdo->exec($sql);
    
    // إنشاء الأدمن الافتراضي (إذا لم يوجد)
    $chk = $pdo->query("SELECT count(*) FROM users WHERE role='admin'")->fetchColumn();
    if($chk == 0){
        $pdo->prepare("INSERT INTO users (username, password, full_name, role) VALUES (?,?,?,?)")
            ->execute(['admin', password_hash('123456', PASSWORD_DEFAULT), 'المدير العام', 'admin']);
    }

    // إعدادات افتراضية
    $defaults = [
        'company_name'=>'دار الميار للمقاولات', 'vat_no'=>'3000000000', 'logo'=>'logo.png'
    ];
    foreach($defaults as $k=>$v) $pdo->prepare("INSERT IGNORE INTO settings (k,v) VALUES (?,?)")->execute([$k,$v]);

    echo "<div style='font-family:tahoma; text-align:center; padding:50px; background:#111; color:#4ade80;'>
            <h1>✅ تم تثبيت النظام بنجاح</h1>
            <p>تم تفعيل التوقيع الإلكتروني، العدادات، وإدارة الموظفين.</p>
            <a href='index.php' style='color:white; font-size:20px'>دخول للنظام</a>
          </div>";

} catch (PDOException $e) { die("Error: " . $e->getMessage()); }
?>
