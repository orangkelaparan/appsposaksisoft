<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class DocumentNumberService
{
    public function next(string $type, ?int $storeId = null): string
    {
        return DB::transaction(function () use ($type, $storeId): string {
            $sequence = DB::table('document_sequences')
                ->where('document_type', $type)
                ->where('store_id', $storeId)
                ->lockForUpdate()
                ->first();

            if (! $sequence) {
                [$prefix, $table, $column] = $this->definitionFor($type);
                $number = $this->firstAvailableNumber($table, $column, $prefix, $storeId);
                $id = DB::table('document_sequences')->insertGetId([
                    'store_id' => $storeId,
                    'document_type' => $type,
                    'prefix' => $prefix,
                    'next_number' => $number + 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $sequence = DB::table('document_sequences')->find($id);
            } else {
                DB::table('document_sequences')->where('id', $sequence->id)->update([
                    'next_number' => $sequence->next_number + 1,
                    'updated_at' => now(),
                ]);
            }

            return sprintf('%s-%s-%06d', $sequence->prefix, now()->format('Y'), $sequence->next_number - 1);
        });
    }

    /** @return array{string, string, string} */
    private function definitionFor(string $type): array
    {
        return match ($type) {
            'sale' => ['INV', 'sales', 'invoice_number'],
            'purchase_order' => ['PO', 'purchase_orders', 'po_number'],
            'purchase_receipt' => ['GRN', 'purchase_receipts', 'receipt_number'],
            'sale_return' => ['RET', 'sale_returns', 'return_number'],
            'stock_transfer' => ['TRF', 'stock_transfers', 'transfer_number'],
            'stock_count' => ['CNT', 'stock_counts', 'count_number'],
            'quotation' => ['QTN', 'quotations', 'quote_number'],
            'sales_order' => ['SO', 'sales_orders', 'order_number'],
            'expense' => ['EXP', 'expenses', 'expense_number'],
            default => [strtoupper(substr($type, 0, 3)), '', ''],
        };
    }

    private function firstAvailableNumber(string $table, string $column, string $prefix, ?int $storeId): int
    {
        if ($table === '' || $column === '') {
            return 1;
        }

        $yearPrefix = $prefix.'-'.now()->format('Y').'-';
        $query = DB::table($table)->where($column, 'like', $yearPrefix.'%');
        if ($storeId !== null && DB::getSchemaBuilder()->hasColumn($table, 'store_id')) {
            $query->where('store_id', $storeId);
        }

        $lastNumber = (int) $query->selectRaw("COALESCE(MAX(CAST(SUBSTR({$column}, 10) AS INTEGER)), 0) AS last_number")->value('last_number');

        return $lastNumber + 1;
    }
}
