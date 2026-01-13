<?php
// تأكد من وجود مجلد للصور
if (!is_dir('uploads')) mkdir('uploads');

$id = $_GET['id'];
$c = $pdo->query("SELECT c.*, t.name as tname, t.phone, u.unit_name, u.type, u.address FROM contracts c JOIN tenants t ON c.tenant_id=t.id JOIN units u ON c.unit_id=u.id WHERE c.id=$id")->fetch();

// حفظ التوقيع
if(isset($_POST['save_sig'])){
    $img = str_replace('data:image/png;base64,', '', $_POST['sig_data']);
    $img = str_replace(' ', '+', $img);
    $data = base64_decode($img);
    $file = 'uploads/sig_'.time().'.png';
    file_put_contents($file, $data);
    $pdo->prepare("UPDATE contracts SET signature_img=? WHERE id=?")->execute([$file, $id]);
    echo "<script>window.location='index.php?p=contract_view&id=$id';</script>";
}

// حفظ الصور
if(isset($_POST['save_photo'])){
    $type = $_POST['photo_type'];
    // التحقق من العدد
    $count = $pdo->query("SELECT COUNT(*) FROM inspection_photos WHERE contract_id=$id AND photo_type='$type'")->fetchColumn();
    if($count >= 10) {
        echo "<script>alert('عفواً، الحد الأقصى 10 صور فقط');</script>";
    } else {
        $img = str_replace('data:image/png;base64,', '', $_POST['photo_data']);
        $img = str_replace(' ', '+', $img);
        $data = base64_decode($img);
        $file = 'uploads/insp_'.uniqid().'.png';
        file_put_contents($file, $data);
        $pdo->prepare("INSERT INTO inspection_photos (contract_id, photo_type, photo_path) VALUES (?,?,?)")->execute([$id, $type, $file]);
        echo "<script>window.location='index.php?p=contract_view&id=$id';</script>";
    }
}
?>

