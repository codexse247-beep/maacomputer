<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique();
            $table->string('name');
            $table->string('duration')->nullable();
            $table->text('blurb')->nullable();
            $table->string('course_name')->nullable();
            $table->unsignedInteger('course_fee')->default(0);
            $table->unsignedInteger('admission_fee')->default(0);
            $table->string('modules')->nullable();
            $table->string('topics')->nullable();
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->string('days')->nullable();
            $table->string('class_time')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('teacher')->nullable();
            $table->string('room')->nullable();
            $table->unsignedSmallInteger('capacity')->default(25);
            $table->string('status', 20)->default('enrolling');
            $table->timestamps();
        });

        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained()->restrictOnDelete();
            $table->string('student_code', 30)->unique();
            $table->unsignedSmallInteger('roll');
            $table->string('name_bn');
            $table->string('name_en');
            $table->string('father_name');
            $table->string('mother_name');
            $table->date('dob')->nullable();
            $table->string('gender', 20);
            $table->string('blood_group', 10)->nullable();
            $table->string('religion', 30)->nullable();
            $table->string('nationality', 50)->nullable();
            $table->string('nid', 40)->nullable();
            $table->string('marital', 20)->nullable();
            $table->string('father_job')->nullable();
            $table->string('mother_job')->nullable();
            $table->string('guardian_phone', 20);
            $table->string('emergency_name')->nullable();
            $table->string('emergency_relation')->nullable();
            $table->string('emergency_phone', 20)->nullable();
            $table->string('phone', 20);
            $table->string('whatsapp', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('present_village');
            $table->string('present_po')->nullable();
            $table->string('present_upazila');
            $table->string('present_district');
            $table->string('perm_village')->nullable();
            $table->string('perm_po')->nullable();
            $table->string('perm_upazila')->nullable();
            $table->string('perm_district')->nullable();
            $table->json('education')->nullable();
            $table->string('prior_knowledge', 20)->nullable();
            $table->string('goal')->nullable();
            $table->date('admission_date');
            $table->string('admission_type', 30)->default('নতুন ভর্তি');
            $table->string('reference')->nullable();
            $table->unsignedInteger('course_fee')->default(0);
            $table->unsignedInteger('admission_fee')->default(0);
            $table->unsignedInteger('discount')->default(0);
            $table->unsignedTinyInteger('installments')->default(1);
            $table->date('next_installment_date')->nullable();
            $table->unsignedInteger('next_installment_amount')->nullable();
            $table->string('status', 20)->default('active');
            $table->string('photo')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->foreignId('admitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['batch_id', 'roll']);
        });

        Schema::create('student_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('type', 30);
            $table->string('path');
            $table->string('original_name');
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('receipt_no', 20)->unique();
            $table->date('paid_on');
            $table->string('item');
            $table->unsignedInteger('amount');
            $table->string('method', 30);
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->date('held_on')->nullable();
            $table->unsignedSmallInteger('score')->nullable();
            $table->unsignedSmallInteger('max_score')->default(100);
            $table->string('status', 20)->default('pending');
            $table->timestamps();
        });

        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->boolean('present')->default(true);
            $table->timestamps();
            $table->unique(['student_id', 'date']);
        });

        Schema::create('student_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('body');
            $table->timestamps();
        });

        Schema::create('notices', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('tag', 20);
            $table->date('published_on');
            $table->text('body')->nullable();
            $table->boolean('is_public')->default(true);
            $table->foreignId('batch_id')->nullable()->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['notices', 'student_notes', 'attendances', 'assessments', 'payments', 'student_documents', 'students', 'batches', 'departments'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
