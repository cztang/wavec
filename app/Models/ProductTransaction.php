<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductTransaction extends Model
{
    use HasFactory;

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::creating(function (ProductTransaction $transaction) {
            if ($transaction->product_id) {
                $product = Product::find($transaction->product_id);
                if ($product) {
                    $transaction->product_name = $product->name;
                    $transaction->product_sku = $product->sku;
                }
            }
        });
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'product_id',
        'product_name',
        'product_sku',
        'transaction_type',
        'transaction_date',
        'quantity',
        'unit_cost',
        'total_cost',
        'wac_before',
        'wac_after',
        'quantity_before',
        'quantity_after',
    ];
    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [        
        'transaction_date' => 'date',
        'quantity' => 'decimal:8',
        'unit_cost' => 'decimal:4',
        'total_cost' => 'decimal:4',
        'wac_before' => 'decimal:4',
        'wac_after' => 'decimal:4',
        'quantity_before' => 'decimal:8',
        'quantity_after' => 'decimal:8',
    ];

    /**
     * Transaction type list
     */
    public static function transactionTypes(): array
    {
        return [
            1 => 'purchase',
            2 => 'sale',
        ];
    }

    /**
     * Get the product that owns the transaction.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
