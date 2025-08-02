<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ClientStoreRequest;
use App\Http\Requests\ClientUpdateRequest;
use App\Http\Resources\ClientResource;
use App\Http\Responses\ApiResponse;
use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClientController extends Controller
{
    private int $userId ; 
    public function __construct()
    {
        $this->userId = Auth::id();
    }
    
    public function index(Request $request): JsonResponse
    {
        
        $clients = Client::forUser($this->userId)
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->search, fn($q) => $q->where('name', 'like', "%{$request->search}%"))
            ->latest()
            ->paginate($request->per_page ?? 15);

        return ApiResponse::paginated('Clients retrieved successfully', $clients, ClientResource::class);
    }

    public function store(ClientStoreRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['user_id'] = $this->userId;
        $data['client_code'] = Client::generateClientCode();

        $client = Client::create($data);

        return ApiResponse::store('Client created successfully', new ClientResource($client));
    }

    public function show(Client $client): JsonResponse
    {
        // Check if client belongs to authenticated user
        if ($client->user_id !== $this->userId) {
            return ApiResponse::forbidden('Access denied');
        }

        return ApiResponse::show('Client retrieved successfully', new ClientResource($client));
    }

    public function update(ClientUpdateRequest $request, Client $client): JsonResponse
    {
        if ($client->user_id !== $this->userId) {
            return ApiResponse::forbidden('Access denied');
        }

        $client->update($request->validated());

        return ApiResponse::update('Client updated successfully', new ClientResource($client));
    }

    public function destroy(Client $client): JsonResponse
    {
        if ($client->user_id !== $this->userId) {
            return ApiResponse::forbidden('Access denied');
        }

        $client->delete();

        return ApiResponse::delete('Client deleted successfully');
    }

    public function indexWithTrashed(Request $request): JsonResponse
    {
        $clients = Client::withTrashed()
            ->forUser($this->userId)
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->search, fn($q) => $q->where('name', 'like', "%{$request->search}%"))
            ->latest()
            ->paginate($request->per_page ?? 15);

        return ApiResponse::paginated('Clients with trashed retrieved successfully', $clients, ClientResource::class);
    }

    public function onlyTrashed(Request $request): JsonResponse
    {
        $clients = Client::onlyTrashed()
            ->forUser($this->userId)
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->search, fn($q) => $q->where('name', 'like', "%{$request->search}%"))
            ->latest()
            ->paginate($request->per_page ?? 15);

        return ApiResponse::paginated('Trashed clients retrieved successfully', $clients, ClientResource::class);
    }

    public function restore(int $id): JsonResponse
    {
        $client = Client::onlyTrashed()->forUser($this->userId)->findOrFail($id);

        $client->restore();

        return ApiResponse::update('Client restored successfully', new ClientResource($client));
    }

    public function forceDestroy(int $id): JsonResponse
    {
        $client = Client::onlyTrashed()->forUser($this->userId)->findOrFail($id);

        $client->forceDelete();

        return ApiResponse::delete('Client permanently deleted successfully');
    }

    public function changeStatus(Request $request, Client $client): JsonResponse
    {
        if ($client->user_id !== $this->userId) {
            return ApiResponse::forbidden('Access denied');
        }

        $request->validate([
            'status' => 'required|in:active,inactive,archived'
        ]);

        $client->update(['status' => $request->status]);

        return ApiResponse::update('Client status updated successfully', new ClientResource($client));
    }
}