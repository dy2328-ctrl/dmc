// تحديث دالة analyzeIDCard لتكون ذكية حقاً
public function analyzeIDCard($imagePath) {
    // هنا نستخدم مكتبة مثل Google Cloud Vision بدلاً من البيانات العشوائية
    // هذا كود توضيحي للمنطق
    $image = file_get_contents($imagePath);
    $base64 = base64_encode($image);
    
    // إرسال لـ API خارجي (مثال)
    $result = $this->callExternalOCR($base64); 
    
    // تحليل النص الراجع (Regex) لاستخراج رقم الهوية
    preg_match('/[0-9]{10}/', $result['text'], $matches);
    
    return [
        'success' => true,
        'data' => [
            'extracted_name' => $result['name_candidate'], // استخراج الاسم بالذكاء الاصطناعي
            'id_number' => $matches[0] ?? null,
            'confidence' => $result['confidence']
        ]
    ];
}

// دالة جديدة: تحليل تكرار الأعطال
public function predictMaintenance($unit_id) {
    // جلب تواريخ الأعطال السابقة لنفس الوحدة
    $stmt = $this->pdo->prepare("SELECT request_date FROM maintenance WHERE unit_id = ? ORDER BY request_date ASC");
    $stmt->execute([$unit_id]);
    $dates = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if(count($dates) < 3) return "بيانات غير كافية";

    // حساب متوسط الأيام بين الأعطال
    $diffs = [];
    for($i=1; $i<count($dates); $i++) {
        $diffs[] = (strtotime($dates[$i]) - strtotime($dates[$i-1])) / (60*60*24);
    }
    $avgDays = array_sum($diffs) / count($diffs);
    
    return "بناءً على السجل، هذه الوحدة تتعطل كل " . round($avgDays) . " يوم تقريباً.";
}
