<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('admin')->after('email');
        });

        Schema::create('teachers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->string('subject')->nullable();
            $table->string('phone', 20)->nullable();
            $table->text('bio')->nullable();
            $table->string('photo')->nullable();
            $table->boolean('show_on_site')->default(true);
            $table->boolean('active')->default(true);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::table('batches', function (Blueprint $table) {
            $table->foreignId('teacher_id')->nullable()->after('teacher')->constrained()->nullOnDelete();
        });

        Schema::table('departments', function (Blueprint $table) {
            $table->text('learn')->nullable()->after('topics');
            $table->string('routine_note')->nullable()->after('learn');
        });

        Schema::table('students', function (Blueprint $table) {
            $table->string('certificate_no', 40)->nullable()->unique()->after('status');
            $table->date('certificate_issued_on')->nullable()->after('certificate_no');
        });

        Schema::table('assessments', function (Blueprint $table) {
            $table->string('remark')->nullable()->after('status');
            $table->boolean('published')->default(true)->after('remark');
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->string('status', 10)->default('present')->after('present');
        });
        DB::table('attendances')->where('present', false)->update(['status' => 'absent']);

        Schema::table('notices', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->after('batch_id')->constrained()->cascadeOnDelete();
        });

        Schema::create('enquiries', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20);
            $table->string('name');
            $table->string('father_name')->nullable();
            $table->string('phone', 20);
            $table->string('email')->nullable();
            $table->date('dob')->nullable();
            $table->string('education')->nullable();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->string('preferred_time', 20)->nullable();
            $table->string('student_code', 30)->nullable();
            $table->string('subject')->nullable();
            $table->text('message')->nullable();
            $table->string('status', 10)->default('new');
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->string('key', 60)->primary();
            $table->text('value')->nullable();
        });

        Schema::create('gallery_photos', function (Blueprint $table) {
            $table->id();
            $table->string('path');
            $table->string('caption')->nullable();
            $table->string('category', 20)->default('lab');
            $table->timestamps();
        });

        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            $table->string('question');
            $table->text('answer');
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['faqs', 'gallery_photos', 'settings', 'enquiries'] as $t) {
            Schema::dropIfExists($t);
        }
        Schema::table('notices', fn (Blueprint $t) => $t->dropConstrainedForeignId('department_id'));
        Schema::table('attendances', fn (Blueprint $t) => $t->dropColumn('status'));
        Schema::table('assessments', fn (Blueprint $t) => $t->dropColumn(['remark', 'published']));
        Schema::table('students', fn (Blueprint $t) => $t->dropColumn(['certificate_no', 'certificate_issued_on']));
        Schema::table('departments', fn (Blueprint $t) => $t->dropColumn(['learn', 'routine_note']));
        Schema::table('batches', fn (Blueprint $t) => $t->dropConstrainedForeignId('teacher_id'));
        Schema::dropIfExists('teachers');
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn('role'));
    }
};
