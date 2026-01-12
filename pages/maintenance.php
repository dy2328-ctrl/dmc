<div id="maintModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <div class="modal-title"><i class="fa-solid fa-screwdriver-wrench"></i> طلب صيانة جديد</div>
            <span onclick="this.parentElement.parentElement.parentElement.style.display='none'" style="cursor:pointer; color:#ef4444; font-size:20px"><i class="fa-solid fa-xmark"></i></span>
        </div>
        
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="add_maint" value="1">
            
            <div class="inp-grid">
                <div class="inp-group">
                    <label class="inp-label">العقار</label>
                    <select name="pid" class="inp">
                        <?php $ps=$pdo->query("SELECT * FROM properties"); while($p=$ps->fetch()) echo "<option value='{$p['id']}'>{$p['name']}</option>"; ?>
                    </select>
                </div>
                <div class="inp-group">
                    <label class="inp-label">الوحدة المتضررة</label>
                    <select name="uid" class="inp">
                        <?php $us=$pdo->query("SELECT * FROM units"); while($u=$us->fetch()) echo "<option value='{$u['id']}'>{$u['unit_name']}</option>"; ?>
                    </select>
                </div>
            </div>

            <div class="inp-group">
                <label class="inp-label">وصف المشكلة</label>
                <input type="text" name="desc" class="inp" placeholder="مثال: تسريب مياه في المطبخ..." required>
            </div>

            <div class="inp-grid">
                <div class="inp-group">
                    <label class="inp-label">المقاول المعتمد</label>
                    <select name="vid" class="inp">
                        <option value="">-- اختر --</option>
                        <?php $vs=$pdo->query("SELECT * FROM vendors"); while($v=$vs->fetch()) echo "<option value='{$v['id']}'>{$v['name']}</option>"; ?>
                    </select>
                </div>
                <div class="inp-group">
                    <label class="inp-label">التكلفة التقديرية</label>
                    <input type="number" name="cost" class="inp" placeholder="0.00">
                </div>
            </div>

            <button class="btn btn-primary" style="width:100%; margin-top:10px">
                <i class="fa-solid fa-paper-plane"></i> إرسال الطلب
            </button>
        </form>
    </div>
</div>
