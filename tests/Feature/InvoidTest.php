<?php

use App\Models\User;
use App\Models\Client;
use App\Models\Project;
use App\Models\Invoice;
use App\Models\FreelancerSetting;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->client = Client::factory()->create(['user_id' => $this->user->id]);
    $this->project = Project::factory()->create(['client_id' => $this->client->id]);
    
    FreelancerSetting::factory()->create([
        'freelancer_id' => $this->user->id,
        'invoice_prefix' => 'INV',
        'next_invoice_number' => 1,
        'invoice_year' => date('Y')
    ]);
    
    $this->actingAs($this->user, 'sanctum');
});

it('can list invoices', function () {
    Invoice::factory(3)->create(['freelancer_id' => $this->user->id, 'client_id' => $this->client->id]);

    $response = $this->getJson('/api/invoices');

    $response->assertOk()
        ->assertJsonStructure([
            'message',
            'data' => [
                '*' => [
                    'id',
                    'invoice_number',
                    'client_id',
                    'total_amount',
                    'status'
                ]
            ],
            'pagination'
        ]);
});

it('can create an invoice', function () {
    $invoiceData = [
        'client_id' => $this->client->id,
        'project_id' => $this->project->id,
        'invoice_date' => now()->format('Y-m-d'),
        'currency' => 'USD',
        'exchange_rate' => 1.0,
        'tax_rate' => 10,
        'items' => [
            [
                'description' => 'Web Development',
                'quantity' => 1,
                'unit_price' => 1000.00
            ]
        ]
    ];

    $response = $this->postJson('/api/invoices', $invoiceData);

    $response->assertCreated()
        ->assertJsonFragment(['message' => 'Invoice created successfully']);
    
    $this->assertDatabaseHas('invoices', [
        'client_id' => $this->client->id,
        'freelancer_id' => $this->user->id
    ]);
});

it('can show an invoice', function () {
    $invoice = Invoice::factory()->create([
        'freelancer_id' => $this->user->id,
        'client_id' => $this->client->id
    ]);

    $response = $this->getJson("/api/invoices/{$invoice->id}");

    $response->assertOk()
        ->assertJsonFragment(['id' => $invoice->id]);
});

it('cannot access other freelancer invoices', function () {
    $otherUser = User::factory()->create();
    $invoice = Invoice::factory()->create(['freelancer_id' => $otherUser->id]);

    $response = $this->getJson("/api/invoices/{$invoice->id}");

    $response->assertForbidden();
});

it('can update an invoice', function () {
    $invoice = Invoice::factory()->create([
        'freelancer_id' => $this->user->id,
        'client_id' => $this->client->id
    ]);

    $updateData = [
        'notes' => 'Updated notes'
    ];

    $response = $this->putJson("/api/invoices/{$invoice->id}", $updateData);

    $response->assertOk()
        ->assertJsonFragment(['message' => 'Invoice updated successfully']);
    
    $this->assertDatabaseHas('invoices', [
        'id' => $invoice->id,
        'notes' => 'Updated notes'
    ]);
});

it('can delete an invoice', function () {
    $invoice = Invoice::factory()->create([
        'freelancer_id' => $this->user->id,
        'client_id' => $this->client->id
    ]);

    $response = $this->deleteJson("/api/invoices/{$invoice->id}");

    $response->assertNoContent();
    $this->assertSoftDeleted('invoices', ['id' => $invoice->id]);
});

it('can mark invoice as sent', function () {
    $invoice = Invoice::factory()->draft()->create([
        'freelancer_id' => $this->user->id,
        'client_id' => $this->client->id
    ]);

    $response = $this->patchJson("/api/invoices/{$invoice->id}/mark-as-sent");

    $response->assertOk();
    $this->assertDatabaseHas('invoices', [
        'id' => $invoice->id,
        'status' => 'sent'
    ]);
});

it('validates required fields when creating invoice', function () {
    $response = $this->postJson('/api/invoices', []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['client_id', 'invoice_date', 'currency', 'exchange_rate', 'items']);
});