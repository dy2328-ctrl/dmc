<?php
require 'db.php';
$id = $_GET['cid'];
$c = $pdo->query("SELECT c.*, t.full_name, t.id_number, u.unit_name, u.type, u.elec_meter_no, u.water_meter_no, p.name as pname 
                  FROM contracts c JOIN tenants t ON c.tenant_id=t.id JOIN units u ON c.unit_id=u.id JOIN properties p ON u.property_id=p.id WHERE c.id=$id")->fetch();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head><meta charset="UTF-8"><title>عقد #<?= $c['id'] ?></title>
<style>body{font-family:'Tahoma';background:#eee;padding:20px}.page{background:white;max-width:800px;margin:auto;padding:50px;border:1px solid #ddd}table{width:100%;border-collapse:collapse;margin-bottom:20px}th,td{border:1px solid #ccc;padding:10px;text-align:right}th{background:#f9f9f9}@media print{body{background:white;padding:0}.page{border:none}}</style></head>
<body onload="window.print()"><div class="page">
    <div style="display:flex;justify-content:space-between;border-bottom:3px solid #333;padding-bottom:20px;margin-bottom:30px">
        <div><h1 style="margin:0">دار الميار للمقاولات</h1><p>الرقم الضريبي: <?= getSet('vat_no') ?></p></div>
        <img src="<?= getSet('logo') ?: 'logo.png' ?>" width="100">
    </div>
    <h2 style="text-align:center;background:#333;color:white;padding:10px">عقد إيجار (<?= $c['type'] ?>)</h2>
    <h3>الطرفين</h3>
    <table><tr><th>المؤجر</th><td>دار الميار</td><th>المستأجر</th><td><?= $c['full_name'] ?> (<?= $c['id_number'] ?>)</td></tr></table>
    <h3>الوحدة</h3>
    <table><tr><th>العقار</th><td><?= $c['pname'] ?></td><th>الوحدة</th><td><?= $c['unit_name'] ?></td></tr>
    <tr><th>النوع</th><td><?= $c['type'] ?></td><th>العدادات</th><td>ك: <?= $c['elec_meter_no'] ?> | م: <?= $c['water_meter_no'] ?></td></tr></table>
    <h3>المالية</h3>
    <table><tr><th>المبلغ</th><td><?= number_format($c['total_amount']) ?></td><th>المدة</th><td><?= $c['start_date'] ?> - <?= $c['end_date'] ?></td></tr></table>
    <div style="display:flex;justify-content:space-between;margin-top:50px">
        <div style="text-align:center"><p>ختم المؤجر</p><strong>دار الميار</strong></div>
        <div style="text-align:center"><p>توقيع المستأجر</p><img src="<?= $c['signature_img'] ?>" width="150" style="border-bottom:1px solid #000"></div>
    </div>
</div></body></html>
