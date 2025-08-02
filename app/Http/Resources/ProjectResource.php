<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'budget' => $this->budget,
            'budget_currency' => $this->budget_currency,
            'notes' => $this->notes,
            'project_details' => $this->project_details,
            'start_date' => $this->start_date?->format('Y-m-d'),
            'end_date' => $this->end_date?->format('Y-m-d'),
            'deadline' => $this->deadline?->format('Y-m-d'),
            'estimated_hours' => $this->estimated_hours,
            'actual_hours' => $this->actual_hours,
            'total_paid' => $this->total_paid,
            'payment_currency' => $this->payment_currency,
            'status' => $this->status,

            // Computed fields
            'budget_exceeded' => $this->budget_exceeded,
            'remaining_budget' => $this->remaining_budget,
            'time_variance' => $this->time_variance,

            // Relationships
            'client' => new ClientResource($this->whenLoaded('client')),
            'freelancer' => $this->whenLoaded('freelancer', function () {
                return [
                    'id' => $this->freelancer->id,
                    'name' => $this->freelancer->name,
                ];
            }),

            // Timestamps
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
