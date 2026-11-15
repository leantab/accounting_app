<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\ProjectRequest;
use App\Http\Resources\V1\ProjectResource;
use App\Models\Project;
use App\Models\User;
use App\Support\Api\MobileAccess;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectController extends CrudController
{
    protected function canAccess(User $user): bool
    {
        return MobileAccess::managesProjects($user);
    }

    protected function query(User $user): Builder
    {
        return Project::query()->with(['customer', 'company', 'status']);
    }

    protected function resourceClass(): string
    {
        return ProjectResource::class;
    }

    protected function filterRules(User $user): array
    {
        return [
            'project_status_id' => ['nullable', 'integer'],
            'company_id' => ['nullable', 'integer'],
            ...($user->is_admin ? ['customer_id' => ['nullable', 'integer']] : []),
        ];
    }

    public function store(ProjectRequest $request): JsonResponse
    {
        $project = Project::query()->create($request->validated());

        return $this->fresh($request, $project)->response()->setStatusCode(201);
    }

    public function update(ProjectRequest $request, int $id): JsonResource
    {
        $project = $this->find($request, $id);
        $project->update($request->validated());

        return $this->fresh($request, $project);
    }
}
