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
        Schema::create('time_tracker_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('time_tracker_id')->constrained()->cascadeOnDelete();
            $table->foreignId('time_tracker_item_type_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->integer('hours')->nullable();
            $table->time('time_start')->nullable();
            $table->time('time_end')->nullable();
            $table->text('description')->nullable();
            $table->decimal('rate', 10, 2)->nullable();
            $table->decimal('amount', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('time_tracker_items');
    }
};
