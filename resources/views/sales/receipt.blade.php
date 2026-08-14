<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $sale->invoice_number }} · Receipt</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-100 p-6">
    <main class="receipt mx-auto max-w-md bg-white p-6 shadow-sm">
        <header class="text-center">
            <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-lg bg-indigo-600 text-lg font-black text-white">A</div>
            <h1 class="mt-2 text-lg font-black text-slate-900">{{ $sale->company_name }}</h1>
            <p class="text-xs text-slate-500">{{ $sale->store_name }}</p>
            <p class="text-xs text-slate-500">{{ $sale->store_address }}</p>
            <p class="text-xs text-slate-500">{{ $sale->company_phone }}</p>
        </header>

        <section class="my-4 border-y border-dashed border-slate-300 py-3 text-xs text-slate-600">
            <div class="flex justify-between"><span>Invoice</span><strong>{{ $sale->invoice_number }}</strong></div>
            <div class="mt-1 flex justify-between"><span>Cashier</span><span>{{ $sale->cashier_name }}</span></div>
            <div class="mt-1 flex justify-between"><span>Date</span><span>{{ \Carbon\Carbon::parse($sale->sold_at)->format('d M Y H:i') }}</span></div>
        </section>

        <section class="space-y-3">
            @foreach ($items as $item)
                <div>
                    <div class="flex justify-between gap-4 text-sm font-semibold text-slate-800"><span>{{ $item->product_name }}</span><span>Rp{{ number_format($item->line_total, 0, ',', '.') }}</span></div>
                    <div class="flex justify-between text-xs text-slate-500"><span>{{ number_format($item->quantity, 3, ',', '.') }} × Rp{{ number_format($item->unit_price, 0, ',', '.') }}</span><span>{{ $item->sku }}</span></div>
                </div>
            @endforeach
        </section>

        <section class="my-4 border-t border-dashed border-slate-300 pt-3 text-sm">
            <div class="flex justify-between"><span>Subtotal</span><span>Rp{{ number_format($sale->subtotal, 0, ',', '.') }}</span></div>
            <div class="mt-1 flex justify-between"><span>Discount</span><span>-Rp{{ number_format($sale->discount_total, 0, ',', '.') }}</span></div>
            <div class="mt-2 flex justify-between text-base font-black text-slate-900"><span>TOTAL</span><span>Rp{{ number_format($sale->grand_total, 0, ',', '.') }}</span></div>
        </section>

        <section class="border-t border-dashed border-slate-300 pt-3 text-xs">
            @foreach ($payments as $payment)
                <div class="flex justify-between"><span>{{ strtoupper(str_replace('_', ' ', $payment->method)) }}</span><span>Rp{{ number_format($payment->amount, 0, ',', '.') }}</span></div>
                @if ($payment->change_amount > 0)
                    <div class="mt-1 flex justify-between font-bold text-emerald-700"><span>CHANGE</span><span>Rp{{ number_format($payment->change_amount, 0, ',', '.') }}</span></div>
                @endif
            @endforeach
        </section>

        <footer class="mt-6 text-center text-xs text-slate-500"><p class="font-semibold text-slate-700">Thank you for your purchase.</p><p class="mt-1">Please retain this receipt for returns.</p></footer>
    </main>
    <div class="no-print mx-auto mt-4 flex max-w-md gap-3"><button onclick="window.print()" class="btn-primary flex-1">Print receipt</button><a href="{{ route('dashboard') }}" class="btn-secondary flex-1">Return to dashboard</a></div>
</body>
</html>
