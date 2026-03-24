<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\InvoiceStoreRequest;
use App\Http\Requests\InvoiceUpdateRequest;
use App\Http\Resources\InvoiceResource;
use App\Http\Responses\ApiResponse;
use App\Models\FreelancerSetting;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\Service;
use App\Services\CurrencyService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller
{
    private int $userId;

    public function __construct(private CurrencyService $currencyService)
    {
        $this->userId = Auth::id();
    }

    public function index(Request $request)
    {
        $invoices = Invoice::whereHas('client', function ($q) {
                $q->where('user_id', $this->userId);
            })
            ->with(['client', 'project', 'currency', 'items'])
            ->when($request->status, fn($q) => $q->byStatus($request->status))
            ->when($request->client_id, fn($q) => $q->where('client_id', $request->client_id))
            ->when($request->billing_month, fn($q) => $q->byBillingMonth($request->billing_month))
            ->latest()
            ->paginate($request->per_page ?? 15);

        return ApiResponse::paginated('Invoices retrieved successfully', $invoices, InvoiceResource::class);
    }

    public function store(InvoiceStoreRequest $request)
    {
        return DB::transaction(function () use ($request) {
            $data = $request->validated();
            $data['freelancer_id']  = $this->userId;
            $data['invoice_number'] = FreelancerSetting::where('freelancer_id', $this->userId)
                ->firstOrFail()
                ->generateInvoiceNumber();

            // Resolve exchange rate snapshot — use request values if provided, otherwise pull from active rate
            $baseCurrency = $this->currencyService->getBaseCurrency($this->userId);
            $snapshot     = $this->currencyService->snapshot($data['currency_id'], $baseCurrency);

            $exchangeRate    = $request->exchange_rate    ?? $snapshot['exchange_rate'];
            $calculationType = $request->calculation_type ?? $snapshot['calculation_type'];

            $data['exchange_rate']    = $exchangeRate;
            $data['calculation_type'] = $calculationType;

            // Auto-derive billing_month from invoice_date if not explicitly provided
            $data['billing_month'] = isset($data['billing_month'])
                ? Carbon::parse($data['billing_month'])->startOfMonth()->toDateString()
                : Carbon::parse($data['invoice_date'])->startOfMonth()->toDateString();

            // Calculate invoice totals in original currency
            $subtotal  = collect($request->items)->sum(fn($item) => $item['quantity'] * $item['unit_price']);
            $taxAmount = $subtotal * (($request->tax_rate ?? 0) / 100);
            $total     = $subtotal + $taxAmount;

            $data['subtotal']                  = $subtotal;
            $data['tax_amount']                = $taxAmount;
            $data['total_amount']              = $total;

            // Base currency equivalents using correct multiply/divide logic
            $data['subtotal_base_currency']    = $this->currencyService->applyRate($subtotal, $exchangeRate, $calculationType);
            $data['tax_amount_base_currency']  = $this->currencyService->applyRate($taxAmount, $exchangeRate, $calculationType);
            $data['total_amount_base_currency'] = $this->currencyService->applyRate($total, $exchangeRate, $calculationType);

            unset($data['items']);
            $invoice = Invoice::create($data);

            // Create invoice items with base currency prices
            foreach ($request->items as $index => $itemData) {
                $itemData['invoice_id']  = $invoice->id;
                $itemData['total_price'] = $itemData['quantity'] * $itemData['unit_price'];
                $itemData['sort_order']  = $index + 1;
                $itemData['item_type']   = $itemData['item_type'] ?? ($itemData['service_id'] ? 'service' : 'custom');

                // Auto-fill title from linked service if not provided
                if (empty($itemData['title']) && !empty($itemData['service_id'])) {
                    $itemData['title'] = Service::find($itemData['service_id'])?->title;
                }

                $itemData['unit_price_base_currency']  = $this->currencyService->applyRate((float) $itemData['unit_price'], $exchangeRate, $calculationType);
                $itemData['total_price_base_currency'] = $this->currencyService->applyRate((float) $itemData['total_price'], $exchangeRate, $calculationType);

                InvoiceItem::create($itemData);
            }

            $invoice->load(['client', 'project', 'currency', 'items']);
            return ApiResponse::store('Invoice created successfully', new InvoiceResource($invoice));
        });
    }

    public function show(Invoice $invoice)
    {
        if ($invoice->freelancer_id !== $this->userId) {
            return ApiResponse::forbidden('Access denied to this invoice');
        }

        $invoice->load(['client', 'project', 'currency', 'items.service']);
        return ApiResponse::show('Invoice retrieved successfully', new InvoiceResource($invoice));
    }

    public function update(InvoiceUpdateRequest $request, Invoice $invoice)
    {
        if ($invoice->freelancer_id !== $this->userId) {
            return ApiResponse::forbidden('Access denied to this invoice');
        }

        return DB::transaction(function () use ($request, $invoice) {
            $data = $request->validated();

            // Resolve current rate values (request overrides existing snapshot)
            $exchangeRate    = $request->exchange_rate    ?? $invoice->exchange_rate;
            $calculationType = $request->calculation_type ?? $invoice->calculation_type;

            // If currency changed, fetch a fresh snapshot (unless rate also explicitly provided)
            if ($request->has('currency_id') && $request->currency_id !== $invoice->currency_id) {
                $baseCurrency = $this->currencyService->getBaseCurrency($this->userId);
                $snapshot     = $this->currencyService->snapshot($request->currency_id, $baseCurrency);
                $exchangeRate    = $request->exchange_rate    ?? $snapshot['exchange_rate'];
                $calculationType = $request->calculation_type ?? $snapshot['calculation_type'];
                $data['exchange_rate']    = $exchangeRate;
                $data['calculation_type'] = $calculationType;
            }

            // Sync billing_month when invoice_date changes (unless explicitly overridden)
            if ($request->has('invoice_date') && !$request->has('billing_month')) {
                $data['billing_month'] = Carbon::parse($data['invoice_date'])->startOfMonth()->toDateString();
            } elseif ($request->has('billing_month')) {
                $data['billing_month'] = Carbon::parse($data['billing_month'])->startOfMonth()->toDateString();
            }

            $invoice->update($data);

            if ($request->has('items')) {
                $invoice->items()->delete();

                foreach ($request->items as $index => $itemData) {
                    $itemData['invoice_id']  = $invoice->id;
                    $itemData['total_price'] = $itemData['quantity'] * $itemData['unit_price'];
                    $itemData['sort_order']  = $index + 1;
                    $itemData['item_type']   = $itemData['item_type'] ?? ($itemData['service_id'] ? 'service' : 'custom');

                    // Auto-fill title from linked service if not provided
                    if (empty($itemData['title']) && !empty($itemData['service_id'])) {
                        $itemData['title'] = Service::find($itemData['service_id'])?->title;
                    }

                    $itemData['unit_price_base_currency']  = $this->currencyService->applyRate((float) $itemData['unit_price'], $exchangeRate, $calculationType);
                    $itemData['total_price_base_currency'] = $this->currencyService->applyRate((float) $itemData['total_price'], $exchangeRate, $calculationType);

                    InvoiceItem::create($itemData);
                }

                // Recalculate invoice totals
                $subtotal  = collect($request->items)->sum(fn($item) => $item['quantity'] * $item['unit_price']);
                $taxAmount = $subtotal * (($request->tax_rate ?? $invoice->tax_rate ?? 0) / 100);
                $total     = $subtotal + $taxAmount;

                $invoice->update([
                    'subtotal'                   => $subtotal,
                    'tax_amount'                 => $taxAmount,
                    'total_amount'               => $total,
                    'subtotal_base_currency'     => $this->currencyService->applyRate($subtotal, $exchangeRate, $calculationType),
                    'tax_amount_base_currency'   => $this->currencyService->applyRate($taxAmount, $exchangeRate, $calculationType),
                    'total_amount_base_currency' => $this->currencyService->applyRate($total, $exchangeRate, $calculationType),
                ]);
            }

            $invoice->load(['client', 'project', 'currency', 'items']);
            return ApiResponse::update('Invoice updated successfully', new InvoiceResource($invoice));
        });
    }

    public function destroy(Invoice $invoice)
    {
        if ($invoice->freelancer_id !== $this->userId) {
            return ApiResponse::forbidden('Access denied to this invoice');
        }

        $invoice->delete();
        return ApiResponse::delete('Invoice deleted successfully');
    }

    public function markAsSent(Invoice $invoice)
    {
        if ($invoice->freelancer_id !== $this->userId) {
            return ApiResponse::forbidden('Access denied to this invoice');
        }

        $invoice->update(['status' => 'sent', 'sent_at' => now()]);
        return ApiResponse::update('Invoice marked as sent', new InvoiceResource($invoice));
    }

    public function stats()
    {
        $invoices = Invoice::whereHas('client', function ($q) {
            $q->where('user_id', $this->userId);
        });

        $totalInvoices    = $invoices->clone()->count();
        $totalBilled      = $invoices->clone()->sum('total_amount_base_currency');
        $overdueInvoices  = $invoices->clone()
            ->where('due_date', '<', now())
            ->whereNotIn('status', ['paid', 'cancelled'])
            ->count();

        $totalPayments = Payment::whereHas('client', function ($q) {
            $q->where('user_id', $this->userId);
        })->where('status', 'completed')->sum('amount_base_currency');

        return ApiResponse::index('Invoice statistics retrieved successfully', [
            'total_invoices'      => $totalInvoices,
            'total_billed'        => round($totalBilled, 2),
            'outstanding_balance' => round($totalBilled - $totalPayments, 2),
            'overdue_invoices'    => $overdueInvoices,
        ]);
    }
}
