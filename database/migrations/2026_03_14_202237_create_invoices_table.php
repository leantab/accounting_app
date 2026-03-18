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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained();
            $table->foreignId('from_company_id')->constrained('companies');
            $table->foreignId('to_company_id')->constrained('companies');
            $table->string('name');
            $table->string('invoice_number')->nullable();
            $table->text('description')->nullable();
            $table->date('date')->nullable();
            $table->decimal('total_amount', 20, 2)->nullable();
            $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('tax_percentage', 5, 2)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('final_amount', 20, 2)->nullable();
            $table->date('payment_due_date')->nullable();
            $table->decimal('payed_amount', 20, 2)->nullable();
            $table->boolean('payed')->default(false);
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
