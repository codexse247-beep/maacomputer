<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_videos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('class_no')->nullable();
            $table->string('title');
            $table->string('type', 10); // upload | url
            $table->string('path')->nullable(); // private "local" disk
            $table->string('url', 500)->nullable();
            $table->timestamps();
            $table->index(['department_id', 'class_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_videos');
    }
};