<style>
    .tab-btn { background:none; border:none; color:#aaa; padding:15px 20px; font-size:16px; cursor:pointer; border-bottom:3px solid transparent; }
    .tab-btn.active { color:#6366f1; border-bottom-color:#6366f1; font-weight:bold; }
    .canvas-box { background:white; border-radius:10px; cursor:crosshair; width:100%; height:250px; touch-action:none; }
    .cam-box { width:100%; background:black; border-radius:10px; height:300px; object-fit:cover; }
    .photo-grid { display:grid; grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap:10px; margin-top:20px; }
    .p-thumb { width:100%; height:80px; object-fit:cover; border-radius:8px; border:1px solid #444; }
</style>

<div class="card">
    <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid #333; padding-bottom:15px; margin-bottom:20px">
        <h3>عقد رقم #<?= $c['id'] ?> - <?= $c['tname'] ?></h3>
        <a href="index.php?p=contracts" class="btn btn-dark">رجوع</a>
    </div>
    
    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:20px; margin-bottom:30px; background:#222; padding:20px; border-radius:10px;">
        <div><small style="color:#888">نوع الوحدة</small><div><?= $c['type'] ?></div></div>
        <div><small style="color:#888">اسم الوحدة</small><div><?= $c['unit_name'] ?></div></div>
        <div><small style="color:#888">قيمة العقد</small><div style="color:#10b981"><?= number_format($c['total_amount']) ?></div></div>
        <div><small style="color:#888">التاريخ</small><div><?= $c['start_date'] ?></div></div>
    </div>

    <div style="margin-bottom:20px; border-bottom:1px solid #333">
        <button onclick="openTab('sig')" class="tab-btn active">✍️ التوقيع الإلكتروني</button>
        <button onclick="openTab('in')" class="tab-btn">📷 صور الاستلام (قبل)</button>
        <button onclick="openTab('out')" class="tab-btn">📸 صور التسليم (بعد)</button>
    </div>

    <div id="tab-sig">
        <?php if($c['signature_img']): ?>
            <div style="text-align:center; padding:30px; background:#fff; border-radius:10px; color:black">
                <img src="<?= $c['signature_img'] ?>" style="height:150px">
                <div style="color:green; margin-top:10px; font-weight:bold">✔ تم توقيع العقد</div>
            </div>
        <?php else: ?>
            <div style="background:#fff; padding:10px; border-radius:10px;">
                <canvas id="sigCanvas" class="canvas-box"></canvas>
            </div>
            <div style="margin-top:15px; display:flex; gap:10px">
                <button onclick="saveSignature()" class="btn btn-primary">حفظ التوقيع واعتماد العقد</button>
                <button onclick="clearSignature()" class="btn btn-dark">مسح وإعادة</button>
            </div>
            <form id="sigForm" method="POST"><input type="hidden" name="save_sig" value="1"><input type="hidden" name="sig_data" id="sigData"></form>
        <?php endif; ?>
    </div>

    <div id="tab-in" style="display:none">
        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px">
            <div>
                <h4>التقاط صور (الحد الأقصى 10)</h4>
                <video id="video-in" class="cam-box" autoplay playsinline></video>
                <button onclick="snap('in')" class="btn btn-primary" style="width:100%; margin-top:10px"><i class="fa-solid fa-camera"></i> التقاط</button>
            </div>
            <div>
                <h4>الصور المحفوظة</h4>
                <div class="photo-grid">
                    <?php 
                    $photos = $pdo->query("SELECT * FROM inspection_photos WHERE contract_id=$id AND photo_type='check_in'");
                    while($p=$photos->fetch()) echo "<a href='{$p['photo_path']}' target='_blank'><img src='{$p['photo_path']}' class='p-thumb'></a>";
                    ?>
                </div>
            </div>
        </div>
    </div>

    <div id="tab-out" style="display:none">
        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px">
            <div>
                <h4>التقاط صور (الحد الأقصى 10)</h4>
                <video id="video-out" class="cam-box" autoplay playsinline></video>
                <button onclick="snap('out')" class="btn btn-danger" style="width:100%; margin-top:10px"><i class="fa-solid fa-camera"></i> التقاط</button>
            </div>
            <div>
                <h4>الصور المحفوظة</h4>
                <div class="photo-grid">
                    <?php 
                    $photos = $pdo->query("SELECT * FROM inspection_photos WHERE contract_id=$id AND photo_type='check_out'");
                    while($p=$photos->fetch()) echo "<a href='{$p['photo_path']}' target='_blank'><img src='{$p['photo_path']}' class='p-thumb'></a>";
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<form id="photoForm" method="POST" style="display:none">
    <input type="hidden" name="save_photo" value="1">
    <input type="hidden" name="photo_type" id="pType">
    <input type="hidden" name="photo_data" id="pData">
</form>

<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>
<script>
    // 1. التبويبات
    function openTab(t) {
        document.getElementById('tab-sig').style.display='none';
        document.getElementById('tab-in').style.display='none';
        document.getElementById('tab-out').style.display='none';
        document.getElementById('tab-'+t).style.display='block';
        if(t !== 'sig') startCamera(t);
    }

    // 2. التوقيع
    var canvas = document.getElementById('sigCanvas');
    if(canvas) {
        var signaturePad = new SignaturePad(canvas, {backgroundColor: 'rgb(255, 255, 255)'});
        function clearSignature() { signaturePad.clear(); }
        function saveSignature() {
            if(signaturePad.isEmpty()) return alert('الرجاء التوقيع أولاً');
            document.getElementById('sigData').value = signaturePad.toDataURL();
            document.getElementById('sigForm').submit();
        }
    }

    // 3. الكاميرا
    function startCamera(type) {
        const video = document.getElementById('video-'+type);
        if(navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
            navigator.mediaDevices.getUserMedia({ video: { facingMode: "environment" } }).then(function(stream) {
                video.srcObject = stream;
                video.play();
            });
        }
    }

    function snap(type) {
        const video = document.getElementById('video-'+type);
        const canvas = document.createElement('canvas');
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        canvas.getContext('2d').drawImage(video, 0, 0);
        
        document.getElementById('pType').value = (type === 'in') ? 'check_in' : 'check_out';
        document.getElementById('pData').value = canvas.toDataURL('image/png');
        document.getElementById('photoForm').submit();
    }
</script>
