<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RegisterService
{
    public function __construct(private readonly AuditService $audit) {}

    public function open(int $registerId, float $openingBalance): int
    {
        $existing = DB::table('register_sessions')->where('register_id', $registerId)->where('user_id', session('user_id'))->where('status', 'open')->exists();
        if ($existing) {
            throw ValidationException::withMessages(['register' => 'You already have an open register session.']);
        }

        $id = DB::table('register_sessions')->insertGetId([
            'register_id' => $registerId,
            'user_id' => session('user_id'),
            'opening_balance' => $openingBalance,
            'expected_cash' => $openingBalance,
            'opened_at' => now(),
            'status' => 'open',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->audit->record('opened', 'cash_register', 'register_session', $id, null, ['opening_balance' => $openingBalance]);

        return $id;
    }

    public function close(int $sessionId, float $actualCash): void
    {
        DB::transaction(function () use ($sessionId, $actualCash): void {
            $session = DB::table('register_sessions')->where('id', $sessionId)->where('user_id', session('user_id'))->lockForUpdate()->first();
            if (! $session || $session->status !== 'open') {
                throw ValidationException::withMessages(['register' => 'No open register session was found.']);
            }
            $cashSales = (float) DB::table('payments')->join('sales', 'sales.id', '=', 'payments.sale_id')->where('sales.register_session_id', $sessionId)->where('payments.method', 'cash')->sum('payments.amount');
            $expected = (float) $session->opening_balance + $cashSales;
            DB::table('register_sessions')->where('id', $sessionId)->update([
                'expected_cash' => $expected,
                'actual_cash' => $actualCash,
                'variance' => $actualCash - $expected,
                'closed_at' => now(),
                'status' => 'closed',
                'updated_at' => now(),
            ]);
            $this->audit->record('closed', 'cash_register', 'register_session', $sessionId, null, ['expected_cash' => $expected, 'actual_cash' => $actualCash]);
        });
    }
}
