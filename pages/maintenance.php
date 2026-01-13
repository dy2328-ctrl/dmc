<?php
// الحفظ
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_maint'])) {
    $u = $pdo->query("SELECT property_id FROM units WHERE id=".$_POST['uid'])->fetch();
    $pid = $u ? $u['property_id'] : 0;
    $pdo->prepare("INSERT INTO maintenance (property_id, unit_id, vendor_id, description, cost, request_date, status) VALUES (?,?,?,?,?, CURDATE(), 'pending')")->execute([$pid, $_POST['uid'], $_POST['vid'], $_POST['desc'], $_POST['cost']]);
    echo "<script>window.location='index.php?p=maintenance';</script>";
}
?>

<style>
    /* نفس الستايل لضمان العمل */
    .modal-overlay {
        display: none; 
        position: fixed; top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(0,0,0,0.85); z-index: 999999;
        justify-content: center; align-items: center;
        backdrop-filter: blur(5px);
    }
    
    /* إظهار النافذة عند الضغط على الرابط */
    #maintModal:target {
        display: flex !important;
    }

    .modal-box {
        background: #1f1f1f; padding: 30px; border-radius: 15px;
        width: 500px; border: 1px solid #444; position: relative;
        box-shadow: 0 20px 50px rgba(0,0,0,0.5);
    }
    .close-btn {
        position: absolute; top: 15px; left: 15px;
        color: #aaa; font-size: 20px; text-decoration: none;
    }
</style>

<div class="card">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px">
        <h3>🛠️ طلبات الصيانة</h3>
        <a href="#maintModal" class="btn btn-primary" style="text-decoration:none">
            <i class="fa-solid fa-plus"></i> تسجيل طلب جديد
        </a>
    </div>
    
    <table style="width:100%; border-collapse:collapse">
        <thead>
            <tr style="background:#222; text-align:right">
                <th style="padding:15px">رقم الطلب</th>
                <th style="padding:15px">الوحدة</th>
                <th style="padding:15px">الوصف</th>
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
                <td style="padding:15px"><?= $r['unit_name'] ?></td>
                <td style="padding:15px"><?= $r['description'] ?></td>
                <td style="padding:15px"><?= $r['vname'] ?: '-' ?></td>
                <td style="padding:15px">
                    <span class="badge" style="background:<?= $r['status']=='pending'?'#f59e0b':'#10b981' ?>"><?= $r['status'] ?></span>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<div id="maintModal" class="modal-overlay">
    <div class="modal-box">
        <a href="#" class="close-btn"><i class="fa-solid fa-xmark"></i></a>
        
        <h3 style="margin-top:0; color:white; margin-bottom:20px">تسجيل طلب صيانة</h3>
        
        <form method="POST" action="index.php?p=maintenance">
            <input type="hidden" name="save_maint" value="1">
            
            <div style="margin-bottom:15px">
                <label style="color:#aaa; display:block; margin-bottom:5px">الوحدة المتضررة</label>
                <select name="uid" class="inp" style="width:100%; padding:10px; background:#333; color:white; border:1px solid #555" required>
                    <option value="">-- اختر --</option>
                    <?php $us=$pdo->query("SELECT * FROM units"); while($u=$us->fetch()) echo "<option value='{$u['id']}'>{$u['unit_name']}</option>"; ?>
                </select>
            </div>
            
            <div style="margin-bottom:15px">
                <label style="color:#aaa; display:block; margin-bottom:5px">المقاول (اختياري)</label>
                <select name="vid" class="inp" style="width:100%; padding:10px; background:#333; color:white; border:1px solid #555">
                    <option value="0">-- اختر --</option>
                    <?php $vs=$pdo->query("SELECT * FROM vendors"); while($v=$vs->fetch()) echo "<option value='{$v['id']}'>{$v['name']}</option>"; ?>
                </select>
            </div>
            
            <div style="margin-bottom:15px">
                <label style="color:#aaa; display:block; margin-bottom:5px">الوصف</label>
                <textarea name="desc" class="inp" style="width:100%; padding:10px; background:#333; color:white; border:1px solid #555; height:80px" required></textarea>
            </div>
            
            <div style="margin-bottom:25px">
                <label style="color:#aaa; display:block; margin-bottom:5px">التكلفة التقديرية</label>
                <input type="number" name="cost" class="inp" style="width:100%; padding:10px; background:#333; color:white; border:1px solid #555">
            </div>
            
            <button class="btn btn-primary" style="width:100%; justify-content:center; padding:12px">حفظ الطلب</button>
        </form>
    </div>
</div>
