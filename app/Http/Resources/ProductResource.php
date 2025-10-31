<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
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
            'sku' => $this->sku,
            'pricing' => [
                'current_wac' => (string) $this->current_wac,
                'current_wac_formatted' => '$' . number_format($this->current_wac, 2),
            ],
            'inventory' => [
                'current_quantity' => (string) $this->current_quantity,
                'current_quantity_formatted' => number_format($this->current_quantity, 2),
            ],
            'total_cost' => (string) $this->total_cost,
            'total_cost_formatted' => number_format($this->total_cost, 2),
        ];
    }
}
