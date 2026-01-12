namespace App\Http\Controllers;

use App\Models\Property;
use Illuminate\Http\Request;

class PropertyController extends Controller
{
    public function store(Request $request)
    {
        // 1. التحقق من صحة البيانات (Validation) - معايير الأمان
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'manager_name' => 'required|string|max:255',
            'manager_phone' => 'required|numeric',
            'address' => 'nullable|string',
        ]);

        // 2. إنشاء السجل في قاعدة البيانات
        Property::create($validated);

        // 3. إعادة التوجيه مع رسالة نجاح
        return redirect()->back()->with('success', 'تم إضافة العقار بنجاح');
    }
}
