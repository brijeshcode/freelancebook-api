<?php

use App\Models\User;
use App\Models\Client;
use App\Models\Payment;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->client = Client::factory()->create(['user_id' => $this->user->id]);
    $this->actingAs($this->user, 'sanctum');
});

it('can list payments', function () {
    Payment::factory(3)->forUser($this->user)->create([
        'client_id' => $this->client->id
    ]);

    $response = $this->getJson('/api/payments');

    $response->assertOk()
        ->assertJsonStructure([
            'message',
            'data' => [
                '*' => [
                    'id',
                    'transaction_number',
                    'client_id',
                    'amount',
                    'payment_method',
                    'status'
                ]
            ],
            'pagination'
        ]);
});

it('can create a payment', function () {
    $paymentData = [
        'client_id' => $this->client->id,
        'amount' => 1500.00,
        'currency' => 'USD',
        'exchange_rate' => 1.0,
        'payment_date' => now()->format('Y-m-d'),
        'payment_method' => 'bank_transfer',
        'notes' => 'Payment for INV-2024-001'
    ];

    $response = $this->postJson('/api/payments', $paymentData);

    $response->assertCreated()
        ->assertJsonFragment(['message' => 'Payment recorded successfully']);
    
    $this->assertDatabaseHas('payments', [
        'client_id' => $this->client->id,
        'freelancer_id' => $this->user->id,
        'amount' => 1500.00
    ]);
});

it('can show a payment', function () {
    $payment = Payment::factory()->forUser($this->user)->create([
        'client_id' => $this->client->id
    ]);

    $response = $this->getJson("/api/payments/{$payment->id}");

    $response->assertOk()
        ->assertJsonFragment(['id' => $payment->id]);
});

it('cannot access other freelancer payments', function () {
    $otherUser = User::factory()->create();
    $payment = Payment::factory()->forUser($otherUser)->create();

    $response = $this->getJson("/api/payments/{$payment->id}");

    $response->assertForbidden();
});

it('can update a payment', function () {
    $payment = Payment::factory()->forUser($this->user)->create([
        'client_id' => $this->client->id
    ]);

    $updateData = [
        'notes' => 'Updated payment notes',
        'transaction_reference' => 'REF123456'
    ];

    $response = $this->putJson("/api/payments/{$payment->id}", $updateData);

    $response->assertOk()
        ->assertJsonFragment(['message' => 'Payment updated successfully']);
    
    $this->assertDatabaseHas('payments', [
        'id' => $payment->id,
        'notes' => 'Updated payment notes'
    ]);
});

it('can delete a payment', function () {
    $payment = Payment::factory()->forUser($this->user)->create([
        'client_id' => $this->client->id
    ]);

    $response = $this->deleteJson("/api/payments/{$payment->id}");

    $response->assertNoContent();
    $this->assertSoftDeleted('payments', ['id' => $payment->id]);
});

it('can verify a payment', function () {
    $payment = Payment::factory()->pending()->forUser($this->user)->create([
        'client_id' => $this->client->id
    ]);

    $response = $this->patchJson("/api/payments/{$payment->id}/verify");

    $response->assertOk();
    $this->assertDatabaseHas('payments', [
        'id' => $payment->id,
        'status' => 'completed',
        'verified_by' => $this->user->id
    ]);
});

it('validates required fields when creating payment', function () {
    $response = $this->postJson('/api/payments', []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['client_id', 'amount', 'currency', 'exchange_rate', 'payment_date', 'payment_method']);
});

it('calculates base currency amount correctly', function () {
    $paymentData = [
        'client_id' => $this->client->id,
        'amount' => 1000.00,
        'currency' => 'EUR',
        'exchange_rate' => 1.10, // 1 EUR = 1.10 USD
        'payment_date' => now()->format('Y-m-d'),
        'payment_method' => 'paypal'
    ];

    $response = $this->postJson('/api/payments', $paymentData);
    $payment = Payment::latest()->first();

    expect($payment->amount_base_currency)->toBe('1100.00');
});

it('can filter payments by status', function () {
    Payment::factory()->completed()->forUser($this->user)->create(['client_id' => $this->client->id]);
    Payment::factory()->pending()->forUser($this->user)->create(['client_id' => $this->client->id]);

    $response = $this->getJson('/api/payments?status=completed');

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1);
});