<?php
// install.php - Gemini Ultimate PRO
require 'db.php';

$sql = "
-- 1. المستخدمين
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE, password VARCHAR(255),
    full_name VARCHAR(100), phone VARCHAR(20), email VARCHAR(100),
    role ENUM('admin','staff') DEFAULT 'staff',
    photo VARCHAR(255), -- صورة الموظف
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. الإعدادات
CREATE TABLE IF NOT EXISTS settings (k VARCHAR(50) PRIMARY KEY, v LONGTEXT);

-- 3. العقارات
CREATE TABLE IF NOT EXISTS properties (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255), type VARCHAR(100), address TEXT, 
    manager_name VARCHAR(100), manager_phone VARCHAR(50),
    photo VARCHAR(255), -- صورة العقار
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 4. الوحدات
CREATE TABLE IF NOT EXISTS units (
    id INT AUTO_INCREMENT PRIMARY KEY,
    property_id INT, unit_name VARCHAR(100), unit_number VARCHAR(50),
    type ENUM('shop','apartment','villa','land','office','warehouse','building','compound') DEFAULT 'apartment',
    floor_number VARCHAR(50), yearly_price DECIMAL(15,2),
    elec_meter_no VARCHAR(50), water_meter_no VARCHAR(50),
    status ENUM('available','rented','maintenance') DEFAULT 'available',
    photo VARCHAR(255), -- صورة الوحدة
    notes TEXT,
    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
);

-- 5. المستأجرين
CREATE TABLE IF NOT EXISTS tenants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(255), phone VARCHAR(20), email VARCHAR(100),
    id_type ENUM('national','iqama','commercial') DEFAULT 'national',
    id_number VARCHAR(50), address TEXT,
    id_photo VARCHAR(255), -- صورة الهوية
    personal_photo VARCHAR(255) -- صورة شخصية
);

-- 6. العقود
CREATE TABLE IF NOT EXISTS contracts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT, unit_id INT, start_date DATE, end_date DATE,
    total_amount DECIMAL(15,2), payment_cycle ENUM('monthly','quarterly','yearly'),
    signature_img LONGTEXT, 
    status ENUM('active','expired','cancelled') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id),
    FOREIGN KEY (unit_id) REFERENCES units(id)
);
";

try {
    $pdo->exec($sql);
    
    // إنشاء مجلد الصور إذا لم يوجد
    if (!file_exists('uploads')) { mkdir('uploads', 0777, true); }

    // إنشاء الأدمن
    $chk = $pdo->query("SELECT count(*) FROM users WHERE username='admin'")->fetchColumn();
    if($chk == 0){
        $pdo->prepare("INSERT INTO users (username, password, full_name, role) VALUES (?,?,?,?)")
            ->execute(['admin', password_hash('123456', PASSWORD_DEFAULT), 'المدير العام', 'admin']);
    }

    // إعدادات افتراضية
    $defaults = ['company_name'=>'دار الميار للمقاولات', 'vat_no'=>'3000000000', 'logo'=>'logo.png'];
    foreach($defaults as $k=>$v) $pdo->prepare("INSERT IGNORE INTO settings (k,v) VALUES (?,?)")->execute([$k,$v]);

    echo "<div style='font-family:tahoma; text-align:center; padding:50px; background:#1e293b; color:#4ade80;'>
            <h1>✅ تم ترقية النظام إلى Gemini Ultimate PRO</h1>
            <p>تم تفعيل تخزين الصور، الصلاحيات المتقدمة، وأنواع العقارات الجديدة.</p>
            <a href='index.php' style='color:white; font-size:20px; text-decoration:underline'>الدخول للنظام</a>
          </div>";

} catch (PDOException $e) { die("Error: " . $e->getMessage()); }
?>
