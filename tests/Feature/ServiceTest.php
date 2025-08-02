<?php

use App\Models\Client;
use App\Models\Project;
use App\Models\Service;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user, 'sanctum');
    
    $this->client = Client::factory()->create();
    $this->project = Project::factory()->create(['client_id' => $this->client->id]);
});

describe('Service Management', function () {
    
    it('can list services', function () {
        Service::factory()->count(3)->create(['created_by' => $this->user->id]);

        $response = $this->getJson('/api/services');

        $response->assertOk()
            ->assertJsonStructure([
                'message',
                'data' => [
                    '*' => [
                        'id', 'title', 'amount', 'currency', 'frequency', 'status'
                    ]
                ],
                'pagination'
            ]);
    });

    it('can create a service', function () {
        $serviceData = [
            'client_id' => $this->client->id,
            'project_id' => $this->project->id,
            'title' => 'Website Development',
            'description' => 'Frontend development service',
            'amount' => 50000,
            'currency' => 'INR',
            'has_tax' => true,
            'tax_name' => 'GST',
            'tax_rate' => 18.00,
            'tax_type' => 'exclusive',
            'frequency' => 'one-time',
            'start_date' => '2025-08-01',
            'status' => 'active',
            'is_active' => true,
        ];

        $response = $this->postJson('/api/services', $serviceData);

        $response->assertCreated()
            ->assertJsonFragment(['title' => 'Website Development'])
            ->assertJsonFragment(['total_amount' => 59000]); // 50000 + 18% GST

        $this->assertDatabaseHas('services', [
            'title' => 'Website Development',
            'created_by' => $this->user->id,
        ]);
    });

    it('can show a service', function () {
        $service = Service::factory()->create([
            'created_by' => $this->user->id,
            'client_id' => $this->client->id,
        ]);

        $response = $this->getJson("/api/services/{$service->id}");

        $response->assertOk()
            ->assertJsonFragment(['id' => $service->id]);
    });

    it('can update a service', function () {
        $service = Service::factory()->create([
            'created_by' => $this->user->id,
            'client_id' => $this->client->id,
        ]);

        $updateData = ['title' => 'Updated Service Title'];

        $response = $this->putJson("/api/services/{$service->id}", $updateData);

        $response->assertOk()
            ->assertJsonFragment(['title' => 'Updated Service Title']);

        $this->assertDatabaseHas('services', [
            'id' => $service->id,
            'title' => 'Updated Service Title',
        ]);
    });

    it('can soft delete a service', function () {
        $service = Service::factory()->create([
            'created_by' => $this->user->id,
            'client_id' => $this->client->id,
        ]);

        $response = $this->deleteJson("/api/services/{$service->id}");

        $response->assertNoContent();
        $this->assertSoftDeleted('services', ['id' => $service->id]);
    });

    it('can restore a soft deleted service', function () {
        $service = Service::factory()->create([
            'created_by' => $this->user->id,
            'client_id' => $this->client->id,
        ]);
        $service->delete();

        $response = $this->patchJson("/api/services/{$service->id}/restore");

        $response->assertOk();
        $this->assertDatabaseHas('services', [
            'id' => $service->id,
            'deleted_at' => null,
        ]);
    });

    it('can toggle service status', function () {
        $service = Service::factory()->create([
            'created_by' => $this->user->id,
            'client_id' => $this->client->id,
            'is_active' => true,
        ]);

        $response = $this->patchJson("/api/services/{$service->id}/toggle-status");

        $response->assertOk();
        $this->assertDatabaseHas('services', [
            'id' => $service->id,
            'is_active' => false,
        ]);
    });

    it('can filter services by client', function () {
        $otherClient = Client::factory()->create();
        
        Service::factory()->create(['client_id' => $this->client->id]);
        Service::factory()->create(['client_id' => $otherClient->id]);

        $response = $this->getJson("/api/services?client_id={$this->client->id}");

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    });

    it('can get services ready for billing', function () {
        Service::factory()->create([
            'status' => 'active',
            'is_active' => true,
            'frequency' => 'monthly',
            'next_billing_date' => now()->subDay(),
        ]);

        Service::factory()->create([
            'status' => 'active',
            'is_active' => true,
            'frequency' => 'monthly',
            'next_billing_date' => now()->addWeek(),
        ]);

        $response = $this->getJson('/api/services/ready-for-billing');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    });

    it('can get recurring services', function () {
        Service::factory()->oneTime()->create();
        Service::factory()->recurring()->active()->create();

        $response = $this->getJson('/api/services/recurring');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    });
});

