<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\InvoiceStoreRequest;
use App\Http\Requests\InvoiceUpdateRequest;
use App\Http\Resources\InvoiceResource;
use App\Http\Responses\ApiResponse;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\FreelancerSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller
{
    private int $userId ; 
    public function __construct()
    {
        $this->userId = Auth::id();
    }
    
    public function index(Request $request)
    {
        $invoices = Invoice::whereHas('client', function($q) {
                $q->where('user_id', $this->userId);
            })
            ->with(['client', 'project', 'items'])
            ->when($request->status, fn($q) => $q->byStatus($request->status))
            ->when($request->client_id, fn($q) => $q->where('client_id', $request->client_id))
            ->latest('invoice_date')
            ->paginate($request->per_page ?? 15);

        return ApiResponse::paginated('Invoices retrieved successfully', $invoices, InvoiceResource::class);
    }

    public function store(InvoiceStoreRequest $request)
    {
        return DB::transaction(function () use ($request) {
            $freelancerSetting = FreelancerSetting::where('freelancer_id', $this->userId)->first();
            
            $invoiceData = $request->validated();
            $invoiceData['freelancer_id'] = $this->userId;
            $invoiceData['invoice_number'] = $freelancerSetting->generateInvoiceNumber();
            
            // Calculate totals
            $subtotal = collect($request->items)->sum(fn($item) => $item['quantity'] * $item['unit_price']);
            $taxAmount = $subtotal * (($request->tax_rate ?? 0) / 100);
            $total = $subtotal + $taxAmount;
            
            $invoiceData['subtotal'] = $subtotal;
            $invoiceData['tax_amount'] = $taxAmount;
            $invoiceData['total_amount'] = $total;
            $invoiceData['total_amount_base_currency'] = $total * $request->exchange_rate;
            
            unset($invoiceData['items']);
            $invoice = Invoice::create($invoiceData);

            // Create invoice items
            foreach ($request->items as $index => $itemData) {
                $itemData['invoice_id'] = $invoice->id;
                $itemData['total_price'] = $itemData['quantity'] * $itemData['unit_price'];
                $itemData['sort_order'] = $index + 1;
                InvoiceItem::create($itemData);
            }

            $invoice->load(['client', 'project', 'items']);
            return ApiResponse::store('Invoice created successfully', new InvoiceResource($invoice));
        });
    }

    public function show(Invoice $invoice)
    {
        if ($invoice->freelancer_id !== $this->userId) {
            return ApiResponse::forbidden('Access denied to this invoice');
        }

        $invoice->load(['client', 'project', 'items.service']);
        return ApiResponse::show('Invoice retrieved successfully', new InvoiceResource($invoice));
    }

    public function update(InvoiceUpdateRequest $request, Invoice $invoice)
    {
        if ($invoice->freelancer_id !== $this->userId) {
            return ApiResponse::forbidden('Access denied to this invoice');
        }

        return DB::transaction(function () use ($request, $invoice) {
            $invoice->update($request->validated());

            if ($request->has('items')) {
                // Delete existing items and recreate
                $invoice->items()->delete();
                
                foreach ($request->items as $index => $itemData) {
                    $itemData['invoice_id'] = $invoice->id;
                    $itemData['total_price'] = $itemData['quantity'] * $itemData['unit_price'];
                    $itemData['sort_order'] = $index + 1;
                    InvoiceItem::create($itemData);
                }
                
                // Recalculate totals
                $subtotal = collect($request->items)->sum(fn($item) => $item['quantity'] * $item['unit_price']);
                $taxAmount = $subtotal * (($request->tax_rate ?? $invoice->tax_rate ?? 0) / 100);
                $total = $subtotal + $taxAmount;
                
                $invoice->update([
                    'subtotal' => $subtotal,
                    'tax_amount' => $taxAmount,
                    'total_amount' => $total,
                    'total_amount_base_currency' => $total * ($request->exchange_rate ?? $invoice->exchange_rate),
                ]);
            }

            $invoice->load(['client', 'project', 'items']);
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

        $invoice->update([
            'status' => 'sent',
            'sent_at' => now()
        ]);

        return ApiResponse::update('Invoice marked as sent', new InvoiceResource($invoice));
    }
}