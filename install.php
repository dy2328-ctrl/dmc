<?php
// install.php - إعداد قاعدة البيانات الشاملة
require 'db.php';

$sql = "
-- جدول المستخدمين
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100), email VARCHAR(100) UNIQUE, password VARCHAR(255),
    role ENUM('admin','staff') DEFAULT 'admin', 
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- جدول الوحدات
CREATE TABLE IF NOT EXISTS units (
    id INT AUTO_INCREMENT PRIMARY KEY,
    unit_name VARCHAR(100), unit_number VARCHAR(50), floor_number VARCHAR(20), 
    yearly_price DECIMAL(15,2), meter_number VARCHAR(50), photo_url LONGTEXT, 
    status ENUM('available','rented','maintenance') DEFAULT 'available'
);

-- جدول المستأجرين
CREATE TABLE IF NOT EXISTS tenants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(255), phone VARCHAR(20), id_number VARCHAR(50), email VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- جدول العقود
CREATE TABLE IF NOT EXISTS contracts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT, unit_id INT, start_date DATE, end_date DATE,
    total_amount DECIMAL(15,2), payment_cycle ENUM('monthly','yearly'),
    status ENUM('active','expired','cancelled') DEFAULT 'active',
    FOREIGN KEY (tenant_id) REFERENCES tenants(id),
    FOREIGN KEY (unit_id) REFERENCES units(id)
);

-- جدول الدفعات (الفواتير)
CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    contract_id INT, amount DECIMAL(15,2), payment_date DATE,
    payment_method VARCHAR(50), note TEXT, uuid VARCHAR(50) UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (contract_id) REFERENCES contracts(id)
);

-- جدول تذاكر الصيانة
CREATE TABLE IF NOT EXISTS maintenance_tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    unit_id INT, description TEXT, status ENUM('pending','in_progress','completed') DEFAULT 'pending',
    cost DECIMAL(10,2) DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (unit_id) REFERENCES units(id)
);
";

try {
    $pdo->exec($sql);
    
    // إنشاء مستخدم مدير افتراضي
    $stmt = $pdo->query("SELECT count(*) FROM users");
    if($stmt->fetchColumn() == 0){
        $pdo->prepare("INSERT INTO users (name,email,password) VALUES (?,?,?)")
            ->execute(['المدير العام', 'admin@system.com', password_hash('123456', PASSWORD_DEFAULT)]);
    }

    echo "<div style='text-align:center; padding:50px; font-family:tahoma; background:#dcfce7; color:#166534;'>
            <h1>✅ تم تثبيت النظام بنجاح</h1>
            <p>تم إنشاء جميع الجداول (الصيانة، المالية، العقود).</p>
            <a href='index.php' style='background:#166534; color:white; padding:10px 20px; text-decoration:none; border-radius:5px;'>الدخول للنظام</a>
          </div>";

} catch (PDOException $e) { die("حدث خطأ أثناء التثبيت: " . $e->getMessage()); }
?>
