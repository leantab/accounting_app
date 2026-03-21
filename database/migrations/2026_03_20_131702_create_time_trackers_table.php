<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('time_trackers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete()->nullable();
            $table->string('name');
            $table->date('date_start');
            $table->date('date_end');
            $table->decimal('hours', 5, 2)->nullable();
            $table->text('description')->nullable();
            $table->boolean('billed')->default(false);
            $table->decimal('amount', 10, 2)->nullable();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->boolean('paid')->default(false);
            $table->foreignId('payment_id')->constrained()->cascadeOnDelete();
            $table->date('paid_date')->nullable();
            $table->boolean('approved')->default(false);
            $table->date('approved_at')->nullable();
            $table->foreignId('approved_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('time_trackers');
    }
};
