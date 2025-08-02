<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'client_id' => $this->client_id,
            'project_id' => $this->project_id,
            'title' => $this->title,
            'description' => $this->description,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'has_tax' => $this->has_tax,
            'tax_name' => $this->tax_name,
            'tax_rate' => $this->tax_rate,
            'tax_type' => $this->tax_type,
            'frequency' => $this->frequency,
            'start_date' => $this->start_date?->format('Y-m-d'),
            'next_billing_date' => $this->next_billing_date?->format('Y-m-d'),
            'end_date' => $this->end_date?->format('Y-m-d'),
            'status' => $this->status,
            'is_active' => $this->is_active,
            'billing_count' => $this->billing_count,
            'last_billed_at' => $this->last_billed_at?->format('Y-m-d H:i:s'),
            'tags' => $this->tags,
            'notes' => $this->notes,
            
            // Calculated fields
            'base_amount' => $this->getBaseAmount(),
            'tax_amount' => $this->getTaxAmount(),
            'total_amount' => $this->getTotalAmount(),
            
            // Relationships
            'client' => $this->whenLoaded('client', fn() => new ClientResource($this->client)),
            'project' => $this->whenLoaded('project', fn() => new ProjectResource($this->project)),
            'creator' => $this->whenLoaded('creator', fn() => new UserResource($this->creator)),
            
            // Timestamps
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}

// ServiceListResource.php - For listing/index endpoints
class ServiceListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'client_id' => $this->client_id,
            'project_id' => $this->project_id,
            'title' => $this->title,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'frequency' => $this->frequency,
            'status' => $this->status,
            'is_active' => $this->is_active,
            'next_billing_date' => $this->next_billing_date?->format('Y-m-d'),
            'total_amount' => $this->getTotalAmount(),
            
            // Basic relationships
            'client_name' => $this->whenLoaded('client', $this->client?->name),
            'project_name' => $this->whenLoaded('project', $this->project?->name),
            
            'created_at' => $this->created_at?->format('Y-m-d'),
        ];
    }
}