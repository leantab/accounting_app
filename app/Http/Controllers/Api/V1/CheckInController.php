<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\CheckInTypeEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreCheckInRequest;
use App\Http\Resources\V1\CheckInResource;
use App\Models\CheckIn;
use App\Models\User;
use App\Support\Api\MobileAccess;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class CheckInController extends Controller
{
    /**
     * Attendance of the whole customer, for whoever reviews it.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = $request->user();

        abort_unless(MobileAccess::reviewsCheckIns($user), 403);

        $filters = $request->validate([
            'user_id' => ['nullable', 'integer'],
            'project_id' => ['nullable', 'integer'],
            'type' => ['nullable', Rule::enum(CheckInTypeEnum::class)],
            'search' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = CheckIn::query()->with(['customer', 'user', 'project']);

        foreach (['user_id', 'project_id', 'type'] as $column) {
            if (isset($filters[$column])) {
                $query->where($column, $filters[$column]);
            }
        }

        if (filled($filters['search'] ?? null)) {
            $search = '%'.$filters['search'].'%';

            $query->whereHas('user', fn (Builder $users) => $users
                ->where('name', 'like', $search)
                ->orWhere('lastname', 'like', $search));
        }

        return CheckInResource::collection(
            $query->orderByDesc('recorded_at')->orderByDesc('id')->paginate($filters['per_page'] ?? 20),
        );
    }

    /**
     * The last records of the signed-in worker and what the next one will be.
     */
    public function mine(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        abort_unless(MobileAccess::recordsCheckIns($user), 403);

        return response()->json($this->ownState($user));
    }

    public function store(StoreCheckInRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $data = $request->validated();
        $type = $this->nextType($user);

        CheckIn::query()->create([
            'customer_id' => $user->customer_id,
            'user_id' => $user->id,
            'project_id' => $data['project_id'] ?? null,
            'type' => $type,
            'recorded_at' => now(),
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
            'accuracy_m' => isset($data['accuracy']) ? (int) round((float) $data['accuracy']) : null,
            'notes' => $data['notes'] ?? null,
        ]);

        return response()->json([
            'message' => $type === CheckInTypeEnum::In ? 'Entrada registrada' : 'Salida registrada',
            ...$this->ownState($user),
        ], 201);
    }

    /**
     * @return array{data: AnonymousResourceCollection, next_type: array{value: int, label: string}}
     */
    private function ownState(User $user): array
    {
        $next = $this->nextType($user);

        return [
            'data' => CheckInResource::collection($this->own($user)->with('project')->limit(10)->get()),
            'next_type' => ['value' => $next->value, 'label' => $next->label()],
        ];
    }

    private function nextType(User $user): CheckInTypeEnum
    {
        $last = $this->own($user)->first();

        return $last === null ? CheckInTypeEnum::In : $last->type->next();
    }

    /**
     * @return Builder<CheckIn>
     */
    private function own(User $user): Builder
    {
        return CheckIn::query()->where('user_id', $user->id)->latest('recorded_at')->latest('id');
    }
}
