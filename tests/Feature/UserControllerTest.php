describe('UserController Destroy Tests', function () {
    
    it('can soft delete a user', function () {
        Sanctum::actingAs($this->adminUser);
        
        $user = User::factory()->create(['name' => 'To Be Deleted']);
        
        $response = $this->deleteJson(route('users.destroy', $user));
        
        $response->assertStatus(204<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create a test admin user for authentication
    $this->adminUser = User::factory()->create([
        'role' => User::ROLE_ADMIN,
        'email' => 'admin@test.com'
    ]);
    
    $this->freelancerUser = User::factory()->create([
        'role' => User::ROLE_FREELANCER,
        'email' => 'freelancer@test.com'
    ]);
    
    $this->clientUser = User::factory()->create([
        'role' => User::ROLE_CLIENT,
        'email' => 'client@test.com'
    ]);
});

describe('UserController Index Tests', function () {
    
    it('can list active users with pagination', function () {
        Sanctum::actingAs($this->adminUser);
        
        // Create additional users
        User::factory()->count(15)->create();
        
        $response = $this->getJson(route('users.index'));
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    '*' => ['id', 'name', 'email', 'role', 'created_at', 'updated_at']
                ],
                'pagination' => [
                    'current_page',
                    'per_page',
                    'total',
                    'last_page',
                    'from',
                    'to',
                    'has_more_pages',
                    'next_page_url',
                    'prev_page_url',
                    'first_page_url',
                    'last_page_url'
                ]
            ]);
            
        expect($response->json('message'))->toBe('Users retrieved successfully');
        expect($response->json('pagination.total'))->toBe(18); // 3 beforeEach + 15 factory
    });
    
    it('can list users with custom pagination parameters', function () {
        Sanctum::actingAs($this->adminUser);
        
        User::factory()->count(25)->create();
        
        $response = $this->getJson(route('users.index', ['page' => 2, 'per_page' => 5]));
        
        $response->assertStatus(200);
        expect($response->json('pagination.current_page'))->toBe(2);
        expect($response->json('pagination.per_page'))->toBe(5);
        expect(count($response->json('data')))->toBe(5);
    });
    
    it('excludes soft deleted users from index', function () {
        Sanctum::actingAs($this->adminUser);
        
        $deletedUser = User::factory()->create();
        $deletedUser->delete();
        
        $response = $this->getJson(route('users.index'));
        
        $response->assertStatus(200);
        $userIds = collect($response->json('data'))->pluck('id')->toArray();
        expect($userIds)->not->toContain($deletedUser->id);
    });
});

describe('UserController IndexWithTrashed Tests', function () {
    
    it('can list all users including soft deleted', function () {
        Sanctum::actingAs($this->adminUser);
        
        $deletedUser = User::factory()->create();
        $deletedUser->delete();
        
        $response = $this->getJson(route('users.with-trashed'));
        
        $response->assertStatus(200);
        expect($response->json('message'))->toBe('All users (including deleted) retrieved successfully');
        
        $userIds = collect($response->json('data'))->pluck('id')->toArray();
        expect($userIds)->toContain($deletedUser->id);
    });
});

describe('UserController OnlyTrashed Tests', function () {
    
    it('can list only soft deleted users', function () {
        Sanctum::actingAs($this->adminUser);
        
        $deletedUser1 = User::factory()->create();
        $deletedUser2 = User::factory()->create();
        $deletedUser1->delete();
        $deletedUser2->delete();
        
        $response = $this->getJson(route('users.only-trashed'));
        
        $response->assertStatus(200);
        expect($response->json('message'))->toBe('Deleted users retrieved successfully');
        expect(count($response->json('data')))->toBe(2);
        
        $userIds = collect($response->json('data'))->pluck('id')->toArray();
        expect($userIds)->toContain($deletedUser1->id, $deletedUser2->id);
    });
    
    it('returns empty result when no deleted users exist', function () {
        Sanctum::actingAs($this->adminUser);
        
        $response = $this->getJson(route('users.only-trashed'));
        
        $response->assertStatus(200);
        expect(count($response->json('data')))->toBe(0);
    });
});

