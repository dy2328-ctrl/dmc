<?php
require 'db.php';
$id = $_GET['cid'];
$c = $pdo->query("SELECT c.*, t.full_name, t.id_number, u.unit_name, u.type, u.elec_meter_no, u.water_meter_no 
                  FROM contracts c 
                  JOIN tenants t ON c.tenant_id=t.id 
                  JOIN units u ON c.unit_id=u.id 
                  WHERE c.id=$id")->fetch();
$logo = getSet('logo');
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>عقد إيجار - <?= $c['id'] ?></title>
    <style>
        body { font-family: 'Tahoma'; padding: 40px; background: #eee; }
        .page { background: white; max-width: 800px; margin: auto; padding: 40px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .header { display: flex; justify-content: space-between; border-bottom: 2px solid #333; padding-bottom: 20px; margin-bottom: 30px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: right; }
        th { background: #f9f9f9; }
        .sig-box { display: flex; justify-content: space-between; margin-top: 50px; }
        @media print { body { background: white; padding: 0; } .page { box-shadow: none; border: none; } }
    </style>
</head>
<body onload="window.print()">
    <div class="page">
        <div class="header">
            <div>
                <h2>دار الميار للمقاولات</h2>
                <p>الرقم الضريبي: <?= getSet('vat_no') ?></p>
            </div>
            <img src="<?= $logo ?>" width="100">
        </div>

        <h2 style="text-align:center; background:#eee; padding:10px">عقد إيجار وحدة (<?= $c['type']=='shop'?'تجاري':'سكني' ?>)</h2>

        <h3>أولاً: بيانات الطرفين</h3>
        <table>
            <tr><th>المؤجر</th><td>دار الميار للمقاولات</td></tr>
            <tr><th>المستأجر</th><td><?= $c['full_name'] ?></td></tr>
            <tr><th>رقم الهوية / السجل</th><td><?= $c['id_number'] ?></td></tr>
        </table>

        <h3>ثانياً: بيانات الوحدة</h3>
        <table>
            <tr><th>اسم الوحدة</th><td><?= $c['unit_name'] ?></td><th>النوع</th><td><?= $c['type'] ?></td></tr>
            <tr><th>عداد الكهرباء</th><td><?= $c['elec_meter_no'] ?></td><th>عداد الماء</th><td><?= $c['water_meter_no'] ?></td></tr>
        </table>

        <h3>ثالثاً: التفاصيل المالية</h3>
        <table>
            <tr><th>تاريخ البداية</th><td><?= $c['start_date'] ?></td><th>تاريخ النهاية</th><td><?= $c['end_date'] ?></td></tr>
            <tr><th>قيمة العقد</th><td><?= number_format($c['total_amount']) ?> ريال سعودي</td><th>طريقة الدفع</th><td><?= $c['payment_cycle'] ?></td></tr>
        </table>

        <div class="sig-box">
            <div style="text-align:center">
                <p>ختم وتوقيع المؤجر</p>
                <br><br>
                <strong>دار الميار للمقاولات</strong>
            </div>
            <div style="text-align:center">
                <p>توقيع المستلم (المستأجر)</p>
                <?php if($c['signature_img']): ?>
                    <img src="<?= $c['signature_img'] ?>" width="150" style="border-bottom:1px solid #000">
                <?php else: ?>
                    <br><br>...........................
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
