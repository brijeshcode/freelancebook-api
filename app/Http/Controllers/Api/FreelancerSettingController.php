<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\FreelancerSettingUpdateRequest;
use App\Http\Resources\FreelancerSettingResource;
use App\Http\Responses\ApiResponse;
use App\Models\FreelancerSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class FreelancerSettingController extends Controller
{
    /**
     * Display the freelancer's settings
     */
    public function show(): JsonResponse
    {
        $freelancerId = Auth::id();
        
        $setting = FreelancerSetting::firstOrCreate(
            ['freelancer_id' => $freelancerId],
            [
                'base_currency' => 'USD',
                'invoice_due_days' => 30,
                'invoice_prefix' => 'INV',
                'next_invoice_number' => 1,
                'invoice_year' => date('Y'),
                'default_tax_rate' => 0.00,
                'notification_preferences' => [
                    'invoice_reminders' => true,
                    'payment_notifications' => true,
                    'renewal_alerts' => true,
                    'email_notifications' => true
                ]
            ]
        );

        return ApiResponse::show(
            'Settings retrieved successfully',
            new FreelancerSettingResource($setting)
        );
    }

    /**
     * Update the freelancer's settings
     */
    public function update(FreelancerSettingUpdateRequest $request): JsonResponse
    {
        $freelancerId = Auth::id();
        
        $setting = FreelancerSetting::where('freelancer_id', $freelancerId)->first();
        
        if (!$setting) {
            return ApiResponse::notFound('Settings not found');
        }

        $data = $request->validated();
        
         

        $setting->update($data);

        return ApiResponse::update(
            'Settings updated successfully',
            new FreelancerSettingResource($setting->fresh())
        );
    }

    /**
     * Reset invoice numbering (useful for new year)
     */
    public function resetInvoiceNumbering(): JsonResponse
    {
        $freelancerId = Auth::id();
        
        $setting = FreelancerSetting::where('freelancer_id', $freelancerId)->first();
        
        if (!$setting) {
            return ApiResponse::notFound('Settings not found');
        }

        $setting->update([
            'next_invoice_number' => 1,
            'invoice_year' => date('Y')
        ]);

        return ApiResponse::successMessage('Invoice numbering reset successfully');
    }
}