describe('UserController Show Tests', function () {
    
    it('can show a specific user', function () {
        Sanctum::actingAs($this->adminUser);
        
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@test.com',
            'role' => User::ROLE_CLIENT
        ]);
        
        $response = $this->getJson(route('users.show', $user));
        
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'User retrieved successfully',
                'data' => [
                    'id' => $user->id,
                    'name' => 'John Doe',
                    'email' => 'john@test.com',
                    'role' => User::ROLE_CLIENT
                ]
            ]);
    });
    
    it('returns 404 for non-existent user', function () {
        Sanctum::actingAs($this->adminUser);
        
        $response = $this->getJson(route('users.show', 999999));
        
        $response->assertStatus(404);
    });
    
    it('cannot show soft deleted user without proper endpoint', function () {
        Sanctum::actingAs($this->adminUser);
        
        $user = User::factory()->create();
        $user->delete();
        
        $response = $this->getJson(route('users.show', $user->id));
        
        $response->assertStatus(404);
    });
});

describe('UserController Store Tests', function () {
    
    it('can create a new user with valid data', function () {
        Sanctum::actingAs($this->adminUser);
        
        $userData = [
            'name' => 'New User',
            'email' => 'newuser@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => User::ROLE_CLIENT,
            'phone' => '+1234567890',
            'address' => '123 Test Street'
        ];
        
        $response = $this->postJson(route('users.store'), $userData);
        
        $response->assertStatus(201)
            ->assertJson([
                'message' => 'User created successfully',
                'data' => [
                    'name' => 'New User',
                    'email' => 'newuser@test.com',
                    'role' => User::ROLE_CLIENT
                ]
            ]);
            
        $this->assertDatabaseHas('users', [
            'name' => 'New User',
            'email' => 'newuser@test.com',
            'role' => User::ROLE_CLIENT
        ]);
        
        // Verify password is hashed
        $user = User::where('email', 'newuser@test.com')->first();
        expect(password_verify('password123', $user->password))->toBeTrue();
    });
    
    it('validates required fields', function () {
        Sanctum::actingAs($this->adminUser);
        
        $response = $this->postJson(route('users.store'), []);
        
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password', 'role']);
    });
    
    it('validates email uniqueness', function () {
        Sanctum::actingAs($this->adminUser);
        
        $existingUser = User::factory()->create(['email' => 'existing@test.com']);
        
        $userData = [
            'name' => 'New User',
            'email' => 'existing@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => User::ROLE_CLIENT
        ];
        
        $response = $this->postJson(route('users.store'), $userData);
        
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    });
    
    it('validates password confirmation', function () {
        Sanctum::actingAs($this->adminUser);
        
        $userData = [
            'name' => 'New User',
            'email' => 'newuser@test.com',
            'password' => 'password123',
            'password_confirmation' => 'differentpassword',
            'role' => User::ROLE_CLIENT
        ];
        
        $response = $this->postJson(route('users.store'), $userData);
        
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    });
    
    it('validates role is from available roles', function () {
        Sanctum::actingAs($this->adminUser);
        
        $userData = [
            'name' => 'New User',
            'email' => 'newuser@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'invalid_role'
        ];
        
        $response = $this->postJson(route('users.store'), $userData);
        
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['role']);
    });
    
    // it('creates user without optional fields', function () {
    //     Sanctum::actingAs($this->adminUser);
        
    //     $userData = [
    //         'name' => 'Minimal User',
    //         'email' => 'minimal@test.com',
    //         'password' => 'password123',
    //         'password_confirmation' => 'password123',
    //         'role' => User::ROLE_FREELANCER
    //     ];
        
    //     $response = $this->postJson(route('users.store'), $userData);
        
    //     $response->assertStatus(201);
    //     $this->assertDatabaseHas('users', [
    //         'name' => 'Minimal User',
    //         'email' => 'minimal@test.com',
    //         'role' => User::ROLE_FREELANCER,
    //         'phone' => null,
    //         'address' => null
    //     ]);
    // });
});

