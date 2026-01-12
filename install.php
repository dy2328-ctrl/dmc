<?php
// install.php - المطور: تفعيل النظام المالي والعدادات
require 'db.php';

$sql = "
-- الجداول الأساسية
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100), email VARCHAR(100) UNIQUE, password VARCHAR(255),
    role ENUM('admin','staff') DEFAULT 'admin', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS settings (k VARCHAR(50) PRIMARY KEY, v TEXT);

CREATE TABLE IF NOT EXISTS properties (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255), address TEXT, manager_name VARCHAR(100)
);

CREATE TABLE IF NOT EXISTS units (
    id INT AUTO_INCREMENT PRIMARY KEY,
    property_id INT, unit_name VARCHAR(100), unit_number VARCHAR(50),
    floor_number VARCHAR(20), yearly_price DECIMAL(15,2), meter_number VARCHAR(50),
    photo_url LONGTEXT, status ENUM('available','rented','maintenance') DEFAULT 'available',
    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS tenants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(255), phone VARCHAR(20), email VARCHAR(100),
    id_number VARCHAR(50), created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS contracts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT, unit_id INT, start_date DATE, end_date DATE,
    total_amount DECIMAL(15,2), payment_cycle ENUM('monthly','quarterly','yearly'),
    status ENUM('active','expired','cancelled') DEFAULT 'active',
    FOREIGN KEY (tenant_id) REFERENCES tenants(id),
    FOREIGN KEY (unit_id) REFERENCES units(id)
);

-- جديد: جدول الدفعات المالية (السندات)
CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    contract_id INT, amount DECIMAL(15,2), payment_date DATE,
    payment_method VARCHAR(50), note TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (contract_id) REFERENCES contracts(id) ON DELETE CASCADE
);

-- تفعيل: قراءات العدادات
CREATE TABLE IF NOT EXISTS meter_readings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    unit_id INT, reading_date DATE, reading_value DECIMAL(10,2),
    notes TEXT,
    FOREIGN KEY (unit_id) REFERENCES units(id)
);
";

try {
    $pdo->exec($sql);
    
    // إنشاء مستخدم افتراضي إذا لم يوجد
    $stmt = $pdo->prepare("SELECT count(*) FROM users"); $stmt->execute();
    if($stmt->fetchColumn() == 0){
        $pdo->prepare("INSERT INTO users (name,email,password) VALUES (?,?,?)")
            ->execute(['المدير العام', 'admin@gmail.com', password_hash('123456', PASSWORD_DEFAULT)]);
    }

    echo "<div style='font-family:tahoma; text-align:center; padding:50px; background:#f0fdf4; color:#166534;'>
            <h1>✅ تم ترقية قاعدة البيانات بنجاح</h1>
            <p>تم تفعيل جداول المالية (Payments) وقراءات العدادات.</p>
            <a href='index.php' style='background:#166534; color:white; padding:10px 20px; text-decoration:none; border-radius:5px;'>انتقل للنظام المطور</a>
          </div>";

} catch (PDOException $e) { die("Error: " . $e->getMessage()); }
?>
