<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Office costs, for the income / expense (profit) report.
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->date('spent_on')->index();
            $table->string('category', 40);
            $table->unsignedInteger('amount');
            $table->string('method', 30)->nullable();
            $table->string('note')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        // Fees a student says they sent by bKash / Nagad / Rocket; the office checks and approves them.
        Schema::create('online_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('method', 20);
            $table->unsignedInteger('amount');
            $table->string('sender', 20);
            $table->string('trx_id', 40)->unique();
            $table->string('status', 10)->default('pending')->index();
            $table->string('note')->nullable();
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });

        // Every SMS / email the site sent (or failed to send).
        Schema::create('message_logs', function (Blueprint $table) {
            $table->id();
            $table->string('channel', 10); // sms | email
            $table->string('event', 30)->nullable();
            $table->string('to');
            $table->text('message');
            $table->string('status', 10); // sent | failed | skipped
            $table->string('response', 500)->nullable();
            $table->foreignId('student_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
            $table->index(['event', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_logs');
        Schema::dropIfExists('online_payments');
        Schema::dropIfExists('expenses');
    }
};