describe('Service Tax Calculations', function () {
    
    it('calculates exclusive tax correctly', function () {
        $service = Service::factory()->create([
            'amount' => 50000,
            'has_tax' => true,
            'tax_rate' => 18.00,
            'tax_type' => 'exclusive',
        ]);

        expect($service->getBaseAmount())->toBe(50000.0);
        expect($service->getTaxAmount())->toBe(9000.0);
        expect($service->getTotalAmount())->toBe(59000.0);
    });

    it('calculates inclusive tax correctly', function () {
        $service = Service::factory()->create([
            'amount' => 59000,
            'has_tax' => true,
            'tax_rate' => 18.00,
            'tax_type' => 'inclusive',
        ]);

        expect($service->getBaseAmount())->toBe(50000.0);
        expect($service->getTaxAmount())->toBe(9000.0);
        expect($service->getTotalAmount())->toBe(59000.0);
    });

    it('handles no tax correctly', function () {
        $service = Service::factory()->withoutTax()->create([
            'amount' => 50000,
        ]);

        expect($service->getBaseAmount())->toBe(50000.0);
        expect($service->getTaxAmount())->toBe(0.0);
        expect($service->getTotalAmount())->toBe(50000.0);
    });
});

describe('Service Validation', function () {
    
    it('validates required fields', function () {
        $response = $this->postJson('/api/services', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'client_id', 'title', 'amount', 'currency', 'frequency', 'start_date', 'status'
            ]);
    });

    it('validates tax fields when tax is applied', function () {
        $response = $this->postJson('/api/services', [
            'client_id' => $this->client->id,
            'title' => 'Test Service',
            'amount' => 1000,
            'currency' => 'INR',
            'frequency' => 'one-time',
            'start_date' => '2025-08-01',
            'status' => 'active',
            'has_tax' => true,
            // Missing tax_name, tax_rate, tax_type
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['tax_name', 'tax_rate', 'tax_type']);
    });

    it('validates currency format', function () {
        $response = $this->postJson('/api/services', [
            'client_id' => $this->client->id,
            'title' => 'Test Service',
            'amount' => 1000,
            'currency' => 'INVALID', // Should be 3 characters
            'frequency' => 'one-time',
            'start_date' => '2025-08-01',
            'status' => 'active',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['currency']);
    });

    it('validates frequency enum', function () {
        $response = $this->postJson('/api/services', [
            'client_id' => $this->client->id,
            'title' => 'Test Service',
            'amount' => 1000,
            'currency' => 'INR',
            'frequency' => 'invalid-frequency',
            'start_date' => '2025-08-01',
            'status' => 'active',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['frequency']);
    });

    it('automatically sets next_billing_date to null for one-time services', function () {
        $response = $this->postJson('/api/services', [
            'client_id' => $this->client->id,
            'title' => 'One Time Service',
            'amount' => 1000,
            'currency' => 'INR',
            'frequency' => 'one-time',
            'start_date' => '2025-08-01',
            'status' => 'active',
            'next_billing_date' => '2025-09-01', // This should be ignored
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('services', [
            'frequency' => 'one-time',
            'next_billing_date' => null,
        ]);
    });
});

describe('Service Relationships', function () {
    
    it('belongs to a client', function () {
        $service = Service::factory()->create(['client_id' => $this->client->id]);

        expect($service->client)->toBeInstanceOf(Client::class);
        expect($service->client->id)->toBe($this->client->id);
    });

    it('can belong to a project', function () {
        $service = Service::factory()->create([
            'client_id' => $this->client->id,
            'project_id' => $this->project->id,
        ]);

        expect($service->project)->toBeInstanceOf(Project::class);
        expect($service->project->id)->toBe($this->project->id);
    });

    it('belongs to a creator', function () {
        $service = Service::factory()->create(['created_by' => $this->user->id]);

        expect($service->creator)->toBeInstanceOf(User::class);
        expect($service->creator->id)->toBe($this->user->id);
    });
});