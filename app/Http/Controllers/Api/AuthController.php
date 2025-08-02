<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    /**
     * Register new freelancer user
     */
    public function register(Request $request): JsonResponse
    {
        // Rate limiting for registration attempts
        $key = 'register.' . $request->ip();
        
        if (RateLimiter::tooManyAttempts($key, 3)) {
            $seconds = RateLimiter::availableIn($key);
            return ApiResponse::throw(
                [], 
                'Too many registration attempts. Please try again in ' . $seconds . ' seconds.',
                429
            );
        }

        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => ['required', 'string', 'confirmed', Password::defaults()],
                'device_name' => 'sometimes|string|max:255'
            ]);
        } catch (ValidationException $e) {
            RateLimiter::hit($key);
            return ApiResponse::failValidation($e->errors(), 'Registration validation failed');
        }

        try {
            // Create new user with freelancer role by default
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => 'freelancer', // Default role for new registrations
                'email_verified_at' => null, // Will be verified later via email
            ]);

            // Clear rate limiter on successful registration
            RateLimiter::clear($key);

            // Generate token for immediate login after registration
            $deviceName = $request->device_name ?? $request->userAgent();
            $token = $user->createToken($deviceName);

            return ApiResponse::store('Registration successful', [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'email_verified_at' => $user->email_verified_at,
                    'created_at' => $user->created_at,
                ],
                'token' => $token->plainTextToken,
                'token_type' => 'Bearer'
            ]);

        } catch (\Exception $e) {
            RateLimiter::hit($key);
            return ApiResponse::serverError('Registration failed. Please try again.');
        }
    }

    /**
     * Login user and create token
     */
    public function login(Request $request): JsonResponse
    {
        // Rate limiting for login attempts
        $key = 'login.' . $request->ip();
        
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            return ApiResponse::throw(
                [], 
                'Too many login attempts. Please try again in ' . $seconds . ' seconds.',
                429
            );
        }

        try {
            $validated = $request->validate([
                'email' => 'required|email',
                'password' => 'required',
                'device_name' => 'sometimes|string|max:255'
            ]);
        } catch (ValidationException $e) {
            RateLimiter::hit($key);
            return ApiResponse::failValidation($e->errors(), 'Login validation failed');
        }

        $user = User::withTrashed()->where('email', $validated['email'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            RateLimiter::hit($key);
            return ApiResponse::throw(['email' => ['The provided credentials are incorrect.']], 'Invalid credentials', 401);
        }

        // Check if user is soft deleted
        if ($user->trashed()) {
            RateLimiter::hit($key);
            return ApiResponse::throw(['email' => ['The provided credentials are incorrect.']], 'Invalid credentials', 401);
        }

        // Clear rate limiter on successful login
        RateLimiter::clear($key);

        // Generate token
        $deviceName = $validated['device_name'] ?? $request->userAgent();
        $token = $user->createToken($deviceName);

        // Update last login timestamp
        $user->update(['last_login_at' => now()]);

        return ApiResponse::send('Login successful', 200, [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'email_verified_at' => $user->email_verified_at,
                'created_at' => $user->created_at,
            ],
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer'
        ]);
    }

    /**
     * Logout user (revoke current token)
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            // Revoke current access token
            $request->user()->currentAccessToken()->delete();
            return ApiResponse::successMessage('Logged out successfully');
        } catch (\Exception $e) {
            return ApiResponse::serverError('Logout failed');
        }
    }

    /**
     * Logout from all devices (revoke all tokens)
     */
    public function logoutAll(Request $request): JsonResponse
    {
        try {
            // Revoke all tokens for the user
            $request->user()->tokens()->delete();
            return ApiResponse::successMessage('Logged out from all devices successfully');
        } catch (\Exception $e) {
            return ApiResponse::serverError('Logout from all devices failed');
        }
    }

    /**
     * Get authenticated user profile
     */
    public function user(Request $request): JsonResponse
    {
        $user = $request->user();
        
        return ApiResponse::show('User profile retrieved', [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'email_verified_at' => $user->email_verified_at,
            'last_login_at' => $user->last_login_at,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
        ]);
    }

    /**
     * Refresh token (optional - useful for long-lived sessions)
     */
    public function refresh(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $currentToken = $request->user()->currentAccessToken();
            
            // Delete current token
            $currentToken->delete();
            
            // Create new token
            $deviceName = $request->device_name ?? $request->userAgent();
            $newToken = $user->createToken($deviceName);

            return ApiResponse::send('Token refreshed successfully', 200, [
                'token' => $newToken->plainTextToken,
                'token_type' => 'Bearer'
            ]);
        } catch (\Exception $e) {
            return ApiResponse::serverError('Token refresh failed');
        }
    }

    /**
     * Get user's active sessions/tokens
     */
    public function sessions(Request $request): JsonResponse
    {
        try {
            $currentTokenId = $request->user()->currentAccessToken()->id;
            
            $sessions = $request->user()->tokens()->get()->map(function ($token) use ($currentTokenId) {
                return [
                    'id' => $token->id,
                    'name' => $token->name,
                    'last_used_at' => $token->last_used_at,
                    'created_at' => $token->created_at,
                    'is_current' => $token->id === $currentTokenId,
                ];
            });

            return ApiResponse::index('Active sessions retrieved', $sessions);
        } catch (\Exception $e) {
            return ApiResponse::serverError('Failed to retrieve sessions');
        }
    }

    /**
     * Revoke specific token/session
     */
    public function revokeSession(Request $request, $tokenId): JsonResponse
    {
        try {
            $user = $request->user();
            $token = $user->tokens()->where('id', $tokenId)->first();

            if (!$token) {
                return ApiResponse::notFound('Session not found');
            }

            // Don't allow revoking current session this way
            if ($token->id === $request->user()->currentAccessToken()->id) {
                return ApiResponse::throw(
                    ['session' => ['Cannot revoke current session']], 
                    'Cannot revoke current session. Use logout endpoint instead.',
                    400
                );
            }

            $token->delete();

            return ApiResponse::successMessage('Session revoked successfully');
        } catch (\Exception $e) {
            return ApiResponse::serverError('Failed to revoke session');
        }
    }

    /**
     * Change password for authenticated user
     */
    public function changePassword(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'current_password' => 'required',
                'password' => ['required', 'string', 'confirmed', Password::defaults()],
            ]);
        } catch (ValidationException $e) {
            return ApiResponse::failValidation($e->errors(), 'Password change validation failed');
        }

        $user = $request->user();

        // Verify current password
        if (!Hash::check($validated['current_password'], $user->password)) {
            return ApiResponse::throw(
                ['current_password' => ['Current password is incorrect']], 
                'Current password is incorrect',
                400
            );
        }

        try {
            // Update password
            $user->update([
                'password' => Hash::make($validated['password'])
            ]);

            // Optionally revoke all other tokens for security
            $currentToken = $request->user()->currentAccessToken();
            $user->tokens()->where('id', '!=', $currentToken->id)->delete();

            return ApiResponse::successMessage('Password changed successfully');
        } catch (\Exception $e) {
            return ApiResponse::serverError('Password change failed');
        }
    }
}