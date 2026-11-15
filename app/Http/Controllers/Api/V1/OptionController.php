<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Project;
use App\Models\User;
use App\Support\Api\MobileAccess;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Short id / name lists that feed the select fields of the app.
 */
class OptionController extends Controller
{
    private const LIMIT = 50;

    public function __invoke(Request $request, string $resource): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'customer_id' => ['nullable', 'integer'],
        ]);

        $search = filled($filters['search'] ?? null) ? '%'.$filters['search'].'%' : null;
        $customerId = $user->is_admin ? ($filters['customer_id'] ?? null) : $user->customer_id;
        $notEmployee = MobileAccess::canUseApp($user) && ! MobileAccess::isEmployee($user);

        $options = match ($resource) {
            'projects' => $this->options(
                MobileAccess::canUseApp($user),
                Project::query(),
                ['name'],
                $search,
                $customerId,
            ),
            'invoices' => $this->options($notEmployee, Invoice::query(), ['name', 'invoice_number'], $search, $customerId),
            'payments' => $this->options($notEmployee, Payment::query(), ['reference'], $search, $customerId, 'reference'),
            'companies' => $this->options(
                MobileAccess::managesProjects($user),
                Company::query(),
                ['name'],
                $search,
                $customerId,
            ),
            'users' => $this->users($notEmployee, $search, $customerId),
            'customers' => $this->options(MobileAccess::managesCustomers($user), Customer::query(), ['name'], $search, null),
            default => abort(404),
        };

        return response()->json(['data' => $options]);
    }

    /**
     * @param  Builder<*>  $query
     * @param  list<string>  $searchColumns
     * @return list<array{id: int, name: string}>
     */
    private function options(
        bool $allowed,
        Builder $query,
        array $searchColumns,
        ?string $search,
        ?int $customerId,
        string $labelColumn = 'name',
    ): array {
        abort_unless($allowed, 403);

        return $query
            ->when($customerId !== null, fn (Builder $query) => $query->where('customer_id', $customerId))
            ->when($search !== null, fn (Builder $query) => $query->where(function (Builder $query) use ($searchColumns, $search): void {
                foreach ($searchColumns as $column) {
                    $query->orWhere($column, 'like', $search);
                }
            }))
            ->orderBy($labelColumn)
            ->limit(self::LIMIT)
            ->get(['id', $labelColumn])
            ->map(fn ($record): array => ['id' => (int) $record->id, 'name' => (string) $record->{$labelColumn}])
            ->all();
    }

    /**
     * Users are not tenant-scoped by the model, so the customer filter is always applied here.
     *
     * @return list<array{id: int, name: string}>
     */
    private function users(bool $allowed, ?string $search, ?int $customerId): array
    {
        abort_unless($allowed, 403);

        return User::query()
            ->when($customerId !== null, fn (Builder $query) => $query->where('customer_id', $customerId))
            ->when($search !== null, fn (Builder $query) => $query->where(fn (Builder $query) => $query
                ->where('name', 'like', $search)
                ->orWhere('lastname', 'like', $search)))
            ->orderBy('name')
            ->orderBy('lastname')
            ->limit(self::LIMIT)
            ->get()
            ->map(fn (User $record): array => ['id' => $record->id, 'name' => $record->fullName])
            ->all();
    }
}
