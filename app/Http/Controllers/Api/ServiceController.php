<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ServiceStoreRequest;
use App\Http\Requests\ServiceUpdateRequest;
use App\Http\Resources\ServiceResource;
use App\Http\Resources\ServiceListResource;
use App\Http\Responses\ApiResponse;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ServiceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $services = Service::with(['client', 'project', 'creator'])
            ->when($request->client_id, fn($q) => $q->where('client_id', $request->client_id))
            ->when($request->project_id, fn($q) => $q->where('project_id', $request->project_id))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->frequency, fn($q) => $q->where('frequency', $request->frequency))
            ->when($request->currency, fn($q) => $q->where('currency', $request->currency))
            ->when($request->is_active !== null, fn($q) => $q->where('is_active', $request->boolean('is_active')))
            ->when($request->has_tax !== null, fn($q) => $q->where('has_tax', $request->boolean('has_tax')))
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return ApiResponse::paginated(
            'Services retrieved successfully',
            $services,
            ServiceResource::class
        );
    }

    public function store(ServiceStoreRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['created_by'] = Auth::id();

        $service = Service::create($data);
        $service->load(['client', 'project', 'creator']);

        return ApiResponse::store(
            'Service created successfully',
            new ServiceResource($service)
        );
    }

    public function show(Service $service): JsonResponse
    {
        $service->load(['client', 'project', 'creator']);

        return ApiResponse::show(
            'Service retrieved successfully',
            new ServiceResource($service)
        );
    }

    public function update(ServiceUpdateRequest $request, Service $service): JsonResponse
    {
        $service->update($request->validated());
        $service->load(['client', 'project', 'creator']);

        return ApiResponse::update(
            'Service updated successfully',
            new ServiceResource($service)
        );
    }

    public function destroy(Service $service): JsonResponse
    {
        $service->delete();

        return ApiResponse::delete('Service deleted successfully');
    }

    public function restore(int $id): JsonResponse
    {
        $service = Service::withTrashed()->findOrFail($id);
        $service->restore();

        return ApiResponse::update(
            'Service restored successfully',
            new ServiceResource($service)
        );
    }

    public function forceDelete(int $id): JsonResponse
    {
        $service = Service::withTrashed()->findOrFail($id);
        $service->forceDelete();

        return ApiResponse::delete('Service permanently deleted');
    }

    public function readyForBilling(Request $request): JsonResponse
    {
        $services = Service::with(['client', 'project'])
            ->readyForBilling()
            ->when($request->client_id, fn($q) => $q->where('client_id', $request->client_id))
            ->orderBy('next_billing_date')
            ->paginate($request->per_page ?? 15);

        return ApiResponse::paginated(
            'Services ready for billing retrieved successfully',
            $services,
            ServiceResource::class
        );
    }

    public function recurring(Request $request): JsonResponse
    {
        $services = Service::with(['client', 'project'])
            ->recurring()
            ->active()
            ->when($request->client_id, fn($q) => $q->where('client_id', $request->client_id))
            ->orderBy('next_billing_date')
            ->paginate($request->per_page ?? 15);

        return ApiResponse::paginated(
            'Recurring services retrieved successfully',
            $services,
            ServiceResource::class
        );
    }

    public function toggleStatus(Service $service): JsonResponse
    {
        $service->update(['is_active' => !$service->is_active]);

        return ApiResponse::update(
            'Service status toggled successfully',
            new ServiceResource($service)
        );
    }
}