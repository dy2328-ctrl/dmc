<?php
// install.php - Dar Al-Mayar Pro Edition
require 'db.php';

$sql = "
-- 1. المستخدمين
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE, password VARCHAR(255),
    full_name VARCHAR(100), phone VARCHAR(20), role ENUM('admin','staff') DEFAULT 'staff',
    photo VARCHAR(255), created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. الإعدادات
CREATE TABLE IF NOT EXISTS settings (k VARCHAR(50) PRIMARY KEY, v LONGTEXT);

-- 3. العقارات
CREATE TABLE IF NOT EXISTS properties (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255), type VARCHAR(100), address TEXT, 
    manager_name VARCHAR(100), manager_phone VARCHAR(50), photo VARCHAR(255)
);

-- 4. الوحدات
CREATE TABLE IF NOT EXISTS units (
    id INT AUTO_INCREMENT PRIMARY KEY,
    property_id INT, unit_name VARCHAR(100), type VARCHAR(50),
    yearly_price DECIMAL(15,2), elec_meter_no VARCHAR(50), water_meter_no VARCHAR(50),
    status VARCHAR(50) DEFAULT 'available', notes TEXT, photo VARCHAR(255),
    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
);

-- 5. المستأجرين (تمت إضافة الحقول الجديدة: السجل، المرفقات)
CREATE TABLE IF NOT EXISTS tenants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(255), phone VARCHAR(20), id_number VARCHAR(50), 
    id_type VARCHAR(50), cr_number VARCHAR(50), email VARCHAR(100), address TEXT,
    id_photo VARCHAR(255), personal_photo VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 6. العقود
CREATE TABLE IF NOT EXISTS contracts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT, unit_id INT, start_date DATE, end_date DATE,
    total_amount DECIMAL(15,2), payment_cycle VARCHAR(50),
    signature_img LONGTEXT, status VARCHAR(20) DEFAULT 'active',
    notes TEXT, services_cost DECIMAL(15,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id),
    FOREIGN KEY (unit_id) REFERENCES units(id)
);

-- 7. الدفعات (الأقساط) - ميزة جديدة
CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    contract_id INT, 
    title VARCHAR(100), -- دفعة 1، دفعة 2...
    amount DECIMAL(15,2), -- المبلغ المستحق
    paid_amount DECIMAL(15,2) DEFAULT 0, -- المبلغ المدفوع
    due_date DATE, -- تاريخ الاستحقاق
    status VARCHAR(20) DEFAULT 'pending', -- pending, paid, partial, late
    paid_date DATE,
    FOREIGN KEY (contract_id) REFERENCES contracts(id) ON DELETE CASCADE
);
";

try {
    $pdo->exec($sql);
    
    // إعدادات افتراضية
    $defaults = ['company_name'=>'دار الميار للمقاولات', 'logo'=>'logo.png'];
    foreach($defaults as $k=>$v) $pdo->prepare("INSERT IGNORE INTO settings (k,v) VALUES (?,?)")->execute([$k,$v]);

    // إنشاء مجلد الصور
    if (!file_exists('uploads')) { mkdir('uploads', 0777, true); }

    // إنشاء الأدمن
    $chk = $pdo->query("SELECT count(*) FROM users WHERE username='admin'")->fetchColumn();
    if($chk == 0){
        $pdo->prepare("INSERT INTO users (username, password, full_name, role) VALUES (?,?,?,?)")
            ->execute(['admin', password_hash('123456', PASSWORD_DEFAULT), 'المدير العام', 'admin']);
    }

    echo "<div style='font-family:tahoma; text-align:center; padding:50px; background:#f3f4f6; color:#10b981;'>
            <h1>✅ تم التحديث لنسخة Dar Al-Mayar Pro</h1>
            <p>تم تفعيل نظام الدفعات التلقائي والمرفقات.</p>
            <a href='index.php' style='background:#6366f1; color:white; padding:10px 20px; text-decoration:none; border-radius:8px'>الدخول للنظام</a>
          </div>";

} catch (PDOException $e) { die("Error: " . $e->getMessage()); }
?>
