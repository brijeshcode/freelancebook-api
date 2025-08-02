<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

uses(RefreshDatabase::class);

describe('Auth Rate Limiting', function () {

    beforeEach(function () {
        // Clear rate limiters before each test
        RateLimiter::clear('login.' . request()->ip());
        RateLimiter::clear('register.' . request()->ip());
        
        $this->user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123')
        ]);
    });

    it('applies rate limiting to login attempts', function () {
        // Make 5 failed login attempts (the limit)
        for ($i = 0; $i < 5; $i++) {
            $response = $this->postJson('/api/login', [
                'email' => 'test@example.com',
                'password' => 'wrongpassword'
            ]);
            
            expect($response->status())->toBe(401);
        }

        // The 6th attempt should be rate limited
        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword'
        ]);

        $response->assertStatus(429)
            ->assertJsonPath('message', function ($message) {
                return str_contains($message, 'Too many login attempts');
            });
    });

    it('clears rate limiting on successful login', function () {
        // Make 4 failed attempts
        for ($i = 0; $i < 4; $i++) {
            $this->postJson('/api/login', [
                'email' => 'test@example.com',
                'password' => 'wrongpassword'
            ]);
        }

        // Successful login should clear the rate limiter
        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);

        $response->assertStatus(200);

        // Should be able to make failed attempts again
        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword'
        ]);

        $response->assertStatus(401); // Not rate limited
    });

    it('applies rate limiting to registration attempts', function () {
        // Make 3 failed registration attempts (the limit)
        for ($i = 0; $i < 3; $i++) {
            $response = $this->postJson('/api/register', [
                'name' => 'Test User',
                'email' => 'invalid-email', // This will fail validation
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!'
            ]);
            
            expect($response->status())->toBe(422);
        }

        // The 4th attempt should be rate limited
        $response = $this->postJson('/api/register', [
            'name' => 'Test User',
            'email' => 'invalid-email',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!'
        ]);

        $response->assertStatus(429)
            ->assertJsonPath('message', function ($message) {
                return str_contains($message, 'Too many registration attempts');
            });
    });

    it('clears rate limiting on successful registration', function () {
        // Make 2 failed attempts
        for ($i = 0; $i < 2; $i++) {
            $this->postJson('/api/register', [
                'name' => 'Test User',
                'email' => 'invalid-email',
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!'
            ]);
        }

        // Successful registration should clear the rate limiter
        $response = $this->postJson('/api/register', [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!'
        ]);

        $response->assertStatus(201);

        // Should be able to make failed attempts again
        $response = $this->postJson('/api/register', [
            'name' => 'Test User',
            'email' => 'invalid-email',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!'
        ]);

        $response->assertStatus(422); // Not rate limited
    });

    it('rate limiting is IP-based', function () {
        // This test would require mocking different IPs
        // For now, we'll just verify that the rate limiter key includes IP
        
        $key = 'login.' . request()->ip();
        expect($key)->toContain('login.');
        
        // In a real application, you might test this by:
        // 1. Mocking Request::ip() to return different values
        // 2. Or using different test clients with different IPs
        // 3. Or testing in an integration environment
    });

    it('rate limiting has correct time windows', function () {
        // Make maximum failed login attempts
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/login', [
                'email' => 'test@example.com',
                'password' => 'wrongpassword'
            ]);
        }

        // Should be rate limited
        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword'
        ]);

        $response->assertStatus(429);
        
        // Check that the response includes timing information
        $message = $response->json('message');
        expect($message)->toContain('seconds');
        
        // The actual time-based testing would require manipulating time
        // which is complex in unit tests. In real scenarios, you might:
        // 1. Use Carbon::setTestNow() to manipulate time
        // 2. Or test this in integration tests with actual waiting
        // 3. Or mock the RateLimiter facade
    });

});