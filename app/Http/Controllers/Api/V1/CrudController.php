<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * List, show and delete shared by the simple resources of the API. Each controller adds its own store and update.
 */
abstract class CrudController extends Controller
{
    abstract protected function canAccess(User $user): bool;

    /**
     * Records the user may see, with the relations the resource needs.
     *
     * @return Builder<*>
     */
    abstract protected function query(User $user): Builder;

    /**
     * @return class-string<JsonResource>
     */
    abstract protected function resourceClass(): string;

    /**
     * @return list<string>
     */
    protected function searchColumns(): array
    {
        return ['name'];
    }

    /**
     * Validation rules of the extra query parameters; each one is applied as an equality filter.
     *
     * @return array<string, mixed>
     */
    protected function filterRules(User $user): array
    {
        return [];
    }

    /**
     * @param  Builder<*>  $query
     */
    protected function order(Builder $query): void
    {
        $query->orderBy('name')->orderBy('id');
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $this->authorizedUser($request);

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            ...$this->filterRules($user),
        ]);

        $query = $this->query($user);
        $table = $query->getModel()->getTable();

        foreach (array_keys($this->filterRules($user)) as $column) {
            if (isset($filters[$column])) {
                $query->where("{$table}.{$column}", $filters[$column]);
            }
        }

        if (filled($filters['search'] ?? null)) {
            $search = '%'.$filters['search'].'%';

            $query->where(function (Builder $query) use ($search, $table): void {
                foreach ($this->searchColumns() as $column) {
                    $query->orWhere("{$table}.{$column}", 'like', $search);
                }
            });
        }

        $this->order($query);

        return $this->resourceClass()::collection($query->paginate($filters['per_page'] ?? 20));
    }

    public function show(Request $request, int $id): JsonResource
    {
        return $this->toResource($this->find($request, $id));
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $record = $this->find($request, $id);

        $this->guardDelete($request, $record);

        try {
            $record->delete();
        } catch (QueryException) {
            return response()->json([
                'message' => __('api.cannot_delete_with_records'),
            ], 409);
        }

        return response()->json(['message' => 'Eliminado.']);
    }

    /**
     * Hook for rules such as "you cannot delete yourself".
     */
    protected function guardDelete(Request $request, Model $record): void {}

    protected function authorizedUser(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();

        abort_unless($this->canAccess($user), 403);

        return $user;
    }

    protected function find(Request $request, int $id): Model
    {
        return $this->query($this->authorizedUser($request))->findOrFail($id);
    }

    protected function toResource(Model $record): JsonResource
    {
        $class = $this->resourceClass();

        return new $class($record);
    }

    /**
     * Reloads a record through the visible query, so the response carries the same relations as show.
     */
    protected function fresh(Request $request, Model $record): JsonResource
    {
        return $this->toResource($this->find($request, (int) $record->getKey()));
    }
}