describe('UserController Update Tests', function () {
    
    it('can update user with valid data', function () {
        Sanctum::actingAs($this->adminUser);
        
        $user = User::factory()->create([
            'name' => 'Original Name',
            'email' => 'original@test.com',
            'role' => User::ROLE_CLIENT
        ]);
        
        $updateData = [
            'name' => 'Updated Name',
            'email' => 'updated@test.com',
            'role' => User::ROLE_FREELANCER
        ];
        
        $response = $this->putJson(route('users.update', $user), $updateData);
        
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'User updated successfully',
                'data' => [
                    'id' => $user->id,
                    'name' => 'Updated Name',
                    'email' => 'updated@test.com',
                    'role' => User::ROLE_FREELANCER
                ]
            ]);
            
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
            'email' => 'updated@test.com',
            'role' => User::ROLE_FREELANCER
        ]);
    });
    
    it('can update user with patch method', function () {
        Sanctum::actingAs($this->adminUser);
        
        $user = User::factory()->create([
            'name' => 'Original Name',
            'email' => 'original@test.com'
        ]);
        
        $updateData = ['name' => 'Patched Name'];
        
        $response = $this->patchJson(route('users.patch', $user), $updateData);
        
        $response->assertStatus(200);
        
        $updatedUser = User::find($user->id);
        expect($updatedUser->name)->toBe('Patched Name');
        expect($updatedUser->email)->toBe('original@test.com'); // Unchanged
    });
    
    it('can update password', function () {
        Sanctum::actingAs($this->adminUser);
        
        $user = User::factory()->create();
        $originalPassword = $user->password;
        
        $updateData = [
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123'
        ];
        
        $response = $this->putJson(route('users.update', $user), $updateData);
        
        $response->assertStatus(200);
        
        $updatedUser = User::find($user->id);
        expect($updatedUser->password)->not->toBe($originalPassword);
        expect(password_verify('newpassword123', $updatedUser->password))->toBeTrue();
    });
    
    it('can update partial data', function () {
        Sanctum::actingAs($this->adminUser);
        
        $user = User::factory()->create([
            'name' => 'Original Name',
            'email' => 'original@test.com'
        ]);
        
        $updateData = ['name' => 'Updated Name Only'];
        
        $response = $this->putJson(route('users.update', $user), $updateData);
        
        $response->assertStatus(200);
        
        $updatedUser = User::find($user->id);
        expect($updatedUser->name)->toBe('Updated Name Only');
        expect($updatedUser->email)->toBe('original@test.com'); // Unchanged
    });
    
    it('validates email uniqueness on update excluding current user', function () {
        Sanctum::actingAs($this->adminUser);
        
        $user1 = User::factory()->create(['email' => 'user1@test.com']);
        $user2 = User::factory()->create(['email' => 'user2@test.com']);
        
        // Should fail - trying to update to existing email
        $response = $this->putJson(route('users.update', $user1), [
            'email' => 'user2@test.com'
        ]);
        
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
            
        // Should pass - updating to same email
        $response = $this->putJson(route('users.update', $user1), [
            'email' => 'user1@test.com'
        ]);
        
        $response->assertStatus(200);
    });
    
    it('returns 404 for non-existent user', function () {
        Sanctum::actingAs($this->adminUser);
        
        $response = $this->putJson(route('users.update', 999999), ['name' => 'Updated']);
        
        $response->assertStatus(404);
    });
});

