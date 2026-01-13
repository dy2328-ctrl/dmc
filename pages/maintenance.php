<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_maint'])) {
    $u = $pdo->query("SELECT property_id FROM units WHERE id=".$_POST['uid'])->fetch();
    $pid = $u ? $u['property_id'] : 0;
    $pdo->prepare("INSERT INTO maintenance (property_id, unit_id, vendor_id, description, cost, request_date, status) VALUES (?,?,?,?,?, CURDATE(), 'pending')")->execute([$pid, $_POST['uid'], $_POST['vid'], $_POST['desc'], $_POST['cost']]);
    echo "<script>window.location='index.php?p=maintenance';</script>";
}
?>

<style>
    /* واجهة التركيز الكامل */
    .focus-overlay {
        position: fixed;
        top: 100%; /* تبدأ من الأسفل */
        left: 0;
        width: 100%;
        height: 100%;
        background: #0f0f0f; /* لون داكن جداً */
        z-index: 99999;
        transition: top 0.5s cubic-bezier(0.77, 0, 0.175, 1); /* حركة انسيابية قوية */
        display: flex;
        justify-content: center;
        align-items: center;
    }
    
    .focus-active { top: 0 !important; }

    .focus-container {
        width: 600px;
        max-width: 90%;
        animation: scaleIn 0.5s ease 0.2s backwards; /* تكبير بسيط عند الظهور */
    }

    @keyframes scaleIn { from { transform: scale(0.9); opacity: 0; } to { transform: scale(1); opacity: 1; } }

    .big-input {
        background: transparent;
        border: none;
        border-bottom: 2px solid #333;
        color: white;
        font-size: 18px;
        padding: 15px 0;
        width: 100%;
        margin-bottom: 30px;
        transition: 0.3s;
    }
    .big-input:focus { border-bottom-color: #10b981; outline: none; }
    
    label { color: #10b981; font-size: 12px; text-transform: uppercase; letter-spacing: 2px; }
</style>

<div class="card">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px">
        <h3>🛠️ طلبات الصيانة</h3>
        <button onclick="openFocusMode()" class="btn btn-primary" style="border-radius:30px; padding:10px 25px; background: linear-gradient(45deg, #10b981, #059669); border:none;">
            <i class="fa-solid fa-plus"></i> تسجيل طلب (Focus UI)
        </button>
    </div>
    
    <table style="width:100%; border-collapse:collapse">
        <thead><tr style="background:#222; text-align:right"><th style="padding:15px">الوحدة</th><th style="padding:15px">المشكلة</th><th style="padding:15px">المقاول</th><th style="padding:15px">الحالة</th></tr></thead>
        <tbody>
            <?php 
            $reqs=$pdo->query("SELECT m.*, u.unit_name, v.name as vname FROM maintenance m JOIN units u ON m.unit_id=u.id LEFT JOIN vendors v ON m.vendor_id=v.id ORDER BY id DESC"); 
            while($r=$reqs->fetch()): ?>
            <tr style="border-bottom:1px solid #333">
                <td style="padding:15px; font-weight:bold"><?= $r['unit_name'] ?></td>
                <td style="padding:15px"><?= $r['description'] ?></td>
                <td style="padding:15px"><?= $r['vname'] ?: '-' ?></td>
                <td style="padding:15px"><span class="badge" style="background:<?= $r['status']=='pending'?'#f59e0b':'#10b981' ?>"><?= $r['status'] ?></span></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<div id="maintFocus" class="focus-overlay">
    <div class="focus-container">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:40px">
            <h1 style="color:white; font-size:35px; margin:0">طلب صيانة جديد</h1>
            <button onclick="closeFocusMode()" style="background:none; border:none; color:#555; font-size:40px; cursor:pointer; transition:0.3s hover:color:red">&times;</button>
        </div>

        <form method="POST">
            <input type="hidden" name="save_maint" value="1">

            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:30px">
                <div>
                    <label>الوحدة المتضررة</label>
                    <select name="uid" class="big-input" required style="color:#aaa">
                        <option value="">-- اختر الوحدة --</option>
                        <?php $us=$pdo->query("SELECT * FROM units"); while($u=$us->fetch()) echo "<option value='{$u['id']}'>{$u['unit_name']}</option>"; ?>
                    </select>
                </div>
                <div>
                    <label>المقاول (اختياري)</label>
                    <select name="vid" class="big-input" style="color:#aaa">
                        <option value="0">-- اختر المقاول --</option>
                        <?php $vs=$pdo->query("SELECT * FROM vendors"); while($v=$vs->fetch()) echo "<option value='{$v['id']}'>{$v['name']}</option>"; ?>
                    </select>
                </div>
            </div>

            <label>وصف المشكلة بدقة</label>
            <textarea name="desc" class="big-input" rows="1" placeholder="اكتب هنا..." required></textarea>

            <label>التكلفة التقديرية (ريال)</label>
            <input type="number" name="cost" class="big-input" placeholder="0.00">

            <button class="btn" style="width:100%; padding:20px; font-size:18px; font-weight:bold; background:#10b981; color:black; border:none; border-radius:50px; cursor:pointer; margin-top:20px">
                إرسال الطلب فوراً <i class="fa-solid fa-paper-plane"></i>
            </button>
        </form>
    </div>
</div>

<script>
    function openFocusMode() {
        document.getElementById('maintFocus').classList.add('focus-active');
    }
    function closeFocusMode() {
        document.getElementById('maintFocus').classList.remove('focus-active');
    }
</script>
