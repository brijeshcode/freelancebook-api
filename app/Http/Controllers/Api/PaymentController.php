<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PaymentStoreRequest;
use App\Http\Requests\PaymentUpdateRequest;
use App\Http\Resources\PaymentResource;
use App\Http\Responses\ApiResponse;
use App\Models\Payment;
use App\Services\CurrencyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    private int $userId;

    public function __construct(private CurrencyService $currencyService)
    {
        $this->userId = Auth::id();
    }

    public function index(Request $request)
    {
        $payments = $this->paymentQuery($request)
            ->paginate($request->per_page ?? 15);

        return ApiResponse::paginated('Payments retrieved successfully', $payments, PaymentResource::class);
    }

    public function store(PaymentStoreRequest $request)
    {
        $data = $request->validated();
        $data['freelancer_id']     = $this->userId;
        $data['transaction_number'] = 'PAY-' . date('Y') . '-' . strtoupper(Str::random(6));
        $data['status']            = $request->status ?? 'completed';

        // Resolve exchange rate snapshot — use request values if provided, otherwise pull from active rate
        $snapshot = $this->currencyService->snapshot(
            $data['currency_id'],
            $this->currencyService->getBaseCurrency($this->userId)
        );

        $data['exchange_rate']    = $request->exchange_rate    ?? $snapshot['exchange_rate'];
        $data['calculation_type'] = $request->calculation_type ?? $snapshot['calculation_type'];

        $data['amount_base_currency'] = $this->currencyService->applyRate(
            (float) $data['amount'],
            (float) $data['exchange_rate'],
            $data['calculation_type']
        );

        $payment = Payment::create($data);
        $payment->load(['client', 'currency']);

        return ApiResponse::store('Payment recorded successfully', new PaymentResource($payment));
    }

    public function show(Payment $payment)
    {
        if ($payment->freelancer_id !== $this->userId) {
            return ApiResponse::forbidden('Access denied to this payment');
        }

        $payment->load(['client', 'currency', 'verifiedBy']);
        return ApiResponse::show('Payment retrieved successfully', new PaymentResource($payment));
    }

    public function update(PaymentUpdateRequest $request, Payment $payment)
    {
        if ($payment->freelancer_id !== $this->userId) {
            return ApiResponse::forbidden('Access denied to this payment');
        }

        $data = $request->validated();

        // Recalculate base currency amount if amount, exchange_rate, or calculation_type changed
        if ($request->hasAny(['amount', 'exchange_rate', 'calculation_type'])) {
            $amount          = (float) ($data['amount']          ?? $payment->amount);
            $exchangeRate    = (float) ($data['exchange_rate']   ?? $payment->exchange_rate);
            $calculationType =          $data['calculation_type'] ?? $payment->calculation_type;

            $data['amount_base_currency'] = $this->currencyService->applyRate(
                $amount,
                $exchangeRate,
                $calculationType
            );
        }

        $payment->update($data);
        $payment->load(['client', 'currency']);

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
            'status'      => 'completed',
            'verified_at' => now(),
            'verified_by' => $this->userId,
        ]);

        return ApiResponse::update('Payment verified successfully', new PaymentResource($payment));
    }

    public function stats(Request $request)
    {
        $payments = $this->paymentQuery($request);

        $totalPayments = $payments->clone()->count();
        $totalReceived = $payments->clone()->where('status', 'completed')->sum('amount_base_currency');
        $pendingAmount = $payments->clone()->where('status', 'pending')->sum('amount_base_currency');
        $thisMonth     = $payments->clone()
            ->where('status', 'completed')
            ->whereMonth('payment_date', now()->month)
            ->whereYear('payment_date', now()->year)
            ->sum('amount_base_currency');

        return ApiResponse::show('Payment statistics retrieved successfully', [
            'total_payments' => $totalPayments,
            'total_received' => round($totalReceived, 2),
            'pending_amount' => round($pendingAmount, 2),
            'this_month'     => round($thisMonth, 2),
        ]);
    }

    public function paymentQuery(Request $request)
    {
        return Payment::query()->whereHas('client', function ($q) {
                $q->where('user_id', $this->userId);
            })
            ->with(['client', 'currency'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->client_id, fn($q) => $q->where('client_id', $request->client_id))
            ->when($request->payment_method, fn($q) => $q->byPaymentMethod($request->payment_method))
            ->latest('payment_date');
    }

    public function uploadReceipts(Request $request)
    {
        $request->validate([
            'files.*' => 'required|file|mimes:jpeg,jpg,png,gif,pdf|max:10240',
        ]);

        $filePaths = [];

        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $filePaths[] = $file->store('payments/receipts', 'public');
            }
        }

        return ApiResponse::show('Files uploaded successfully', ['file_paths' => $filePaths]);
    }

    public function viewFile(Request $request)
    {
        $path = $request->get('path');

        if (!Storage::exists($path)) {
            return response()->json(['error' => 'File not found'], 404);
        }

        return Storage::response($path);
    }

    public function downloadFile(Request $request)
    {
        $path = $request->get('path');

        if (!Storage::exists($path)) {
            return response()->json(['error' => 'File not found'], 404);
        }

        return Storage::download($path);
    }
}
