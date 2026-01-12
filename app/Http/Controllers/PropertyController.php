<?php

namespace App\Http\Controllers;

use App\Models\Property;
use Illuminate\Http\Request;

class PropertyController extends Controller
{
    // عرض قائمة العقارات
    public function index()
    {
        $properties = Property::withCount('units')->get();
        return view('properties.index', compact('properties'));
    }

    // حفظ عقار جديد
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'manager_phone' => 'nullable|numeric',
        ]);

        Property::create($request->all());

        return redirect()->route('properties.index')->with('success', 'تم إضافة العقار بنجاح');
    }
    
    // حذف عقار
    public function destroy($id)
    {
        Property::destroy($id);
        return back()->with('success', 'تم الحذف بنجاح');
    }
}
