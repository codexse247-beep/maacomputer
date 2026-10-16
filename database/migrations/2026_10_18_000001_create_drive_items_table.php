<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Admin › ড্রাইভ: the office's own files and folders (like Google Drive). Files live in storage/app/private/drive. */
    public function up(): void
    {
        Schema::create('drive_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('drive_items')->cascadeOnDelete();
            $table->boolean('is_folder')->default(false);
            $table->string('name');
            $table->string('path')->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->string('mime', 150)->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('starred')->default(false);
            $table->string('share_token', 40)->nullable()->unique();
            $table->softDeletes(); // = in the trash
            $table->timestamps();
            $table->index(['parent_id', 'deleted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drive_items');
    }
};
