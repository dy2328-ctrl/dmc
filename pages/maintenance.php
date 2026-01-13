<?php
// حفظ الطلب
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_maint'])) {
    // جلب معرف العقار تلقائياً
    $u = $pdo->query("SELECT property_id FROM units WHERE id=".$_POST['uid'])->fetch();
    $pid = $u ? $u['property_id'] : 0;
    
    $stmt = $pdo->prepare("INSERT INTO maintenance (property_id, unit_id, vendor_id, description, cost, request_date, status) VALUES (?,?,?,?,?, CURDATE(), 'pending')");
    $stmt->execute([$pid, $_POST['uid'], $_POST['vid'], $_POST['desc'], $_POST['cost']]);
    echo "<script>window.location='index.php?p=maintenance';</script>";
}
?>

<style>
    .custom-modal {
        display: none; 
        position: fixed; 
        top: 0; left: 0; 
        width: 100%; height: 100%; 
        background: rgba(0,0,0,0.85); 
        z-index: 10000; 
        justify-content: center; 
        align-items: center;
        backdrop-filter: blur(5px);
    }
    .custom-modal-content {
        background: #1f1f1f; 
        padding: 30px; 
        border-radius: 15px; 
        width: 500px; 
        box-shadow: 0 10px 30px rgba(0,0,0,0.5); 
        border: 1px solid #333;
        animation: fadeIn 0.3s ease;
    }
</style>

<div class="card">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px">
        <h3>🛠️ طلبات الصيانة</h3>
        <button onclick="document.getElementById('maintOverlay').style.display='flex'" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i> تسجيل طلب جديد
        </button>
    </div>
    
    <table style="width:100%; border-collapse:collapse">
        <thead>
            <tr style="background:#222; text-align:right">
                <th style="padding:15px">رقم الطلب</th>
                <th style="padding:15px">الوحدة</th>
                <th style="padding:15px">المشكلة</th>
                <th style="padding:15px">المقاول</th>
                <th style="padding:15px">الحالة</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $reqs = $pdo->query("SELECT m.*, u.unit_name, v.name as vname FROM maintenance m JOIN units u ON m.unit_id=u.id LEFT JOIN vendors v ON m.vendor_id=v.id ORDER BY m.id DESC");
            while($r = $reqs->fetch()): 
            ?>
            <tr style="border-bottom:1px solid #333">
                <td style="padding:15px">#<?= $r['id'] ?></td>
                <td style="padding:15px; font-weight:bold"><?= $r['unit_name'] ?></td>
                <td style="padding:15px"><?= $r['description'] ?></td>
                <td style="padding:15px"><?= $r['vname'] ?: '<span style="color:#666">غير محدد</span>' ?></td>
                <td style="padding:15px">
                    <span class="badge" style="background:<?= $r['status']=='pending'?'#f59e0b':'#10b981' ?>">
                        <?= $r['status']=='pending'?'انتظار':'مكتمل' ?>
                    </span>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<div id="maintOverlay" class="custom-modal">
    <div class="custom-modal-content">
        <div style="display:flex; justify-content:space-between; margin-bottom:20px;">
            <h3 style="margin:0; color:#fff">تسجيل طلب صيانة</h3>
            <div onclick="document.getElementById('maintOverlay').style.display='none'" style="cursor:pointer; font-size:20px; color:#aaa">
                <i class="fa-solid fa-xmark"></i>
            </div>
        </div>
        
        <form method="POST">
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
                <textarea name="desc" class="inp" required style="width:100%; height:80px;"></textarea>
            </div>
            
            <div style="margin-bottom:25px">
                <label class="inp-label">التكلفة التقديرية</label>
                <input type="number" name="cost" class="inp" placeholder="0.00" style="width:100%">
            </div>
            
            <button class="btn btn-primary" style="width:100%; justify-content:center; padding:12px">حفظ الطلب</button>
        </form>
    </div>
</div>
