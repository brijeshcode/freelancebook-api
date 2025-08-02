<?php

use App\Models\Client;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    // $this->actingAs($this->user, 'sanctum');
    login($this->user);
});

test('can list clients', function () {
    Client::factory()->count(2)->create(['user_id' => $this->user->id]);
    
    $response = $this->getJson('/api/clients');
    
    $response->assertOk();
    expect($response->json('data'))->toHaveCount(2);
});

test('can create client', function () {
    $response = $this->postJson('/api/clients/store', [
        'name' => 'Test Company',
        'type' => 'company',
        'email' => 'test@company.com'
    ]);
    
    $response->assertStatus(201);
    $this->assertDatabaseHas('clients', ['name' => 'Test Company']);
});

test('validates required fields', function () {
    $response = $this->postJson('/api/clients/store', []);
    
    $response->assertStatus(422)
        ->assertJsonValidationErrors(['name', 'type']);
});

test('can show own client', function () {
    $client = Client::factory()->create(['user_id' => $this->user->id]);
    
    $response = $this->getJson("/api/clients/{$client->id}");
    
    $response->assertOk()
        ->assertJsonFragment(['name' => $client->name]);
});

test('cannot access other users client', function () {
    $otherUser = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $otherUser->id]);
    
    $response = $this->getJson("/api/clients/{$client->id}");
    
    $response->assertForbidden();
});

test('can update client', function () {
    $client = Client::factory()->create(['user_id' => $this->user->id]);
    
    $response = $this->putJson("/api/clients/{$client->id}", [
        'name' => 'Updated Name'
    ]);
    
    $response->assertOk();
    expect($client->fresh()->name)->toBe('Updated Name');
});

test('can soft delete client', function () {
    $client = Client::factory()->create(['user_id' => $this->user->id]);
    
    $response = $this->deleteJson("/api/clients/{$client->id}");
    
    $response->assertStatus(204);
    $this->assertSoftDeleted('clients', ['id' => $client->id]);
});

test('can view trashed clients', function () {
    $client = Client::factory()->create(['user_id' => $this->user->id]);
    $client->delete();
    
    $response = $this->getJson('/api/clients/only-trashed/list');
    
    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1);
});

test('can restore deleted client', function () {
    $client = Client::factory()->create(['user_id' => $this->user->id]);
    $client->delete();
    
    $response = $this->patchJson("/api/clients/{$client->id}/restore");
    
    $response->assertOk();
    expect($client->fresh()->deleted_at)->toBeNull();
});

test('can change client status', function () {
    $client = Client::factory()->create(['user_id' => $this->user->id, 'status' => 'active']);
    
    $response = $this->patchJson("/api/clients/{$client->id}/change-status", [
        'status' => 'inactive'
    ]);
    
    $response->assertOk();
    expect($client->fresh()->status)->toBe('inactive');
});