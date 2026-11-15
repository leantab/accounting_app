<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\InviteUserRequest;
use App\Http\Requests\Api\V1\UserRequest;
use App\Http\Resources\V1\UserResource;
use App\Mail\ResendSender;
use App\Mail\UserInviteMail;
use App\Models\User;
use App\Models\UserRate;
use App\Support\Api\MobileAccess;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Throwable;

class UserController extends CrudController
{
    protected function canAccess(User $user): bool
    {
        return MobileAccess::managesCustomer($user);
    }

    /**
     * Users are not tenant-scoped by the model. Owners and admins of a customer reach its users,
     * except platform admins, whose accounts only other platform admins may manage.
     */
    protected function query(User $user): Builder
    {
        return User::query()
            ->with(['customer', 'role', 'userRates'])
            ->when(! $user->is_admin, fn (Builder $query) => $query
                ->where('customer_id', $user->customer_id)
                ->where('is_admin', false));
    }

    protected function resourceClass(): string
    {
        return UserResource::class;
    }

    protected function searchColumns(): array
    {
        return ['name', 'lastname', 'email', 'phone', 'address', 'tax_id'];
    }

    protected function filterRules(User $user): array
    {
        return [
            'user_role_id' => ['nullable', 'integer'],
            'is_active' => ['nullable', 'boolean'],
            ...($user->is_admin ? ['customer_id' => ['nullable', 'integer']] : []),
        ];
    }

    protected function order(Builder $query): void
    {
        $query->orderBy('name')->orderBy('lastname')->orderBy('id');
    }

    public function store(UserRequest $request): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        $user = DB::transaction(function () use ($request, $actor): User {
            $attributes = $request->userAttributes();

            if (! $actor->is_admin) {
                $attributes['customer_id'] = $actor->customer_id;
            }

            // The form request has already whitelisted these; phone and address are not in the model's fillable list.
            $user = (new User)->forceFill($attributes);
            $user->save();
            $this->saveIndividualRates($user, $request->individualRates());

            return $user;
        });

        return $this->fresh($request, $user)->response()->setStatusCode(201);
    }

    public function update(UserRequest $request, int $id): JsonResource
    {
        /** @var User $user */
        $user = $this->find($request, $id);

        DB::transaction(function () use ($request, $user): void {
            $user->forceFill($request->userAttributes())->save();
            $this->saveIndividualRates($user, $request->individualRates());
        });

        return $this->fresh($request, $user);
    }

    public function invite(InviteUserRequest $request): JsonResponse
    {
        $email = mb_strtolower($request->string('email')->toString());

        try {
            ResendSender::send(new UserInviteMail($email, (int) $request->customerId()), $email);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'No se pudo enviar la invitación. Intente nuevamente en unos minutos.',
            ], 502);
        }

        return response()->json(['message' => 'Invitación enviada']);
    }

    protected function guardDelete(Request $request, Model $record): void
    {
        abort_if($record->is($request->user()), 422, 'No podés eliminar tu propio usuario.');
    }

    /**
     * A null rate falls back to the default rate, so it removes any custom rate.
     *
     * @param  array<int, float|null>|null  $rates
     */
    private function saveIndividualRates(User $user, ?array $rates): void
    {
        foreach ($rates ?? [] as $typeId => $rate) {
            $query = UserRate::query()
                ->where('user_id', $user->id)
                ->where('time_tracker_item_type_id', $typeId);

            if ($rate === null) {
                $query->delete();

                continue;
            }

            $query->exists()
                ? $query->update(['rate' => $rate])
                : UserRate::query()->create([
                    'customer_id' => $user->customer_id,
                    'user_id' => $user->id,
                    'time_tracker_item_type_id' => $typeId,
                    'rate' => $rate,
                ]);
        }
    }
}