describe('UserController Destroy Tests', function () {
    
    it('can soft delete a user', function () {
        Sanctum::actingAs($this->adminUser);
        
        $user = User::factory()->create(['name' => 'To Be Deleted']);
        
        $response = $this->deleteJson(route('users.destroy', $user));
        
        $response->assertStatus(204);
        
        $this->assertSoftDeleted('users', ['id' => $user->id]);
        
        // Verify user still exists but is soft deleted
        $deletedUser = User::withTrashed()->find($user->id);
        expect($deletedUser)->not->toBeNull();
        expect($deletedUser->deleted_at)->not->toBeNull();
    });
    
    it('returns 404 for non-existent user', function () {
        Sanctum::actingAs($this->adminUser);
        
        $response = $this->deleteJson(route('users.destroy', 999999));
        
        $response->assertStatus(404);
    });
});

describe('UserController Restore Tests', function () {
    
    it('can restore a soft deleted user', function () {
        Sanctum::actingAs($this->adminUser);
        
        $user = User::factory()->create(['name' => 'Deleted User']);
        $user->delete();
        
        $response = $this->patchJson(route('users.restore', $user->id));
        
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'User restored successfully',
                'data' => [
                    'id' => $user->id,
                    'name' => 'Deleted User'
                ]
            ]);
            
        $restoredUser = User::find($user->id);
        expect($restoredUser)->not->toBeNull();
        expect($restoredUser->deleted_at)->toBeNull();
    });
    
    it('returns 404 for non-existent user', function () {
        Sanctum::actingAs($this->adminUser);
        
        $response = $this->patchJson(route('users.restore', 999999));
        
        $response->assertStatus(404);
    });
    
    it('returns 400 for user that is not deleted', function () {
        Sanctum::actingAs($this->adminUser);
        
        $user = User::factory()->create();
        
        $response = $this->patchJson(route('users.restore', $user->id));
        
        $response->assertStatus(400)
            ->assertJson(['message' => 'User is not deleted']);
    });
});

describe('UserController ForceDestroy Tests', function () {
    
    it('can permanently delete a user', function () {
        Sanctum::actingAs($this->adminUser);
        
        $user = User::factory()->create();
        $userId = $user->id;
        
        $response = $this->deleteJson(route('users.force-destroy', $userId));
        
        $response->assertStatus(204);
        
        // Verify user is completely removed from database
        $deletedUser = User::withTrashed()->find($userId);
        expect($deletedUser)->toBeNull();
        
        $this->assertDatabaseMissing('users', ['id' => $userId]);
    });
    
    it('can force delete an already soft deleted user', function () {
        Sanctum::actingAs($this->adminUser);
        
        $user = User::factory()->create();
        $userId = $user->id;
        $user->delete(); // Soft delete first
        
        $response = $this->deleteJson(route('users.force-destroy', $userId));
        
        $response->assertStatus(204);
        
        $deletedUser = User::withTrashed()->find($userId);
        expect($deletedUser)->toBeNull();
    });
    
    it('returns 404 for non-existent user', function () {
        Sanctum::actingAs($this->adminUser);
        
        $response = $this->deleteJson(route('users.force-destroy', 999999));
        
        $response->assertStatus(404);
    });
});

describe('UserController ChangeRole Tests', function () {
    
    it('can change user role', function () {
        Sanctum::actingAs($this->adminUser);
        
        $user = User::factory()->create(['role' => User::ROLE_CLIENT]);
        
        $response = $this->patchJson(route('users.change-role', $user), [
            'role' => User::ROLE_FREELANCER
        ]);
        
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'User role updated successfully',
                'data' => [
                    'id' => $user->id,
                    'role' => User::ROLE_FREELANCER
                ]
            ]);
            
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'role' => User::ROLE_FREELANCER
        ]);
    });
    
    it('validates role is from available roles', function () {
        Sanctum::actingAs($this->adminUser);
        
        $user = User::factory()->create(['role' => User::ROLE_CLIENT]);
        
        $response = $this->patchJson(route('users.change-role', $user), [
            'role' => 'invalid_role'
        ]);
        
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['role']);
    });
    
    it('requires role field', function () {
        Sanctum::actingAs($this->adminUser);
        
        $user = User::factory()->create();
        
        $response = $this->patchJson(route('users.change-role', $user), []);
        
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['role']);
    });
    
    it('returns 404 for non-existent user', function () {
        Sanctum::actingAs($this->adminUser);
        
        $response = $this->patchJson(route('users.change-role', 999999), [
            'role' => User::ROLE_ADMIN
        ]);
        
        $response->assertStatus(404);
    });
});

