<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\ProductTransaction;

class ProductTransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $transactionTypes = ProductTransaction::transactionTypes();
        
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'product_name' => $this->product_name,
            'product_sku' => $this->product_sku,
            'transaction_type' => $this->transaction_type,
            'transaction_type_label' => $transactionTypes[$this->transaction_type] ?? 'Unknown',
            'transaction_date' => $this->transaction_date->format('Y-m-d'),
            'transaction_date_formatted' => $this->transaction_date->format('M d, Y'),
            'quantity' => (string) $this->quantity,
            'quantity_formatted' => number_format($this->quantity, 2),
            'unit_cost' => (string) $this->unit_cost,
            'unit_cost_formatted' => number_format($this->unit_cost, 2),
            'total_cost' => (string) $this->total_cost,
            'total_cost_formatted' => number_format($this->total_cost, 2),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}