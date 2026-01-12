<?php
// install.php - Gemini Stable Fixer
require 'db.php';

echo "<body style='background:#111; color:#fff; font-family:tahoma; padding:40px;'>";
echo "<h2>⚙️ جاري فحص وصيانة النظام...</h2>";

$tables = [
    "users" => "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE, password VARCHAR(255),
        full_name VARCHAR(100), phone VARCHAR(20), role ENUM('admin','staff') DEFAULT 'staff',
        photo VARCHAR(255), created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "settings" => "CREATE TABLE IF NOT EXISTS settings (k VARCHAR(50) PRIMARY KEY, v LONGTEXT)",
    "properties" => "CREATE TABLE IF NOT EXISTS properties (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255), type VARCHAR(100), address TEXT, 
        manager_name VARCHAR(100), manager_phone VARCHAR(50), photo VARCHAR(255)
    )",
    "units" => "CREATE TABLE IF NOT EXISTS units (
        id INT AUTO_INCREMENT PRIMARY KEY,
        property_id INT, unit_name VARCHAR(100), type VARCHAR(50),
        yearly_price DECIMAL(15,2), elec_meter_no VARCHAR(50), water_meter_no VARCHAR(50),
        status VARCHAR(50) DEFAULT 'available', notes TEXT, photo VARCHAR(255),
        FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
    )",
    "vendors" => "CREATE TABLE IF NOT EXISTS vendors (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100), service_type VARCHAR(50), phone VARCHAR(20), email VARCHAR(100)
    )",
    "maintenance" => "CREATE TABLE IF NOT EXISTS maintenance (
        id INT AUTO_INCREMENT PRIMARY KEY,
        property_id INT, unit_id INT, vendor_id INT,
        description TEXT, cost DECIMAL(15,2), status ENUM('pending','completed','paid') DEFAULT 'pending',
        request_date DATE,
        FOREIGN KEY (property_id) REFERENCES properties(id)
    )",
    "tenants" => "CREATE TABLE IF NOT EXISTS tenants (
        id INT AUTO_INCREMENT PRIMARY KEY,
        full_name VARCHAR(255), phone VARCHAR(20), id_number VARCHAR(50), 
        id_type VARCHAR(50), cr_number VARCHAR(50), email VARCHAR(100), address TEXT,
        id_photo VARCHAR(255), personal_photo VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "contracts" => "CREATE TABLE IF NOT EXISTS contracts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tenant_id INT, unit_id INT, start_date DATE, end_date DATE,
        total_amount DECIMAL(15,2), payment_cycle VARCHAR(50),
        signature_img LONGTEXT, status VARCHAR(20) DEFAULT 'active',
        notes TEXT, services_cost DECIMAL(15,2) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (tenant_id) REFERENCES tenants(id),
        FOREIGN KEY (unit_id) REFERENCES units(id)
    )",
    "payments" => "CREATE TABLE IF NOT EXISTS payments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        contract_id INT, title VARCHAR(100), 
        amount DECIMAL(15,2), paid_amount DECIMAL(15,2) DEFAULT 0, 
        due_date DATE, status VARCHAR(20) DEFAULT 'pending', 
        paid_date DATE,
        FOREIGN KEY (contract_id) REFERENCES contracts(id) ON DELETE CASCADE
    )",
    "transactions" => "CREATE TABLE IF NOT EXISTS transactions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        payment_id INT, amount_paid DECIMAL(15,2),
        payment_method VARCHAR(50), transaction_date DATE, notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE CASCADE
    )"
];

try {
    foreach($tables as $name => $sql) {
        $pdo->exec($sql);
        echo "<p style='color:#4ade80'>✅ جدول $name جاهز.</p>";
    }

    // إصلاح الأعمدة الناقصة (Alter Table Safe Mode)
    $updates = [
        "ALTER TABLE tenants ADD COLUMN cr_number VARCHAR(50)",
        "ALTER TABLE tenants ADD COLUMN email VARCHAR(100)",
        "ALTER TABLE maintenance ADD COLUMN vendor_id INT",
        "ALTER TABLE transactions ADD COLUMN notes TEXT"
    ];
    foreach($updates as $up) {
        try { $pdo->exec($up); } catch(Exception $e) {} // تجاهل الخطأ إذا العمود موجود
    }

    // إعدادات افتراضية
    $defaults = ['company_name'=>'دار الميار للمقاولات', 'logo'=>'logo.png'];
    foreach($defaults as $k=>$v) $pdo->prepare("INSERT IGNORE INTO settings (k,v) VALUES (?,?)")->execute([$k,$v]);
    
    // إنشاء الأدمن
    $chk = $pdo->query("SELECT count(*) FROM users WHERE username='admin'")->fetchColumn();
    if($chk == 0){
        $pdo->prepare("INSERT INTO users (username, password, full_name, role) VALUES (?,?,?,?)")
            ->execute(['admin', password_hash('123456', PASSWORD_DEFAULT), 'المدير العام', 'admin']);
        echo "<p style='color:#facc15'>🔑 تم إنشاء حساب المدير: admin / 123456</p>";
    }

    echo "<h1 style='color:#4ade80; border-top:1px solid #333; padding-top:20px'>🚀 تم إصلاح النظام بنجاح!</h1>";
    echo "<a href='index.php' style='background:#6366f1; color:white; padding:10px 20px; text-decoration:none; border-radius:5px'>الدخول للنظام</a>";

} catch (PDOException $e) { die("Error: " . $e->getMessage()); }
?>
