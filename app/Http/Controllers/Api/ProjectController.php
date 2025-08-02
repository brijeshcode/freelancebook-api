<?php

namespace App\Http\Controllers\Api;

use App\Models\Project;
use App\Http\Controllers\Controller;
use App\Http\Requests\ProjectStoreRequest;
use App\Http\Requests\ProjectUpdateRequest;
use App\Http\Resources\ProjectResource;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProjectController extends Controller
{
    private int $userId ; 
    public function __construct()
    {
        $this->userId = Auth::id();
    }
    
    public function index(Request $request): JsonResponse
    {
        $query = Project::with(['client', 'freelancer'])
            ->where('freelancer_id', $this->userId);

        // Include trashed projects if requested
        if ($request->boolean('with_trashed')) {
            $query->withTrashed();
        } elseif ($request->boolean('only_trashed')) {
            $query->onlyTrashed();
        }

        $projects = $query->paginate(15);

        return ApiResponse::paginated(
            'Projects retrieved successfully',
            $projects,
            ProjectResource::class
        );
    }

    public function store(ProjectStoreRequest $request): JsonResponse
    {
        $project = Project::create([
            ...$request->validated(),
            'freelancer_id' => $this->userId,
        ]);

        $project->load(['client', 'freelancer']);

        return ApiResponse::store(
            'Project created successfully',
            new ProjectResource($project)
        );
    }

    public function show(Project $project): JsonResponse
    {
        // Check ownership manually since we don't have Policy yet
        if ($project->freelancer_id !== $this->userId) {
            return ApiResponse::forbidden('You are not authorized to view this project');
        }
        
        $project->load(['client', 'freelancer']);

        return ApiResponse::show(
            'Project retrieved successfully',
            new ProjectResource($project)
        );
    }

    public function update(ProjectUpdateRequest $request, Project $project): JsonResponse
    {
        // Check ownership manually since we don't have Policy yet
        if ($project->freelancer_id !== $this->userId) {
            return ApiResponse::forbidden('You are not authorized to update this project');
        }

        $project->update($request->validated());
        $project->load(['client', 'freelancer']);

        return ApiResponse::update(
            'Project updated successfully',
            new ProjectResource($project)
        );
    }

    public function destroy(Project $project): JsonResponse
    {
        // Check ownership manually since we don't have Policy yet
        if ($project->freelancer_id !== $this->userId) {
            return ApiResponse::forbidden('You are not authorized to delete this project');
        }

        $project->delete();

        return ApiResponse::delete('Project deleted successfully');
    }

    public function restore(int $id): JsonResponse
    {
        $project = Project::withTrashed()
            ->where('freelancer_id', $this->userId)
            ->findOrFail($id);

        $project->restore();
        $project->load(['client', 'freelancer']);

        return ApiResponse::update(
            'Project restored successfully',
            new ProjectResource($project)
        );
    }

    public function forceDelete(int $id): JsonResponse
    {
        $project = Project::withTrashed()
            ->where('freelancer_id', $this->userId)
            ->findOrFail($id);

        $project->forceDelete();

        return ApiResponse::delete('Project permanently deleted');
    }

    public function trashed(): JsonResponse
    {
        $projects = Project::onlyTrashed()
            ->with(['client', 'freelancer'])
            ->where('freelancer_id', $this->userId)
            ->paginate(15);

        return ApiResponse::paginated(
            'Trashed projects retrieved successfully',
            $projects,
            ProjectResource::class
        );
    }
}