<?php
// install.php - Gemini Smart Edition
require 'db.php';

$sql = "
-- 1. المستخدمين
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100), email VARCHAR(100) UNIQUE, password VARCHAR(255),
    role ENUM('admin','staff') DEFAULT 'admin', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. الإعدادات (لتخزين بيانات الشركة والضريبة)
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

-- 4. الوحدات (مع دعم الصور والعدادات)
CREATE TABLE IF NOT EXISTS units (
    id INT AUTO_INCREMENT PRIMARY KEY,
    property_id INT, unit_name VARCHAR(100), unit_number VARCHAR(50),
    floor_number VARCHAR(20), yearly_price DECIMAL(10,2),
    meter_number VARCHAR(50), -- رقم العداد
    photo_url LONGTEXT, -- صورة الوحدة
    status ENUM('available','rented','maintenance') DEFAULT 'available',
    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
);

-- 5. المستأجرين (مطابق للصورة: هوية، سجل تجاري، نشاط)
CREATE TABLE IF NOT EXISTS tenants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(255), phone VARCHAR(20), 
    id_number VARCHAR(50), cr_number VARCHAR(50), -- السجل التجاري
    activity_type VARCHAR(100), -- نوع النشاط
    email VARCHAR(100), address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 6. العقود (الذكية)
CREATE TABLE IF NOT EXISTS contracts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT, unit_id INT, start_date DATE, end_date DATE,
    total_amount DECIMAL(15,2), payment_cycle VARCHAR(50),
    status ENUM('active','expired') DEFAULT 'active',
    FOREIGN KEY (tenant_id) REFERENCES tenants(id),
    FOREIGN KEY (unit_id) REFERENCES units(id)
);
";

try {
    $pdo->exec($sql);
    
    // مستخدم افتراضي
    $pdo->prepare("INSERT IGNORE INTO users (name,email,password) VALUES (?,?,?)")
        ->execute(['Admin', 'admin@gmail.com', password_hash('123456', PASSWORD_DEFAULT)]);
    
    // إعدادات افتراضية
    $sets = ['company_name'=>'دار الميار','vat_percent'=>'15','currency'=>'SAR'];
    foreach($sets as $k=>$v) $pdo->prepare("INSERT IGNORE INTO settings (k,v) VALUES (?,?)")->execute([$k,$v]);

    echo "<h1 style='text-align:center; color:green; font-family:sans-serif'>✅ تم تثبيت النظام وتفعيل المميزات الذكية!</h1><center><a href='index.php'>انتقل للوحة التحكم</a></center>";

} catch (PDOException $e) { die("Error: " . $e->getMessage()); }
?>
