<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use App\Models\Product;
use App\Models\ProductTransaction;
use App\Http\Requests\ProductTransactionCreateRequest;
use App\Http\Requests\ProductTransactionEditRequest;
use App\Http\Resources\ProductTransactionResource;

class ProductTransactionController extends Controller
{
    //
    public function index($id, Request $request)
    {
        $perPage = $request->get('per_page', 15); // Default 15 items per page
        $query = ProductTransaction::query();

        if (!empty($request->transaction_type)) {
            $query->where('transaction_type', $request->transaction_type);
        }
        $transactions = $query->where('product_id', $id)->latest('transaction_date')->paginate($perPage);

        return response()->json([
            'message' => 'Product transactions retrieved successfully',
            'transactions' => ProductTransactionResource::collection($transactions),
            'pagination' => [
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'per_page' => $transactions->perPage(),
                'total' => $transactions->total(),
                'from' => $transactions->firstItem(),
                'to' => $transactions->lastItem(),
                'has_more_pages' => $transactions->hasMorePages(),
                'prev_page_url' => $transactions->previousPageUrl(),
                'next_page_url' => $transactions->nextPageUrl(),
            ]
        ]);
    }

    public function store($id, ProductTransactionCreateRequest $request)
    {
        $product = Product::find($id);
        if (!$product) {
            return response()->json([
                'message' => 'Product not found',
            ], Response::HTTP_NOT_FOUND);
        }

        $previousTransaction = ProductTransaction::where('product_id', $id)
            ->where('transaction_date', '<', $request->transaction_date)
            ->latest('transaction_date')
            ->first();
        
        //first transaction must always be purchase
        if ($previousTransaction == null && $request->transaction_type != 1) {
            return response()->json([
                'message' => 'The first transaction for a product must be a purchase.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        //if the transaction is sale, check if there is sufficient quantity
        if ($request->transaction_type == 2 && $previousTransaction->quantity_after < $request->quantity) {
            return response()->json([
                'message' => 'Insufficient quantity for sale transaction. Available quantity: ' . $previousTransaction->quantity_after,
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        //check latest transaction to get latest date
        $latestTransaction = ProductTransaction::where('product_id', $id)->latest('transaction_date')->first();
        //transaction date must not be more than 30 days earlier than latest transaction date
        if ($latestTransaction && $request->transaction_date < $latestTransaction->transaction_date->subDays(30)) {
            return response()->json([
                'message' => 'Transaction date cannot be more than 30 days earlier than the latest transaction date: ' . $latestTransaction->transaction_date->format('Y-m-d'),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            DB::beginTransaction();

            $currentWac = 0;
            $currentQuantity = 0;
            $currentTotalCost = 0;
            if (!empty($previousTransaction)) {
                $currentWac = $previousTransaction->wac_after;
                $currentQuantity = $previousTransaction->quantity_after;
                $currentTotalCost = $currentWac * $currentQuantity;
            }
                
            if ($request->transaction_type == 1) {
                //transaction type is purchase
                $newTotalQuantity = $currentQuantity + $request->quantity;
                $newTotalCost = $currentTotalCost + ($request->quantity * $request->cost_per_unit);
                $newWac = $newTotalQuantity > 0 ? $newTotalCost / $newTotalQuantity : 0;

                $transactionQuantity = $request->quantity;
                $transactionUnitCost = $request->cost_per_unit;
                $transactionTotalCost = $transactionQuantity * $transactionUnitCost;

            } else if ($request->transaction_type == 2) {
                //transaction type is sale
                $newTotalQuantity = $currentQuantity - $request->quantity;
                $newTotalCost = $currentTotalCost - ($request->quantity * $currentWac);
                $newWac = $newTotalQuantity > 0 ? $currentWac : 0; // WAC remains unchanged on sale. If no more quantity, newWac will be set to 0

                $transactionQuantity = -1 * $request->quantity;
                $transactionUnitCost = $currentWac;
                $transactionTotalCost = $transactionQuantity * $transactionUnitCost;

            }
            
            $product->update([
                'current_quantity' => $newTotalQuantity,
                'total_cost' => $newTotalCost,
                'current_wac' => $newWac,
            ]);

            $transaction = ProductTransaction::create([
                'product_id' => $id,
                'transaction_type' => $request->transaction_type,
                'quantity' => $transactionQuantity,
                'unit_cost' => $transactionUnitCost,
                'total_cost' => $transactionTotalCost,
                'transaction_date' => $request->transaction_date,
                'wac_before' => $currentWac,
                'wac_after' => $newWac,
                'quantity_before' => $currentQuantity,
                'quantity_after' => $newTotalQuantity,
            ]);

            //update subsequent transactions
            $this->updateSubsequentTransactions($id, $request->transaction_date, $newWac, $newTotalQuantity, $product);

            DB::commit();

            return response()->json([
                'message' => 'Product transaction created successfully',
                'transaction' => new ProductTransactionResource($transaction),
            ], Response::HTTP_CREATED);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'message' => 'Failed to create product transaction',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update($id, $transactionId, ProductTransactionEditRequest $request)
    {
        $product = Product::find($id);
        if (!$product) {
            return response()->json([
                'message' => 'Product not found',
            ], Response::HTTP_NOT_FOUND);
        }

        $transaction = ProductTransaction::where('product_id', $id)->find($transactionId);
        if (!$transaction) {
            return response()->json([
                'message' => 'Transaction not found',
            ], Response::HTTP_NOT_FOUND);
        }

        // Get the transaction before this one to check
        $previousTransaction = ProductTransaction::where('product_id', $id)
            ->where('transaction_date', '<', $transaction->transaction_date)
            ->latest('transaction_date')
            ->first();

        // If updating a sale transaction, check if there's sufficient quantity with new quantity
        if ($transaction->transaction_type == 2) {
            $availableQuantity = $previousTransaction ? $previousTransaction->quantity_after : 0;
            if ($availableQuantity < $request->quantity) {
                return response()->json([
                    'message' => 'Insufficient quantity for sale transaction. Available quantity: ' . $availableQuantity,
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
        }

        try {
            DB::beginTransaction();

            // Calculate from the previous transaction state
            $currentWac = 0;
            $currentQuantity = 0;
            $currentTotalCost = 0;
            if (!empty($previousTransaction)) {
                $currentWac = $previousTransaction->wac_after;
                $currentQuantity = $previousTransaction->quantity_after;
                $currentTotalCost = $currentWac * $currentQuantity;
            }

            // Calculate new values based on transaction type
            if ($transaction->transaction_type == 1) {
                // Transaction type is purchase
                $newTotalQuantity = $currentQuantity + $request->quantity;
                $newTotalCost = $currentTotalCost + ($request->quantity * $request->cost_per_unit);
                $newWac = $newTotalQuantity > 0 ? $newTotalCost / $newTotalQuantity : 0;

                $transactionQuantity = $request->quantity;
                $transactionUnitCost = $request->cost_per_unit;
                $transactionTotalCost = $transactionQuantity * $transactionUnitCost;

            } else if ($transaction->transaction_type == 2) {
                // Transaction type is sale
                $newTotalQuantity = $currentQuantity - $request->quantity;
                $newTotalCost = $currentTotalCost - ($request->quantity * $currentWac);
                $newWac = $newTotalQuantity > 0 ? $currentWac : 0; // WAC remains unchanged on sale

                $transactionQuantity = -1 * $request->quantity;
                $transactionUnitCost = $currentWac;
                $transactionTotalCost = $transactionQuantity * $transactionUnitCost;
            }

            // Update the transaction
            $transaction->update([
                'quantity' => $transactionQuantity,
                'unit_cost' => $transactionUnitCost,
                'total_cost' => $transactionTotalCost,
                'wac_before' => $currentWac,
                'wac_after' => $newWac,
                'quantity_before' => $currentQuantity,
                'quantity_after' => $newTotalQuantity,
            ]);

            // Update subsequent transactions starting from this transaction's date
            $this->updateSubsequentTransactions($id, $transaction->transaction_date, $newWac, $newTotalQuantity, $product);

            DB::commit();

            return response()->json([
                'message' => 'Product transaction updated successfully',
                'transaction' => new ProductTransactionResource($transaction),
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'message' => 'Failed to update product transaction',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy($id, $transactionId)
    {
        $transaction = ProductTransaction::where('product_id', $id)->find($transactionId);

        if (!$transaction) {
            return response()->json([
                'message' => 'Transaction not found',
            ], Response::HTTP_NOT_FOUND);
        }

        //cannot delete transaction if it's more than 30 days from latest transaction date
        $latestTransaction = ProductTransaction::where('product_id', $id)->latest('transaction_date')->first();
        if ($latestTransaction && $transaction->transaction_date < $latestTransaction->transaction_date->subDays(30)) {
            return response()->json([
                'message' => 'Transaction cannot be deleted as it is more than 30 days older than the latest transaction date: ' . $latestTransaction->transaction_date->format('Y-m-d'),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        //check if this deletion will cause subsequent sale transactions to have insufficient quantity
        $nextTransaction = ProductTransaction::where('product_id', $id)
            ->where('transaction_date', '>', $transaction->transaction_date)
            ->orderBy('transaction_date', 'asc')
            ->first();
        if ($nextTransaction && $nextTransaction->transaction_type == 2 && $nextTransaction->quantity > $transaction->quantity_after) {
            return response()->json([
                'message' => 'Cannot delete this transaction as it would result in insufficient quantity for a subsequent sale transaction dated ' . $nextTransaction->transaction_date->format('Y-m-d'),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        
        DB::beginTransaction();

        try {
            $previousTransaction = ProductTransaction::where('product_id', $id)
                ->where('transaction_date', '<', $transaction->transaction_date)
                ->latest('transaction_date')
                ->first();
            
            // Recalculate product state after deletion
            $currentWac = 0;
            $currentQuantity = 0;
            if (!empty($previousTransaction)) {
                $currentWac = $previousTransaction->wac_after;
                $currentQuantity = $previousTransaction->quantity_after;
            }

            $transactionDate = $previousTransaction->transaction_date ?? $transaction->transaction_date;
            $transaction->delete();

            // Update product
            $product = Product::find($id);
            $product->update([
                'current_quantity' => $currentQuantity,
                'total_cost' => $currentWac * $currentQuantity,
                'current_wac' => $currentWac,
            ]);

            // Update subsequent transactions
            $this->updateSubsequentTransactions($id, $transactionDate, $currentWac, $currentQuantity, $product);

            DB::commit();

            return response()->json([
                'message' => 'Product transaction deleted successfully',
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to delete product transaction',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update subsequent transactions after a new transaction is added or modified
     *
     * @param int $productId
     * @param string $transactionDate
     * @param float $initialWac
     * @param float $initialQuantity
     * @param Product $product
     * @return void
     */
    private function updateSubsequentTransactions($productId, $transactionDate, $initialWac, $initialQuantity, $product)
    {
        $newWac = $initialWac;
        $newTotalQuantity = $initialQuantity;

        //check if there is transaction after the current transaction date to update their quantity_before and quantity_after
        $subsequentTransactions = ProductTransaction::where('product_id', $productId)
            ->where('transaction_date', '>', $transactionDate)
            ->orderBy('transaction_date', 'asc')
            ->get();
            
        foreach ($subsequentTransactions as $subsequentTransaction) {
            $subsequentTransaction->wac_before = $newWac; //1st loop will from current transaction. Next loop will be from previous loop
            $subsequentTransaction->quantity_before = $newTotalQuantity; //1st loop will from current transaction. Next loop will be from previous loop
            $subsequentTransaction->quantity_after = $newTotalQuantity + $subsequentTransaction->quantity;

            // Check if the new quantity_after becomes negative
            if ($subsequentTransaction->quantity_after < 0) {
                throw new \Exception('Transaction update would result in negative inventory. Insufficient quantity for sale transaction on ' . $subsequentTransaction->transaction_date->format('Y-m-d') . '. Available: ' . $newTotalQuantity . ', Required: ' . abs($subsequentTransaction->quantity));
            }

            if ($subsequentTransaction->transaction_type == 1) {
                //if transaction is purchase
                $subsequentTransaction->wac_after = (($newTotalQuantity * $newWac) + $subsequentTransaction->total_cost) /  $subsequentTransaction->quantity_after;    
            } else if ($subsequentTransaction->transaction_type == 2) {
                //if transaction is sale
                $subsequentTransaction->unit_cost = $newWac;
                $subsequentTransaction->wac_after = $newWac;
                $subsequentTransaction->total_cost = $newWac * $subsequentTransaction->quantity;
            }

            $subsequentTransaction->save();
            $subsequentTransaction->refresh();

            $newWac = $subsequentTransaction->wac_after;
            $newTotalQuantity = $subsequentTransaction->quantity_after;

            $product->update([
                'current_quantity' => $newTotalQuantity,
                'total_cost' => $newWac * $newTotalQuantity,
                'current_wac' => $newWac,
            ]);
        }
    }
}
