<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use App\Traits\HasPagination;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    use HasPagination;
    /**
     * Display a listing of active users
     */
    public function index(Request $request): JsonResponse
    {
        $query = User::query(); // Only non-deleted users
        $users = $this->applyPagination($query, $request);
        
        return ApiResponse::paginated('Users retrieved successfully', $users);
    }

    /**
     * Display a listing including soft deleted users
     */
    public function indexWithTrashed(Request $request): JsonResponse
    {
        $query = User::withTrashed();
        $users = $this->applyPagination($query, $request);
        
        return ApiResponse::paginated('All users (including deleted) retrieved successfully', $users);
    }

    /**
     * Display only soft deleted users
     */
    public function onlyTrashed(Request $request): JsonResponse
    {
        $query = User::onlyTrashed();
        $users = $this->applyPagination($query, $request);
        
        return ApiResponse::paginated('Deleted users retrieved successfully', $users);
    }

    /**
     * Display the specified user
     */
    public function show(User $user): JsonResponse
    {
        return ApiResponse::show('User retrieved successfully', $user);
    }

    /**
     * Store a newly created user
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8|confirmed',
                'role' => 'required|in:' . implode(',', User::getAvailableRoles()),
                'phone' => 'nullable|string|max:20',
                'address' => 'nullable|string|max:500',
            ]);

            $validated['password'] = Hash::make($validated['password']);
            
            $user = User::create($validated);
            
            return ApiResponse::store('User created successfully', $user);

        } catch (ValidationException $e) {
            return ApiResponse::failValidation($e->errors(), 'Validation failed');
        } catch (\Exception $e) {
            return ApiResponse::serverError('Failed to create user');
        }
    }

    /**
     * Update the specified user
     */
    public function update(Request $request, User $user): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'email' => [
                    'sometimes',
                    'required',
                    'string',
                    'email',
                    'max:255',
                    Rule::unique('users')->ignore($user->id)
                ],
                'password' => 'sometimes|string|min:8|confirmed',
                'role' => 'sometimes|required|in:' . implode(',', User::getAvailableRoles()),
                'phone' => 'nullable|string|max:20',
                'address' => 'nullable|string|max:500',
            ]);

            // Hash password if provided
            if (isset($validated['password'])) {
                $validated['password'] = Hash::make($validated['password']);
            }
            
            $user->update($validated);
            
            return ApiResponse::update('User updated successfully', $user->fresh());

        } catch (ValidationException $e) {
            return ApiResponse::failValidation($e->errors(), 'Validation failed');
        } catch (\Exception $e) {
            return ApiResponse::serverError('Failed to update user');
        }
    }

    /**
     * Soft delete the specified user
     */
    public function destroy(User $user): JsonResponse
    {
        try {
            $user->delete(); // Soft delete
            return ApiResponse::delete('User deleted successfully');
        } catch (\Exception $e) {
            return ApiResponse::serverError('Failed to delete user');
        }
    }

    /**
     * Restore a soft deleted user
     */
    public function restore(int $id): JsonResponse
    {
        try {
            $user = User::withTrashed()->find($id);
            
            if (!$user) {
                return ApiResponse::notFound('User not found');
            }

            if (!$user->trashed()) {
                return ApiResponse::custom('User is not deleted', 400);
            }

            $user->restore();
            return ApiResponse::update('User restored successfully', $user);
        } catch (\Exception $e) {
            return ApiResponse::serverError('Failed to restore user');
        }
    }

    /**
     * Permanently delete a user (force delete)
     */
    public function forceDestroy(int $id): JsonResponse
    {
        try {
            $user = User::withTrashed()->find($id);
            
            if (!$user) {
                return ApiResponse::notFound('User not found');
            }

            $user->forceDelete(); // Permanent delete
            return ApiResponse::delete('User permanently deleted');
        } catch (\Exception $e) {
            return ApiResponse::serverError('Failed to permanently delete user');
        }
    }

    /**
     * Change user role (admin only)
     */
    public function changeRole(Request $request, User $user): JsonResponse
    {
        try {
            $request->validate([
                'role' => 'required|in:' . implode(',', User::getAvailableRoles())
            ]);

            $user->update(['role' => $request->role]);
            return ApiResponse::update('User role updated successfully', $user);

        } catch (ValidationException $e) {
            return ApiResponse::failValidation($e->errors(), 'Validation failed');
        } catch (\Exception $e) {
            return ApiResponse::serverError('Failed to update user role');
        }
    }
}