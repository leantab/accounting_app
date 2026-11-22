<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\TimeTracker\ApproveTimeTrackerAction;
use App\Actions\TimeTracker\AutoCompleteTimeTrackerAction;
use App\Actions\TimeTracker\SaveTimeTrackerAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\TimeTrackerRequest;
use App\Http\Resources\V1\TimeTrackerResource;
use App\Models\TimeTracker;
use App\Models\TimeTrackerItemType;
use App\Models\User;
use App\Support\Api\MobileAccess;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TimeTrackerController extends Controller
{
    private const DETAIL_RELATIONS = [
        'customer',
        'user',
        'project',
        'invoice',
        'payment',
        'approvedBy',
        'items.timeTrackerItemType',
    ];

    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $this->authorizedUser($request);

        $filters = $request->validate([
            'status' => ['nullable', 'in:pending_approval,approved_unbilled,pending_payment,paid'],
            'customer_id' => ['nullable', 'integer'],
            'user_id' => ['nullable', 'integer'],
            'project_id' => ['nullable', 'integer'],
            'approved' => ['nullable', 'boolean'],
            'billed' => ['nullable', 'boolean'],
            'paid' => ['nullable', 'boolean'],
            'search' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = $this->visibleTo($user)->with(['customer', 'user', 'project']);

        match ($filters['status'] ?? null) {
            'pending_approval' => $query->where('approved', false),
            'approved_unbilled' => $query->where('approved', true)->where('billed', false),
            'pending_payment' => $query->where('approved', true)->where('billed', true)->where('paid', false),
            'paid' => $query->where('paid', true),
            default => null,
        };

        foreach (['user_id', 'project_id', 'approved', 'billed', 'paid'] as $column) {
            if (isset($filters[$column])) {
                $query->where($column, $filters[$column]);
            }
        }

        if ($user->is_admin && isset($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }

        if (filled($filters['search'] ?? null)) {
            $query->where('name', 'like', '%'.$filters['search'].'%');
        }

        return TimeTrackerResource::collection(
            $query->orderByDesc('date_start')->orderByDesc('id')->paginate($filters['per_page'] ?? 20),
        );
    }

    public function show(Request $request, int $timeTracker): TimeTrackerResource
    {
        return new TimeTrackerResource($this->find($request, $timeTracker));
    }

    public function store(TimeTrackerRequest $request): JsonResponse
    {
        $timeTracker = SaveTimeTrackerAction::execute(null, $request->trackerAttributes(), $request->items() ?? []);

        return (new TimeTrackerResource($timeTracker->load(self::DETAIL_RELATIONS)))
            ->response()
            ->setStatusCode(201);
    }

    public function update(TimeTrackerRequest $request, int $timeTracker): TimeTrackerResource
    {
        $record = $this->find($request, $timeTracker);

        SaveTimeTrackerAction::execute($record, $request->trackerAttributes(), $request->items());

        return new TimeTrackerResource($record->refresh()->load(self::DETAIL_RELATIONS));
    }

    public function destroy(Request $request, int $timeTracker): JsonResponse
    {
        $record = $this->find($request, $timeTracker);

        $record->items()->delete();
        $record->delete();

        return response()->json(['message' => __('api.time_tracker_deleted')]);
    }

    public function approve(Request $request, int $timeTracker): JsonResponse
    {
        $user = $this->authorizedUser($request);

        abort_unless(MobileAccess::approvesTimeTrackers($user), 403, __('api.no_permission_approve'));

        $record = $this->find($request, $timeTracker);

        if ($record->approved) {
            return response()->json(['message' => __('api.already_approved')], 422);
        }

        ApproveTimeTrackerAction::execute($record, $user);

        return response()->json([
            'message' => __('api.time_tracker_approved'),
            'data' => new TimeTrackerResource($record->refresh()->load(self::DETAIL_RELATIONS)),
        ]);
    }

    public function recalculate(Request $request, int $timeTracker): JsonResponse
    {
        $user = $this->authorizedUser($request);

        abort_unless(MobileAccess::approvesTimeTrackers($user), 403, __('api.no_permission_recalculate'));

        $record = $this->find($request, $timeTracker);
        $result = AutoCompleteTimeTrackerAction::recalculate($record);

        return response()->json([
            'message' => $result['missing_rates'] === []
                ? __('api.recalculated')
                : __('api.recalculated_missing_rates', ['types' => TimeTrackerItemType::translateMany($result['missing_rates'])]),
            'missing_rates' => $result['missing_rates'],
            'data' => new TimeTrackerResource($record->refresh()->load(self::DETAIL_RELATIONS)),
        ]);
    }

    public function autoComplete(Request $request): JsonResponse
    {
        $user = $this->authorizedUser($request);

        abort_unless(MobileAccess::approvesTimeTrackers($user), 403, __('api.no_permission_auto_complete'));

        $result = AutoCompleteTimeTrackerAction::execute();

        $message = match (true) {
            $result['trackers'] === 0 => __('api.nothing_to_calculate'),
            $result['missing_rates'] !== [] => __('api.calculated_missing_rates', ['types' => TimeTrackerItemType::translateMany($result['missing_rates'])]),
            default => __('api.calculated'),
        };

        return response()->json([
            'message' => $message,
            'trackers' => $result['trackers'],
            'missing_rates' => $result['missing_rates'],
        ]);
    }

    private function authorizedUser(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();

        abort_unless(MobileAccess::usesTimeTrackers($user), 403);

        return $user;
    }

    /**
     * Employees only reach their own trackers; the tenant scope covers everyone else.
     *
     * @return Builder<TimeTracker>
     */
    private function visibleTo(User $user): Builder
    {
        return TimeTracker::query()
            ->when(MobileAccess::isEmployee($user), fn (Builder $query) => $query->where('user_id', $user->id));
    }

    private function find(Request $request, int $id): TimeTracker
    {
        return $this->visibleTo($this->authorizedUser($request))
            ->with(self::DETAIL_RELATIONS)
            ->findOrFail($id);
    }
}
