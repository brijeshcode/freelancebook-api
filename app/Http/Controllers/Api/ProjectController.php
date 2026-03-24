<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProjectStoreRequest;
use App\Http\Requests\ProjectUpdateRequest;
use App\Http\Resources\ProjectResource;
use App\Http\Responses\ApiResponse;
use App\Models\Project;
use App\Services\CurrencyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProjectController extends Controller
{
    private int $userId;

    public function __construct(private CurrencyService $currencyService)
    {
        $this->userId = Auth::id();
    }

    public function index(Request $request): JsonResponse
    {
        $query = Project::with(['client', 'freelancer', 'currency'])
            ->where('freelancer_id', $this->userId);

        if ($request->boolean('with_trashed')) {
            $query->withTrashed();
        } elseif ($request->boolean('only_trashed')) {
            $query->onlyTrashed();
        }

        return ApiResponse::paginated(
            'Projects retrieved successfully',
            $query->paginate(15),
            ProjectResource::class
        );
    }

    public function store(ProjectStoreRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['freelancer_id'] = $this->userId;

        // Resolve exchange rate and compute budget in base currency
        if (!empty($data['budget']) && !empty($data['currency_id'])) {
            $baseCurrency = $this->currencyService->getBaseCurrency($this->userId);
            $snapshot     = $this->currencyService->snapshot($data['currency_id'], $baseCurrency);

            $data['exchange_rate']    = $request->exchange_rate    ?? $snapshot['exchange_rate'];
            $data['calculation_type'] = $request->calculation_type ?? $snapshot['calculation_type'];
            $data['budget_base_currency'] = $this->currencyService->applyRate(
                (float) $data['budget'],
                (float) $data['exchange_rate'],
                $data['calculation_type']
            );
        }

        $project = Project::create($data);
        $project->load(['client', 'freelancer', 'currency']);

        return ApiResponse::store('Project created successfully', new ProjectResource($project));
    }

    public function show(Project $project): JsonResponse
    {
        if ($project->freelancer_id !== $this->userId) {
            return ApiResponse::forbidden('You are not authorized to view this project');
        }

        $project->load(['client', 'freelancer', 'currency']);
        return ApiResponse::show('Project retrieved successfully', new ProjectResource($project));
    }

    public function update(ProjectUpdateRequest $request, Project $project): JsonResponse
    {
        if ($project->freelancer_id !== $this->userId) {
            return ApiResponse::forbidden('You are not authorized to update this project');
        }

        $data = $request->validated();

        // Recalculate budget_base_currency if budget, currency, or rate changes
        if ($request->hasAny(['budget', 'currency_id', 'exchange_rate', 'calculation_type'])) {
            $budget      = (float) ($data['budget']      ?? $project->budget);
            $currencyId  = $data['currency_id']          ?? $project->currency_id;
            $exchangeRate    = $data['exchange_rate']    ?? null;
            $calculationType = $data['calculation_type'] ?? null;

            // If currency changed or no explicit rate given, refresh snapshot
            if (!$exchangeRate || !$calculationType) {
                $baseCurrency = $this->currencyService->getBaseCurrency($this->userId);
                $snapshot     = $this->currencyService->snapshot($currencyId, $baseCurrency);
                $exchangeRate    = $exchangeRate    ?? $snapshot['exchange_rate'];
                $calculationType = $calculationType ?? $snapshot['calculation_type'];
            }

            $data['exchange_rate']        = $exchangeRate;
            $data['calculation_type']     = $calculationType;
            $data['budget_base_currency'] = $this->currencyService->applyRate($budget, (float) $exchangeRate, $calculationType);
        }

        $project->update($data);
        $project->load(['client', 'freelancer', 'currency']);

        return ApiResponse::update('Project updated successfully', new ProjectResource($project));
    }

    public function destroy(Project $project): JsonResponse
    {
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
        $project->load(['client', 'freelancer', 'currency']);

        return ApiResponse::update('Project restored successfully', new ProjectResource($project));
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
            ->with(['client', 'freelancer', 'currency'])
            ->where('freelancer_id', $this->userId)
            ->paginate(15);

        return ApiResponse::paginated('Trashed projects retrieved successfully', $projects, ProjectResource::class);
    }
}
