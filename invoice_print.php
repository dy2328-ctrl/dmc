<?php
require 'db.php';
$id = $_GET['cid'];
$c = $pdo->query("SELECT c.*, t.full_name, t.id_number, u.unit_name, u.type, u.elec_meter_no, u.water_meter_no, p.name as pname 
                  FROM contracts c 
                  JOIN tenants t ON c.tenant_id=t.id 
                  JOIN units u ON c.unit_id=u.id 
                  JOIN properties p ON u.property_id=p.id
                  WHERE c.id=$id")->fetch();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>عقد رقم #<?= $c['id'] ?></title>
    <style>
        body { font-family: 'Tahoma'; padding: 40px; background: #eee; }
        .page { background: white; max-width: 800px; margin: auto; padding: 50px; border: 1px solid #ddd; }
        .header { display: flex; justify-content: space-between; border-bottom: 3px solid #333; padding-bottom: 20px; margin-bottom: 40px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        th, td { border: 1px solid #ccc; padding: 12px; text-align: right; }
        th { background: #f5f5f5; width: 150px; }
        .footer { margin-top: 50px; display: flex; justify-content: space-between; }
        .sig-box { text-align: center; width: 200px; }
        .sig-img { width: 100%; border-bottom: 1px solid #000; padding-bottom: 10px; }
        @media print { body{background:white; padding:0;} .page{border:none; padding:0;} }
    </style>
</head>
<body onload="window.print()">
    <div class="page">
        <div class="header">
            <div>
                <h1 style="margin:0">دار الميار للمقاولات</h1>
                <p>إدارة الأملاك والتطوير العقاري</p>
                <p>الرقم الضريبي: <?= getSet('vat_no') ?></p>
            </div>
            <img src="logo.png" width="120">
        </div>

        <h2 style="text-align:center; background:#333; color:white; padding:10px">عقد إيجار (<?= $c['type'] ?>)</h2>

        <h3>1. بيانات الطرفين</h3>
        <table>
            <tr><th>المؤجر</th><td>دار الميار للمقاولات</td></tr>
            <tr><th>المستأجر</th><td><?= $c['full_name'] ?></td></tr>
            <tr><th>رقم الهوية/السجل</th><td><?= $c['id_number'] ?></td></tr>
        </table>

        <h3>2. بيانات الوحدة</h3>
        <table>
            <tr><th>العقار</th><td><?= $c['pname'] ?></td><th>رقم الوحدة</th><td><?= $c['unit_name'] ?></td></tr>
            <tr><th>النوع</th><td><?= $c['type'] ?></td><th>عداد الكهرباء</th><td><?= $c['elec_meter_no'] ?></td></tr>
            <tr><th>عداد الماء</th><td><?= $c['water_meter_no'] ?></td><th>حالة التسليم</th><td>ممتازة</td></tr>
        </table>

        <h3>3. التفاصيل المالية والمدة</h3>
        <table>
            <tr><th>يبدأ في</th><td><?= $c['start_date'] ?></td><th>ينتهي في</th><td><?= $c['end_date'] ?></td></tr>
            <tr><th>القيمة الإجمالية</th><td><?= number_format($c['total_amount']) ?> ريال سعودي</td><th>طريقة السداد</th><td><?= $c['payment_cycle'] ?></td></tr>
        </table>

        <div class="footer">
            <div class="sig-box">
                <p>ختم وتوقيع المؤجر</p>
                <br><br>
                <strong>دار الميار للمقاولات</strong>
            </div>
            <div class="sig-box">
                <p>توقيع المستأجر</p>
                <?php if($c['signature_img']): ?>
                    <img src="<?= $c['signature_img'] ?>" class="sig-img">
                <?php else: ?>
                    <br><br>........................
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
