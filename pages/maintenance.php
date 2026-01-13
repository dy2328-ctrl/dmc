<?php
// حفظ طلب الصيانة
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_maint'])) {
    // جلب معرف العقار تلقائياً بناء على الوحدة
    $unit = $pdo->query("SELECT property_id FROM units WHERE id = " . $_POST['uid'])->fetch();
    $pid = $unit ? $unit['property_id'] : 0;
    
    $stmt = $pdo->prepare("INSERT INTO maintenance (property_id, unit_id, vendor_id, description, cost, request_date, status) VALUES (?,?,?,?,?, CURDATE(), 'pending')");
    $stmt->execute([$pid, $_POST['uid'], $_POST['vid'], $_POST['desc'], $_POST['cost']]);
    echo "<script>window.location='index.php?p=maintenance';</script>";
}
?>

<div class="card">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px">
        <h3>🛠️ طلبات الصيانة</h3>
        <button type="button" onclick="document.getElementById('forceMaintModal').style.display='flex'" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i> تسجيل طلب جديد
        </button>
    </div>
    
    <table style="width:100%; border-collapse:collapse">
        <thead>
            <tr style="background:#222; text-align:right">
                <th style="padding:10px">رقم الطلب</th>
                <th style="padding:10px">الوحدة</th>
                <th style="padding:10px">الوصف</th>
                <th style="padding:10px">المقاول</th>
                <th style="padding:10px">التكلفة</th>
                <th style="padding:10px">الحالة</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $reqs = $pdo->query("SELECT m.*, u.unit_name, v.name as vname FROM maintenance m JOIN units u ON m.unit_id=u.id LEFT JOIN vendors v ON m.vendor_id=v.id ORDER BY m.id DESC");
            if($reqs->rowCount() == 0):
                echo "<tr><td colspan='6' style='text-align:center; padding:20px'>لا توجد طلبات صيانة.</td></tr>";
            else:
                while($r = $reqs->fetch()): 
            ?>
            <tr style="border-bottom:1px solid #333">
                <td style="padding:10px">#<?= $r['id'] ?></td>
                <td style="padding:10px; font-weight:bold"><?= $r['unit_name'] ?></td>
                <td style="padding:10px"><?= $r['description'] ?></td>
                <td style="padding:10px"><?= $r['vname'] ?: '-' ?></td>
                <td style="padding:10px"><?= number_format($r['cost']) ?></td>
                <td style="padding:10px">
                    <span class="badge" style="background:<?= $r['status']=='pending'?'#f59e0b':'#10b981' ?>">
                        <?= $r['status']=='pending'?'انتظار':'مكتمل' ?>
                    </span>
                </td>
            </tr>
            <?php endwhile; endif; ?>
        </tbody>
    </table>
</div>

<div id="forceMaintModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.9); z-index:99999; justify-content:center; align-items:center;">
    <div style="background:#1a1a1a; padding:30px; border-radius:10px; width:500px; border:1px solid #444;">
        <h3 style="margin-top:0; color:#fff">تسجيل طلب صيانة</h3>
        <form method="POST">
            <input type="hidden" name="save_maint" value="1">
            
            <div style="margin-bottom:15px">
                <label style="color:#aaa; display:block; margin-bottom:5px">الوحدة</label>
                <select name="uid" class="inp" required style="width:100%; padding:10px;">
                    <option value="">-- اختر الوحدة --</option>
                    <?php 
                    $units = $pdo->query("SELECT * FROM units"); 
                    while($u = $units->fetch()) echo "<option value='{$u['id']}'>{$u['unit_name']}</option>"; 
                    ?>
                </select>
            </div>

            <div style="margin-bottom:15px">
                <label style="color:#aaa; display:block; margin-bottom:5px">المقاول (اختياري)</label>
                <select name="vid" class="inp" style="width:100%; padding:10px;">
                    <option value="0">-- اختر المقاول --</option>
                    <?php 
                    $vendors = $pdo->query("SELECT * FROM vendors"); 
                    while($v = $vendors->fetch()) echo "<option value='{$v['id']}'>{$v['name']} ({$v['service_type']})</option>"; 
                    ?>
                </select>
            </div>
            
            <div style="margin-bottom:15px">
                <label style="color:#aaa; display:block; margin-bottom:5px">وصف المشكلة</label>
                <textarea name="desc" class="inp" required style="width:100%; padding:10px; height:80px"></textarea>
            </div>
            
            <div style="margin-bottom:15px">
                <label style="color:#aaa; display:block; margin-bottom:5px">التكلفة التقديرية</label>
                <input type="number" name="cost" class="inp" placeholder="0.00" style="width:100%; padding:10px;">
            </div>
            
            <div style="display:flex; gap:10px; margin-top:20px">
                <button class="btn btn-primary" style="flex:1">حفظ الطلب</button>
                <button type="button" onclick="document.getElementById('forceMaintModal').style.display='none'" class="btn btn-danger">إلغاء</button>
            </div>
        </form>
    </div>
</div>
