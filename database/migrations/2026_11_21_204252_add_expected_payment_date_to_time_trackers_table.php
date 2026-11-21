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
        Schema::table('time_trackers', function (Blueprint $table) {
            $table->date('expected_payment_date')->nullable()->after('paid_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('time_trackers', function (Blueprint $table) {
            $table->dropColumn('expected_payment_date');
        });
    }
};
