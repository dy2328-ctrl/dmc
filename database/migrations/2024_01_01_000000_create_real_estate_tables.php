<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. جدول العقارات (المباني)
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // اسم العقار
            $table->string('manager_name')->nullable(); // اسم المسؤول
            $table->string('manager_phone')->nullable(); // هاتف المسؤول
            $table->string('address')->nullable(); // العنوان
            $table->string('email')->nullable(); // البريد الإلكتروني
            $table->text('notes')->nullable(); // ملاحظات
            $table->timestamps();
        });

        // 2. جدول الوحدات (الشقق/المحلات)
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained('properties')->onDelete('cascade');
            $table->string('unit_name'); // اسم الوحدة
            $table->string('unit_number'); // رقم الوحدة
            $table->decimal('yearly_price', 15, 2)->default(0); // السعر السنوي
            $table->integer('floor_number')->nullable(); // رقم الطابق
            $table->integer('rooms_count')->nullable(); // عدد الغرف
            $table->enum('status', ['available', 'rented', 'maintenance'])->default('available'); // الحالة
            $table->enum('type', ['residential', 'commercial'])->default('residential'); // النوع
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // 3. جدول المستأجرين
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('national_id')->unique(); // الهوية
            $table->string('phone');
            $table->string('email')->nullable();
            $table->timestamps();
        });

        // 4. جدول العقود
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants');
            $table->foreignId('unit_id')->constrained('units');
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('total_amount', 15, 2);
            $table->enum('payment_cycle', ['monthly', 'quarterly', 'yearly']);
            $table->string('contract_file')->nullable(); // ملف العقد PDF
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contracts');
        Schema::dropIfExists('tenants');
        Schema::dropIfExists('units');
        Schema::dropIfExists('properties');
    }
};
