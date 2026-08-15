<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ExpenseService
{
    public function __construct(
        private readonly DocumentNumberService $numbers,
        private readonly AuditService $audit,
    ) {}

    public function create(array $data): int
    {
        return DB::transaction(function () use ($data): int {
            $store = DB::table('stores')->where('id', $data['store_id'])->lockForUpdate()->first();
            if (! $store) {
                throw ValidationException::withMessages(['store_id' => 'The selected outlet is unavailable.']);
            }
            $registerSessionId = $data['register_session_id'] ?? null;
            if ($registerSessionId) {
                $session = DB::table('register_sessions')->where('id', $registerSessionId)->lockForUpdate()->first();
                if (! $session || $session->status !== 'open') {
                    throw ValidationException::withMessages(['register_session_id' => 'The selected register session is not open.']);
                }
            }
            $expenseId = DB::table('expenses')->insertGetId([
                'store_id' => $store->id,
                'register_session_id' => $registerSessionId,
                'created_by' => session('user_id'),
                'expense_number' => $this->numbers->next('expense', (int) $store->id),
                'category' => $data['category'],
                'amount' => $data['amount'],
                'payment_method' => $data['payment_method'],
                'expense_date' => $data['expense_date'] ?? now()->toDateString(),
                'description' => $data['description'] ?? null,
                'status' => 'approved',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            if ($registerSessionId && $data['payment_method'] === 'cash') {
                DB::table('cash_movements')->insert([
                    'register_session_id' => $registerSessionId,
                    'user_id' => session('user_id'),
                    'type' => 'expense',
                    'amount' => $data['amount'],
                    'reason' => 'Expense '.$data['category'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            $this->audit->record('created', 'expenses', 'expense', $expenseId, null, ['amount' => $data['amount'], 'category' => $data['category']]);

            return $expenseId;
        });
    }
}
