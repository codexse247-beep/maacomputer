<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** English versions of website text (shown on the English website; Bangla is used when empty). */
    public function up(): void
    {
        foreach (['departments', 'teachers', 'faqs'] as $table) {
            Schema::table($table, fn (Blueprint $t) => $t->json('en')->nullable());
        }
    }

    public function down(): void
    {
        foreach (['departments', 'teachers', 'faqs'] as $table) {
            Schema::table($table, fn (Blueprint $t) => $t->dropColumn('en'));
        }
    }
};
