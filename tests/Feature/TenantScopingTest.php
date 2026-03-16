<?php

use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\User;
// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

use function Pest\Laravel\actingAs;

uses(TestCase::class); //, RefreshDatabase::class);

test('non admin users only see records for their customer', function (): void {
    $tenantCustomer = Customer::factory()->create();
    $otherCustomer = Customer::factory()->create();

    $tenantUser = User::factory()->create([
        'customer_id' => $tenantCustomer->id,
        'is_admin' => false,
    ]);

    $otherTenantUser = User::factory()->create([
        'customer_id' => $otherCustomer->id,
        'is_admin' => false,
    ]);

    $tenantCompany = Company::factory()->create(['customer_id' => $tenantCustomer->id]);
    $otherCompany = Company::factory()->create(['customer_id' => $otherCustomer->id]);

    $tenantInvoice = Invoice::factory()->create([
        'customer_id' => $tenantCustomer->id,
        'from_company_id' => $tenantCompany->id,
        'to_company_id' => $tenantCompany->id,
    ]);

    $otherInvoice = Invoice::factory()->create([
        'customer_id' => $otherCustomer->id,
        'from_company_id' => $otherCompany->id,
        'to_company_id' => $otherCompany->id,
    ]);

    $tenantPayment = Payment::factory()->create([
        'customer_id' => $tenantCustomer->id,
        'invoice_id' => $tenantInvoice->id,
        'from_company_id' => $tenantCompany->id,
        'to_company_id' => $tenantCompany->id,
    ]);

    $otherPayment = Payment::factory()->create([
        'customer_id' => $otherCustomer->id,
        'invoice_id' => $otherInvoice->id,
        'from_company_id' => $otherCompany->id,
        'to_company_id' => $otherCompany->id,
    ]);

    $tenantItem = InvoiceItem::factory()->create([
        'customer_id' => $tenantCustomer->id,
        'invoice_id' => $tenantInvoice->id,
    ]);

    $otherItem = InvoiceItem::factory()->create([
        'customer_id' => $otherCustomer->id,
        'invoice_id' => $otherInvoice->id,
    ]);

    actingAs($tenantUser);

    expect(Company::query()->pluck('id'))
        ->toContain($tenantCompany->id)
        ->not->toContain($otherCompany->id);

    expect(Invoice::query()->pluck('id'))
        ->toContain($tenantInvoice->id)
        ->not->toContain($otherInvoice->id);

    expect(Payment::query()->pluck('id'))
        ->toContain($tenantPayment->id)
        ->not->toContain($otherPayment->id);

    expect(InvoiceItem::query()->pluck('id'))
        ->toContain($tenantItem->id)
        ->not->toContain($otherItem->id);

    actingAs($otherTenantUser);

    expect(Company::query()->pluck('id'))
        ->toContain($otherCompany->id)
        ->not->toContain($tenantCompany->id);

    expect(Invoice::query()->pluck('id'))
        ->toContain($otherInvoice->id)
        ->not->toContain($tenantInvoice->id);

    expect(Payment::query()->pluck('id'))
        ->toContain($otherPayment->id)
        ->not->toContain($tenantPayment->id);

    expect(InvoiceItem::query()->pluck('id'))
        ->toContain($otherItem->id)
        ->not->toContain($tenantItem->id);
});

test('admin users can see records for all customers', function (): void {
    $tenantCustomer = Customer::factory()->create();
    $otherCustomer = Customer::factory()->create();

    $admin = User::factory()->create([
        'customer_id' => $tenantCustomer->id,
        'is_admin' => true,
    ]);

    $tenantCompany = Company::factory()->create(['customer_id' => $tenantCustomer->id]);
    $otherCompany = Company::factory()->create(['customer_id' => $otherCustomer->id]);

    $tenantInvoice = Invoice::factory()->create([
        'customer_id' => $tenantCustomer->id,
        'from_company_id' => $tenantCompany->id,
        'to_company_id' => $tenantCompany->id,
    ]);

    $otherInvoice = Invoice::factory()->create([
        'customer_id' => $otherCustomer->id,
        'from_company_id' => $otherCompany->id,
        'to_company_id' => $otherCompany->id,
    ]);

    $tenantPayment = Payment::factory()->create([
        'customer_id' => $tenantCustomer->id,
        'invoice_id' => $tenantInvoice->id,
        'from_company_id' => $tenantCompany->id,
        'to_company_id' => $tenantCompany->id,
    ]);

    $otherPayment = Payment::factory()->create([
        'customer_id' => $otherCustomer->id,
        'invoice_id' => $otherInvoice->id,
        'from_company_id' => $otherCompany->id,
        'to_company_id' => $otherCompany->id,
    ]);

    $tenantItem = InvoiceItem::factory()->create([
        'customer_id' => $tenantCustomer->id,
        'invoice_id' => $tenantInvoice->id,
    ]);

    $otherItem = InvoiceItem::factory()->create([
        'customer_id' => $otherCustomer->id,
        'invoice_id' => $otherInvoice->id,
    ]);

    actingAs($admin);

    expect(Company::query()->pluck('id'))
        ->toContain($tenantCompany->id)
        ->toContain($otherCompany->id);

    expect(Invoice::query()->pluck('id'))
        ->toContain($tenantInvoice->id)
        ->toContain($otherInvoice->id);

    expect(Payment::query()->pluck('id'))
        ->toContain($tenantPayment->id)
        ->toContain($otherPayment->id);

    expect(InvoiceItem::query()->pluck('id'))
        ->toContain($tenantItem->id)
        ->toContain($otherItem->id);
});

test('non admin users automatically get their customer id on create', function (): void {
    $customer = Customer::factory()->create();

    $tenantUser = User::factory()->create([
        'customer_id' => $customer->id,
        'is_admin' => false,
    ]);

    actingAs($tenantUser);

    $company = Company::create([
        'name' => 'Test Company',
    ]);

    expect($company->customer_id)->toBe($customer->id);

    $invoice = Invoice::create([
        'from_company_id' => $company->id,
        'to_company_id' => $company->id,
        'name' => 'Test Invoice',
        'date' => now(),
        'total_amount' => 100,
        'payed_amount' => 0,
        'payed' => false,
        'payment_due_date' => now()->addDay(),
    ]);

    expect($invoice->customer_id)->toBe($customer->id);

    $payment = Payment::create([
        'invoice_id' => $invoice->id,
        'from_company_id' => $company->id,
        'to_company_id' => $company->id,
        'reference' => 'TEST',
        'amount' => 50,
    ]);

    expect($payment->customer_id)->toBe($customer->id);
});
