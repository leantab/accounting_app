<?php

use App\Models\Company;
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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained();
            $table->foreignId('from_company_id')->constrained('companies');
            $table->foreignId('to_company_id')->constrained('companies');
            $table->string('name');
            $table->text('desctiption')->nullable();
            $table->date('date')->nullable();
            $table->double('total_amount', 20, 2)->nullable();
            $table->double('payed_amount', 20, 2)->nullable();
            $table->boolean('payed')->default(false);
            $table->date('payment_due_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
