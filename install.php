<?php
// install.php - Gemini Ultimate Database
require 'db.php';

$sql = "
-- 1. المستخدمين (مع الصلاحيات والحالة)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100), email VARCHAR(100) UNIQUE, password VARCHAR(255),
    phone VARCHAR(20), role ENUM('admin','staff') DEFAULT 'admin',
    status ENUM('active','inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. الإعدادات (شاملة: الضريبة، العملة، التنبيهات)
CREATE TABLE IF NOT EXISTS settings (
    k VARCHAR(50) PRIMARY KEY, v TEXT
);

-- 3. العقارات
CREATE TABLE IF NOT EXISTS properties (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255), type VARCHAR(50), address TEXT,
    manager_name VARCHAR(100), manager_phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 4. الوحدات (صور، عدادات، دور)
CREATE TABLE IF NOT EXISTS units (
    id INT AUTO_INCREMENT PRIMARY KEY,
    property_id INT, unit_name VARCHAR(100), unit_number VARCHAR(50),
    floor_number VARCHAR(20), rooms_count INT,
    yearly_price DECIMAL(15,2), meter_number VARCHAR(50),
    photo_url LONGTEXT, status ENUM('available','rented','maintenance') DEFAULT 'available',
    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
);

-- 5. المستأجرين (هوية، سجل، نشاط)
CREATE TABLE IF NOT EXISTS tenants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(255), phone VARCHAR(20), email VARCHAR(100),
    id_number VARCHAR(50), cr_number VARCHAR(50), activity_type VARCHAR(100),
    address TEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 6. العقود (الذكية)
CREATE TABLE IF NOT EXISTS contracts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT, unit_id INT, start_date DATE, end_date DATE,
    total_amount DECIMAL(15,2), paid_amount DECIMAL(15,2) DEFAULT 0,
    payment_cycle ENUM('monthly','quarterly','yearly'),
    next_payment_date DATE, -- للمطالبات الذكية
    signature_img LONGTEXT, contract_file VARCHAR(255),
    status ENUM('active','expired','cancelled') DEFAULT 'active',
    FOREIGN KEY (tenant_id) REFERENCES tenants(id),
    FOREIGN KEY (unit_id) REFERENCES units(id)
);

-- 7. الخدمات المضافة (جديد - حسب صورة العقد)
CREATE TABLE IF NOT EXISTS contract_services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    contract_id INT, service_name VARCHAR(100), price DECIMAL(10,2),
    FOREIGN KEY (contract_id) REFERENCES contracts(id) ON DELETE CASCADE
);

-- 8. قراءات العدادات
CREATE TABLE IF NOT EXISTS meter_readings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    unit_id INT, reading_date DATE, reading_value DECIMAL(10,2),
    photo_evidence LONGTEXT, notes TEXT,
    FOREIGN KEY (unit_id) REFERENCES units(id)
);
";

try {
    $pdo->exec($sql);
    
    // المستخدم المدير
    $pdo->prepare("INSERT IGNORE INTO users (name,email,password,role) VALUES (?,?,?,?)")
        ->execute(['المدير العام', 'admin@gmail.com', password_hash('123456', PASSWORD_DEFAULT), 'admin']);
    
    // إعدادات افتراضية مطابقة لصورك
    $sets = [
        'company_name'=>'دار الميار للمقاولات', 'company_phone'=>'0505256365', 
        'vat_number'=>'310157238100003', 'vat_percent'=>'15', 'currency'=>'SAR',
        'alert_days_before'=>'30', 'invoice_prefix'=>'INV-'
    ];
    foreach($sets as $k=>$v) $pdo->prepare("INSERT IGNORE INTO settings (k,v) VALUES (?,?)")->execute([$k,$v]);

    echo "<div style='font-family:tahoma; text-align:center; padding:40px; background:#dcfce7; color:#166534; border-radius:10px; margin:20px;'>
            <h1>🚀 تم تفعيل معمارية Gemini Ultimate</h1>
            <p>تم بناء الجداول، تفعيل المطالبات الذكية، وخدمات العقود.</p>
            <a href='index.php' style='background:#166534; color:white; padding:10px 20px; text-decoration:none; border-radius:5px;'>ابدأ الآن</a>
          </div>";

} catch (PDOException $e) { die("Fatal Error: " . $e->getMessage()); }
?>
