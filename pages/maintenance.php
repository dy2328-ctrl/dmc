<?php
// pages/maintenance.php

// 1. الحفظ
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_maint'])) {
    // جلب معرف العقار تلقائياً
    $u = $pdo->query("SELECT property_id FROM units WHERE id=".$_POST['uid'])->fetch();
    $pid = $u ? $u['property_id'] : 0;
    
    $stmt = $pdo->prepare("INSERT INTO maintenance (property_id, unit_id, vendor_id, description, cost, request_date, status) VALUES (?,?,?,?,?, CURDATE(), 'pending')");
    $stmt->execute([$pid, $_POST['uid'], $_POST['vid'], $_POST['desc'], $_POST['cost']]);
    echo "<script>window.location='index.php?p=maintenance';</script>";
    exit;
}

$action = $_GET['action'] ?? 'list';
?>

<div class="card">
    <?php if ($action == 'list'): ?>
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px">
            <h3>🛠️ طلبات الصيانة</h3>
            <a href="index.php?p=maintenance&action=form" class="btn btn-primary">
                <i class="fa-solid fa-plus"></i> تسجيل طلب جديد
            </a>
        </div>
        
        <table style="width:100%; border-collapse:collapse">
            <thead>
                <tr style="background:#222; text-align:right">
                    <th style="padding:10px">رقم الطلب</th>
                    <th style="padding:10px">الوحدة</th>
                    <th style="padding:10px">الوصف</th>
                    <th style="padding:10px">المقاول</th>
                    <th style="padding:10px">الحالة</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $reqs = $pdo->query("SELECT m.*, u.unit_name, v.name as vname FROM maintenance m JOIN units u ON m.unit_id=u.id LEFT JOIN vendors v ON m.vendor_id=v.id ORDER BY m.id DESC");
                if($reqs->rowCount() == 0):
                    echo "<tr><td colspan='5' style='text-align:center; padding:20px'>لا توجد طلبات.</td></tr>";
                else:
                    while($r = $reqs->fetch()): 
                ?>
                <tr style="border-bottom:1px solid #333">
                    <td style="padding:10px">#<?= $r['id'] ?></td>
                    <td style="padding:10px"><?= $r['unit_name'] ?></td>
                    <td style="padding:10px"><?= $r['description'] ?></td>
                    <td style="padding:10px"><?= $r['vname'] ?: '-' ?></td>
                    <td style="padding:10px">
                        <span class="badge" style="background:<?= $r['status']=='pending'?'#f59e0b':'#10b981' ?>">
                            <?= $r['status']=='pending'?'انتظار':'مكتمل' ?>
                        </span>
                    </td>
                </tr>
                <?php endwhile; endif; ?>
            </tbody>
        </table>

    <?php else: ?>
        <div style="display:flex; justify-content:space-between; margin-bottom:20px; border-bottom:1px solid #333; padding-bottom:10px">
            <h3>تسجيل طلب صيانة جديد</h3>
            <a href="index.php?p=maintenance" class="btn btn-dark">رجوع للقائمة</a>
        </div>

        <form method="POST" style="max-width:600px">
            <input type="hidden" name="save_maint" value="1">
            
            <div style="margin-bottom:15px">
                <label class="inp-label">الوحدة المتضررة</label>
                <select name="uid" class="inp" required style="width:100%">
                    <option value="">-- اختر الوحدة --</option>
                    <?php $us=$pdo->query("SELECT * FROM units"); while($u=$us->fetch()) echo "<option value='{$u['id']}'>{$u['unit_name']}</option>"; ?>
                </select>
            </div>

            <div style="margin-bottom:15px">
                <label class="inp-label">المقاول المكلف (اختياري)</label>
                <select name="vid" class="inp" style="width:100%">
                    <option value="0">-- اختر المقاول --</option>
                    <?php $vs=$pdo->query("SELECT * FROM vendors"); while($v=$vs->fetch()) echo "<option value='{$v['id']}'>{$v['name']} ({$v['service_type']})</option>"; ?>
                </select>
            </div>
            
            <div style="margin-bottom:15px">
                <label class="inp-label">وصف المشكلة</label>
                <textarea name="desc" class="inp" required style="width:100%; height:100px"></textarea>
            </div>
            
            <div style="margin-bottom:20px">
                <label class="inp-label">التكلفة التقديرية</label>
                <input type="number" name="cost" class="inp" placeholder="0.00" style="width:100%">
            </div>

            <button class="btn btn-primary" style="padding:10px 20px">حفظ الطلب</button>
        </form>
    <?php endif; ?>
</div>
