<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'AksiSoft POS' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<div class="min-h-screen lg:flex">
    <aside class="no-print hidden w-64 shrink-0 bg-[#172033] lg:fixed lg:inset-y-0 lg:flex lg:flex-col">
        <a href="{{ route('dashboard') }}" class="flex h-[72px] items-center gap-3 border-b border-white/10 px-6 text-white">
            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-500 text-lg font-black">A</span>
            <span><span class="block text-base font-bold tracking-tight">AksiSoft</span><span class="block text-[10px] font-semibold uppercase tracking-[.2em] text-indigo-300">POS Platform</span></span>
        </a>
        <nav class="flex-1 overflow-y-auto px-3 pb-5">
            <p class="nav-group">Overview</p>
            <a class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}"><span>▦</span>Dashboard</a>
            <p class="nav-group">Sales</p>
            <a class="nav-item {{ request()->routeIs('pos.*') ? 'active' : '' }}" href="{{ route('pos.index') }}"><span>▣</span>Point of Sale</a>
            <a class="nav-item {{ request()->routeIs('registers.*') ? 'active' : '' }}" href="{{ route('registers.index') }}"><span>◴</span>Cash Register</a>
            <a class="nav-item" href="{{ route('reports.index') }}"><span>◫</span>Sales History</a>
            <p class="nav-group">Catalog & Inventory</p>
            <a class="nav-item {{ request()->routeIs('products.*') ? 'active' : '' }}" href="{{ route('products.index') }}"><span>▤</span>Products</a>
            <a class="nav-item {{ request()->routeIs('inventory.*') ? 'active' : '' }}" href="{{ route('inventory.index') }}"><span>◈</span>Inventory Ledger</a>
            <p class="nav-group">Purchasing</p>
            <a class="nav-item {{ request()->routeIs('suppliers.*') ? 'active' : '' }}" href="{{ route('suppliers.index') }}"><span>◌</span>Suppliers</a>
            <a class="nav-item {{ request()->routeIs('purchases.*') ? 'active' : '' }}" href="{{ route('purchases.index') }}"><span>▧</span>Purchase Orders</a>
            <p class="nav-group">Relationships</p>
            <a class="nav-item {{ request()->routeIs('customers.*') ? 'active' : '' }}" href="{{ route('customers.index') }}"><span>◎</span>Customers</a>
            <p class="nav-group">Insights & Control</p>
            <a class="nav-item {{ request()->routeIs('reports.*') ? 'active' : '' }}" href="{{ route('reports.index') }}"><span>◔</span>Reports</a>
            <a class="nav-item {{ request('section') === 'users' ? 'active' : '' }}" href="{{ route('administration.index', 'users') }}"><span>◉</span>Users & Roles</a>
            <a class="nav-item {{ request('section') === 'audit' ? 'active' : '' }}" href="{{ route('administration.index', 'audit') }}"><span>◍</span>Audit Logs</a>
            <a class="nav-item {{ request('section') === 'settings' ? 'active' : '' }}" href="{{ route('administration.index', 'settings') }}"><span>⚙</span>Settings</a>
        </nav>
        <div class="m-3 rounded-xl bg-white/5 p-3 text-xs text-slate-400">
            <p class="font-semibold text-white">{{ session('user_name') }}</p>
            <p>{{ session('user_role') }}</p>
            <form method="POST" action="{{ route('logout') }}" class="mt-3">@csrf<button class="font-semibold text-indigo-300 hover:text-white">Sign out securely</button></form>
        </div>
    </aside>

    <main class="min-w-0 flex-1 lg:ml-64">
        <header class="no-print sticky top-0 z-20 flex h-[72px] items-center justify-between border-b border-slate-200 bg-white/95 px-5 backdrop-blur lg:px-8">
            <div><p class="text-xs font-semibold uppercase tracking-[.14em] text-indigo-600">AksiSoft POS</p><h1 class="text-lg font-bold text-slate-900">{{ $title ?? 'Workspace' }}</h1></div>
            <div class="flex items-center gap-3"><span class="hidden rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 sm:inline">● System operational</span><a href="{{ route('pos.index') }}" class="btn-primary">Open POS</a></div>
        </header>
        <div class="p-5 lg:p-8">
            @if(session('success'))<div class="mb-5 flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">✓ {{ session('success') }}</div>@endif
            @if(session('error'))<div class="mb-5 flex items-center gap-3 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800">! {{ session('error') }}</div>@endif
            @if($errors->any())<div class="mb-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800"><p class="font-bold">Please review the highlighted information.</p><ul class="mt-1 list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
            {{ $slot }}
        </div>
    </main>
</div>
</body>
</html>