describe('Authorization Tests', function () {
    
    it('requires authentication for all endpoints', function () {
        $user = User::factory()->create();
        
        $endpoints = [
            ['GET', route('users.index')],
            ['GET', route('users.with-trashed')],
            ['GET', route('users.only-trashed')],
            ['GET', route('users.show', $user)],
            ['POST', route('users.store')],
            ['PUT', route('users.update', $user)],
            ['PATCH', route('users.patch', $user)],
            ['DELETE', route('users.destroy', $user)],
            ['PATCH', route('users.restore', $user->id)],
            ['DELETE', route('users.force-destroy', $user->id)],
            ['PATCH', route('users.change-role', $user)],
        ];
        
        foreach ($endpoints as [$method, $endpoint]) {
            $response = $this->json($method, $endpoint);
            $response->assertStatus(401);
        }
    });
});

describe('Error Handling Tests', function () {
    
    // it('handles database errors gracefully', function () {
    //     Sanctum::actingAs($this->adminUser);
        
    //     // Mock a database error scenario
    //     $this->mock(\Illuminate\Database\Eloquent\Builder::class)
    //         ->shouldReceive('create')
    //         ->andThrow(new \Exception('Database connection failed'));
            
    //     $userData = [
    //         'name' => 'Test User',
    //         'email' => 'test@test.com',
    //         'password' => 'password123',
    //         'password_confirmation' => 'password123',
    //         'role' => User::ROLE_CLIENT
    //     ];
        
    //     $response = $this->postJson('/api/users', $userData);
        
    //     // Should handle gracefully without exposing internal errors
    //     $response->assertStatus(500);
    // });
    
    it('handles validation exceptions properly', function () {
        Sanctum::actingAs($this->adminUser);
        
        $response = $this->postJson(route('users.store'), [
            'name' => '', // Invalid
            'email' => 'invalid-email', // Invalid
            'password' => '123', // Too short
            'role' => 'invalid' // Invalid
        ]);
        // $response->dump();
        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors' => [
                    'name',
                    'email',
                    'password',
                    'role'
                ]
            ]);
    });
});

// Additional utility tests for User model methods
describe('User Model Method Tests', function () {
    
    it('has role check methods working correctly', function () {
        $adminUser = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $freelancerUser = User::factory()->create(['role' => User::ROLE_FREELANCER]);
        $clientUser = User::factory()->create(['role' => User::ROLE_CLIENT]);
        
        // Admin checks
        expect($adminUser->isAdmin())->toBeTrue();
        expect($adminUser->isFreelancer())->toBeFalse();
        expect($adminUser->isClient())->toBeFalse();
        expect($adminUser->hasRole(User::ROLE_ADMIN))->toBeTrue();
        
        // Freelancer checks
        expect($freelancerUser->isAdmin())->toBeFalse();
        expect($freelancerUser->isFreelancer())->toBeTrue();
        expect($freelancerUser->isClient())->toBeFalse();
        expect($freelancerUser->hasRole(User::ROLE_FREELANCER))->toBeTrue();
        
        // Client checks
        expect($clientUser->isAdmin())->toBeFalse();
        expect($clientUser->isFreelancer())->toBeFalse();
        expect($clientUser->isClient())->toBeTrue();
        expect($clientUser->hasRole(User::ROLE_CLIENT))->toBeTrue();
    });
    
    it('returns correct available roles', function () {
        $availableRoles = User::getAvailableRoles();
        
        expect($availableRoles)->toBe([
            User::ROLE_ADMIN,
            User::ROLE_FREELANCER,
            User::ROLE_CLIENT
        ]);
    });
});