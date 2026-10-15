<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course_videos', function (Blueprint $table) {
            $table->text('description')->nullable()->after('title');
            // null = every batch of the course
            $table->foreignId('batch_id')->nullable()->after('department_id')->constrained()->nullOnDelete();
            $table->date('release_on')->nullable()->after('url');
            $table->boolean('paid_only')->default(false)->after('release_on');
        });

        // Notes / practice files attached to a class.
        Schema::create('course_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_video_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('path'); // private "local" disk
            $table->unsignedInteger('size')->default(0);
            $table->timestamps();
        });

        // Which student has watched which class.
        Schema::create('video_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_video_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['student_id', 'course_video_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('video_views');
        Schema::dropIfExists('course_files');
        Schema::table('course_videos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('batch_id');
            $table->dropColumn(['description', 'release_on', 'paid_only']);
        });
    }
};
