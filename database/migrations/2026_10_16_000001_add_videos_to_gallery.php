<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gallery_photos', function (Blueprint $table) {
            $table->string('type', 10)->default('image')->after('id');
            $table->string('youtube_id', 20)->nullable()->after('type');
            // Videos have no uploaded file; YouTube's own thumbnail is shown.
            $table->string('path')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('gallery_photos', function (Blueprint $table) {
            $table->dropColumn(['type', 'youtube_id']);
        });
    }
};
