<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ServiceStoreRequest;
use App\Http\Requests\ServiceUpdateRequest;
use App\Http\Resources\ServiceResource;
use App\Http\Resources\ServiceListResource;
use App\Http\Responses\ApiResponse;
use App\Models\Service;
use App\Services\CurrencyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ServiceController extends Controller
{
    public function __construct(private CurrencyService $currencyService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $services = Service::with(['client', 'project', 'creator', 'currency'])
            ->when($request->client_id, fn($q) => $q->where('client_id', $request->client_id))
            ->when($request->project_id, fn($q) => $q->where('project_id', $request->project_id))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->frequency, fn($q) => $q->where('frequency', $request->frequency))
            ->when($request->currency_id, fn($q) => $q->where('currency_id', $request->currency_id))
            ->when($request->is_active !== null, fn($q) => $q->where('is_active', $request->boolean('is_active')))
            ->when($request->has_tax !== null, fn($q) => $q->where('has_tax', $request->boolean('has_tax')))
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return ApiResponse::paginated('Services retrieved successfully', $services, ServiceResource::class);
    }

    public function store(ServiceStoreRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['created_by'] = Auth::id();

        // Resolve exchange rate snapshot and compute amount in base currency
        $baseCurrency = $this->currencyService->getBaseCurrency(Auth::id());
        $snapshot     = $this->currencyService->snapshot($data['currency_id'], $baseCurrency);

        $data['exchange_rate']      = $request->exchange_rate    ?? $snapshot['exchange_rate'];
        $data['calculation_type']   = $request->calculation_type ?? $snapshot['calculation_type'];
        $data['amount_base_currency'] = $this->currencyService->applyRate(
            (float) $data['amount'],
            (float) $data['exchange_rate'],
            $data['calculation_type']
        );

        $service = Service::create($data);
        $service->load(['client', 'project', 'creator', 'currency']);

        return ApiResponse::store('Service created successfully', new ServiceResource($service));
    }

    public function show(Service $service): JsonResponse
    {
        $service->load(['client', 'project', 'creator', 'currency']);
        return ApiResponse::show('Service retrieved successfully', new ServiceResource($service));
    }

    public function update(ServiceUpdateRequest $request, Service $service): JsonResponse
    {
        $data = $request->validated();

        // Recalculate amount_base_currency if amount, currency, or rate changes
        if ($request->hasAny(['amount', 'currency_id', 'exchange_rate', 'calculation_type'])) {
            $amount      = (float) ($data['amount']      ?? $service->amount);
            $currencyId  = $data['currency_id']          ?? $service->currency_id;
            $exchangeRate    = $data['exchange_rate']    ?? null;
            $calculationType = $data['calculation_type'] ?? null;

            // Refresh snapshot if no explicit rate given
            if (!$exchangeRate || !$calculationType) {
                $baseCurrency = $this->currencyService->getBaseCurrency(Auth::id());
                $snapshot     = $this->currencyService->snapshot($currencyId, $baseCurrency);
                $exchangeRate    = $exchangeRate    ?? $snapshot['exchange_rate'];
                $calculationType = $calculationType ?? $snapshot['calculation_type'];
            }

            $data['exchange_rate']        = $exchangeRate;
            $data['calculation_type']     = $calculationType;
            $data['amount_base_currency'] = $this->currencyService->applyRate($amount, (float) $exchangeRate, $calculationType);
        }

        $service->update($data);
        $service->load(['client', 'project', 'creator', 'currency']);

        return ApiResponse::update('Service updated successfully', new ServiceResource($service));
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

        return ApiResponse::update('Service restored successfully', new ServiceResource($service));
    }

    public function forceDelete(int $id): JsonResponse
    {
        $service = Service::withTrashed()->findOrFail($id);
        $service->forceDelete();

        return ApiResponse::delete('Service permanently deleted');
    }

    public function readyForBilling(Request $request): JsonResponse
    {
        $services = Service::with(['client', 'project', 'currency'])
            ->readyForBilling()
            ->when($request->client_id, fn($q) => $q->where('client_id', $request->client_id))
            ->orderBy('next_billing_date')
            ->paginate($request->per_page ?? 15);

        return ApiResponse::paginated('Services ready for billing retrieved successfully', $services, ServiceResource::class);
    }

    public function recurring(Request $request): JsonResponse
    {
        $services = Service::with(['client', 'project', 'currency'])
            ->recurring()
            ->active()
            ->when($request->client_id, fn($q) => $q->where('client_id', $request->client_id))
            ->orderBy('next_billing_date')
            ->paginate($request->per_page ?? 15);

        return ApiResponse::paginated('Recurring services retrieved successfully', $services, ServiceResource::class);
    }

    public function toggleStatus(Service $service): JsonResponse
    {
        $service->update(['is_active' => !$service->is_active]);
        return ApiResponse::update('Service status toggled successfully', new ServiceResource($service));
    }
}
