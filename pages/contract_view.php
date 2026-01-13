<?php
// pages/contract_view.php
$id = $_GET['id'] ?? 0;
$c = $pdo->query("SELECT c.*, t.name as tname, u.unit_name, u.type, p.name as pname 
                  FROM contracts c 
                  JOIN tenants t ON c.tenant_id=t.id 
                  JOIN units u ON c.unit_id=u.id 
                  JOIN properties p ON u.property_id=p.id 
                  WHERE c.id=$id")->fetch();

if(!$c) die("العقد غير موجود");

// معالجة حفظ التوقيع
if(isset($_POST['save_signature'])){
    $img = $_POST['sig_data'];
    $img = str_replace('data:image/png;base64,', '', $img);
    $img = str_replace(' ', '+', $img);
    $data = base64_decode($img);
    $file = 'uploads/sig_' . uniqid() . '.png';
    file_put_contents($file, $data);
    $pdo->prepare("UPDATE contracts SET signature_img=? WHERE id=?")->execute([$file, $id]);
    echo "<script>window.location='index.php?p=contract_view&id=$id';</script>";
}

// معالجة صور الفحص (Camera)
if(isset($_POST['save_photos'])){
    foreach($_POST['photos'] as $base64){
        if(empty($base64)) continue;
        $img = str_replace('data:image/png;base64,', '', $base64);
        $img = str_replace(' ', '+', $img);
        $data = base64_decode($img);
        $file = 'uploads/insp_' . uniqid() . '.png';
        file_put_contents($file, $data);
        $pdo->prepare("INSERT INTO inspection_photos (contract_id, photo_type, photo_path) VALUES (?,?,?)")
            ->execute([$id, $_POST['type'], $file]);
    }
    echo "<script>window.location='index.php?p=contract_view&id=$id';</script>";
}

// جلب الصور المحفوظة
$check_in_photos = $pdo->query("SELECT * FROM inspection_photos WHERE contract_id=$id AND photo_type='check_in'")->fetchAll();
$check_out_photos = $pdo->query("SELECT * FROM inspection_photos WHERE contract_id=$id AND photo_type='check_out'")->fetchAll();
?>

<style>
    /* تنسيق خاص للتوقيع والكاميرا */
    .sig-canvas { border: 2px dashed #444; background: #fff; border-radius: 10px; cursor: crosshair; width: 100%; height: 200px; }
    .cam-feed { width: 100%; border-radius: 10px; background: #000; transform: scaleX(-1); }
    .photo-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap: 10px; margin-top: 15px; }
    .photo-thumb { width: 100%; height: 80px; object-fit: cover; border-radius: 8px; border: 1px solid #333; }
    .tab-btn { background: transparent; border: none; color: #aaa; padding: 10px 20px; font-weight: bold; cursor: pointer; border-bottom: 3px solid transparent; }
    .tab-btn.active { color: var(--primary); border-bottom-color: var(--primary); }
</style>

<div class="card">
    <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid #333; padding-bottom:15px; margin-bottom:20px">
        <h2 style="margin:0">عقد إيجار #<?= $c['id'] ?></h2>
        <a href="index.php?p=contracts" class="btn btn-dark">رجوع</a>
    </div>

    <div style="display:grid; grid-template-columns: repeat(4, 1fr); gap:20px; margin-bottom:30px; background:#151515; padding:20px; border-radius:15px;">
        <div><small style="color:#888">المستأجر</small><div style="font-weight:bold"><?= $c['tname'] ?></div></div>
        <div><small style="color:#888">الوحدة</small><div style="font-weight:bold"><?= $c['unit_name'] ?> (<?= $c['pname'] ?>)</div></div>
        <div><small style="color:#888">مدة العقد</small><div style="font-weight:bold"><?= $c['start_date'] ?> <span style="color:#primary">➜</span> <?= $c['end_date'] ?></div></div>
        <div><small style="color:#888">القيمة</small><div style="font-weight:bold; color:#10b981"><?= number_format($c['total_amount']) ?> ريال</div></div>
    </div>

    <div style="border-bottom:1px solid #333; margin-bottom:20px;">
        <button class="tab-btn active" onclick="showTab('sig')">✍️ التوقيع الإلكتروني</button>
        <button class="tab-btn" onclick="showTab('in')">📷 استلام الوحدة (قبل)</button>
        <button class="tab-btn" onclick="showTab('out')">📸 تسليم الوحدة (بعد)</button>
    </div>

    <div id="sig-tab">
        <?php if($c['signature_img']): ?>
            <div style="text-align:center; padding:30px; background:#f9f9f9; border-radius:10px;">
                <h4 style="color:black">التوقيع المعتمد</h4>
                <img src="<?= $c['signature_img'] ?>" style="max-height:150px">
                <p style="color:green; margin-top:10px">✔ تم التوقيع والحفظ</p>
            </div>
        <?php else: ?>
            <div style="background:#222; padding:20px; border-radius:15px;">
                <p style="color:#ccc; margin-bottom:10px">الرجاء التوقيع داخل المربع أدناه (باستخدام الإصبع أو الماوس)</p>
                <canvas id="signature-pad" class="sig-canvas" width="600" height="200"></canvas>
                <div style="margin-top:15px; display:flex; gap:10px">
                    <form method="POST" id="sigForm">
                        <input type="hidden" name="save_signature" value="1">
                        <input type="hidden" name="sig_data" id="sig-data">
                        <button type="button" onclick="saveSig()" class="btn btn-primary">حفظ التوقيع</button>
                    </form>
                    <button onclick="clearSig()" class="btn btn-dark">مسح وإعادة</button>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div id="in-tab" style="display:none">
        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px">
            <div class="card" style="margin:0">
                <h4>التقاط صور (الحد الأقصى 10)</h4>
                <video id="video-in" class="cam-feed" autoplay playsinline></video>
                <button onclick="takePhoto('in')" class="btn btn-primary" style="width:100%; margin-top:10px"><i class="fa-solid fa-camera"></i> التقاط صورة</button>
                <canvas id="canvas-in" style="display:none"></canvas>
            </div>
            <div class="card" style="margin:0">
                <h4>الصور الملتقطة</h4>
                <form method="POST" id="form-in">
                    <input type="hidden" name="save_photos" value="1">
                    <input type="hidden" name="type" value="check_in">
                    <div id="gallery-in" class="photo-grid"></div>
                    <button class="btn btn-primary" style="width:100%; margin-top:20px">حفظ الصور في النظام</button>
                </form>
            </div>
        </div>
        <?php if($check_in_photos): ?>
            <h4 style="margin-top:20px">سجل الصور المحفوظة</h4>
            <div class="photo-grid">
                <?php foreach($check_in_photos as $p): ?>
                    <a href="<?= $p['photo_path'] ?>" target="_blank"><img src="<?= $p['photo_path'] ?>" class="photo-thumb"></a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div id="out-tab" style="display:none">
        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px">
            <div class="card" style="margin:0">
                <h4>التقاط صور التسليم</h4>
                <video id="video-out" class="cam-feed" autoplay playsinline></video>
                <button onclick="takePhoto('out')" class="btn btn-danger" style="width:100%; margin-top:10px"><i class="fa-solid fa-camera"></i> التقاط صورة</button>
                <canvas id="canvas-out" style="display:none"></canvas>
            </div>
            <div class="card" style="margin:0">
                <h4>الصور الملتقطة</h4>
                <form method="POST" id="form-out">
                    <input type="hidden" name="save_photos" value="1">
                    <input type="hidden" name="type" value="check_out">
                    <div id="gallery-out" class="photo-grid"></div>
                    <button class="btn btn-danger" style="width:100%; margin-top:20px">حفظ صور التسليم</button>
                </form>
            </div>
        </div>
        <?php if($check_out_photos): ?>
            <h4 style="margin-top:20px">سجل صور التسليم</h4>
            <div class="photo-grid">
                <?php foreach($check_out_photos as $p): ?>
                    <a href="<?= $p['photo_path'] ?>" target="_blank"><img src="<?= $p['photo_path'] ?>" class="photo-thumb"></a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>
<script>
    // 1. منطق التبويبات
    function showTab(id) {
        document.getElementById('sig-tab').style.display = 'none';
        document.getElementById('in-tab').style.display = 'none';
        document.getElementById('out-tab').style.display = 'none';
        document.getElementById(id+'-tab').style.display = 'block';
        
        // تشغيل الكاميرا عند فتح التبويب
        if(id === 'in') startCamera('in');
        if(id === 'out') startCamera('out');
        
        // تحديث الأزرار
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        event.target.classList.add('active');
    }

    // 2. منطق التوقيع
    var canvas = document.getElementById('signature-pad');
    if(canvas){
        var signaturePad = new SignaturePad(canvas, { backgroundColor: 'rgb(255, 255, 255)' });
        function clearSig() { signaturePad.clear(); }
        function saveSig() {
            if (signaturePad.isEmpty()) { alert("الرجاء التوقيع أولاً"); return; }
            document.getElementById('sig-data').value = signaturePad.toDataURL();
            document.getElementById('sigForm').submit();
        }
    }

    // 3. منطق الكاميرا
    let streams = {};
    function startCamera(type) {
        const video = document.getElementById('video-' + type);
        if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
            navigator.mediaDevices.getUserMedia({ video: { facingMode: "environment" } }).then(function(stream) {
                video.srcObject = stream;
                video.play();
                streams[type] = stream;
            });
        }
    }

    let photosCount = { in: 0, out: 0 };
    function takePhoto(type) {
        if(photosCount[type] >= 10) { alert("الحد الأقصى 10 صور فقط"); return; }
        
        const video = document.getElementById('video-' + type);
        const canvas = document.getElementById('canvas-' + type);
        const context = canvas.getContext('2d');
        const gallery = document.getElementById('gallery-' + type);
        
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        context.drawImage(video, 0, 0, canvas.width, canvas.height);
        
        const dataURL = canvas.toDataURL('image/png');
        
        // إنشاء عنصر الصورة المخفية للإرسال
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'photos[]';
        input.value = dataURL;
        
        // إنشاء مصغرة للعرض
        const img = document.createElement('img');
        img.src = dataURL;
        img.className = 'photo-thumb';
        
        const div = document.createElement('div');
        div.appendChild(img);
        div.appendChild(input);
        
        gallery.appendChild(div);
        photosCount[type]++;
    }
</script>
