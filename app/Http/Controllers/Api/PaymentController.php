<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PaymentStoreRequest;
use App\Http\Requests\PaymentUpdateRequest;
use App\Http\Resources\PaymentResource;
use App\Http\Responses\ApiResponse;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    private int $userId ; 
    public function __construct()
    {
        $this->userId = Auth::id();
    }
    
    public function index(Request $request)
    {
        $payments = Payment::whereHas('client', function($q) {
                $q->where('user_id', $this->userId);
            })
            ->with(['client'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->client_id, fn($q) => $q->where('client_id', $request->client_id))
            ->when($request->payment_method, fn($q) => $q->byPaymentMethod($request->payment_method))
            ->latest('payment_date')
            ->paginate($request->per_page ?? 15);

        return ApiResponse::paginated('Payments retrieved successfully', $payments, PaymentResource::class);
    }

    public function store(PaymentStoreRequest $request)
    {
        $paymentData = $request->validated();
        $paymentData['freelancer_id'] = $this->userId;
        $paymentData['transaction_number'] = 'PAY-' . date('Y') . '-' . strtoupper(Str::random(6));
        $paymentData['amount_base_currency'] = $request->amount * $request->exchange_rate;
        $paymentData['status'] = $request->status ?? 'completed';

        $payment = Payment::create($paymentData);
        $payment->load('client');

        return ApiResponse::store('Payment recorded successfully', new PaymentResource($payment));
    }

    public function show(Payment $payment)
    {
        if ($payment->freelancer_id !== $this->userId) {
            return ApiResponse::forbidden('Access denied to this payment');
        }

        $payment->load(['client', 'verifiedBy']);
        return ApiResponse::show('Payment retrieved successfully', new PaymentResource($payment));
    }

    public function update(PaymentUpdateRequest $request, Payment $payment)
    {
        if ($payment->freelancer_id !== $this->userId) {
            return ApiResponse::forbidden('Access denied to this payment');
        }

        $updateData = $request->validated();
        
        // Recalculate base currency amount if amount or exchange rate changed
        if ($request->has('amount') || $request->has('exchange_rate')) {
            $amount = $request->amount ?? $payment->amount;
            $exchangeRate = $request->exchange_rate ?? $payment->exchange_rate;
            $updateData['amount_base_currency'] = $amount * $exchangeRate;
        }

        $payment->update($updateData);
        $payment->load('client');

        return ApiResponse::update('Payment updated successfully', new PaymentResource($payment));
    }

    public function destroy(Payment $payment)
    {
        if ($payment->freelancer_id !== $this->userId) {
            return ApiResponse::forbidden('Access denied to this payment');
        }

        $payment->delete();
        return ApiResponse::delete('Payment deleted successfully');
    }

    public function verify(Payment $payment)
    {
        if ($payment->freelancer_id !== $this->userId) {
            return ApiResponse::forbidden('Access denied to this payment');
        }

        $payment->update([
            'status' => 'completed',
            'verified_at' => now(),
            'verified_by' => $this->userId
        ]);

        return ApiResponse::update('Payment verified successfully', new PaymentResource($payment));
    }
}