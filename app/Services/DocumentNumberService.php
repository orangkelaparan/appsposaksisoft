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
                $prefix = match ($type) {
                    'sale' => 'INV',
                    'purchase_order' => 'PO',
                    'purchase_receipt' => 'GRN',
                    'sale_return' => 'RET',
                    default => strtoupper(substr($type, 0, 3)),
                };

                $id = DB::table('document_sequences')->insertGetId([
                    'store_id' => $storeId,
                    'document_type' => $type,
                    'prefix' => $prefix,
                    'next_number' => 2,
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
}
