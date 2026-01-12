public function store(Request $request)
{
    // 1. التحقق الصارم (Validation) لمنع حقن البيانات
    $validated = $request->validate([
        'unit_name' => 'required|string|max:255',
        'yearly_price' => 'required|numeric|min:0',
        'photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048', // حماية من الملفات الضارة
    ]);

    // 2. التخزين الآمن للصور (بدلاً من Base64)
    if ($request->hasFile('photo')) {
        // يتم تخزين الصورة في مجلد محمي مع إعادة تسميتها تلقائياً
        $path = $request->file('photo')->store('units', 'public');
        $validated['photo_url'] = '/storage/' . $path;
    }

    // 3. استخدام Eloquent ORM للحماية من SQL Injection
    $unit = Unit::create($validated);

    return redirect()->back()->with('success', 'تمت إضافة الوحدة بنظام آمن.');
}
