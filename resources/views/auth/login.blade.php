<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Sign in · AksiSoft POS</title>@vite(['resources/css/app.css', 'resources/js/app.js'])</head>
<body class="min-h-screen bg-slate-950">
<div class="grid min-h-screen lg:grid-cols-2">
    <section class="relative hidden overflow-hidden bg-[#172033] p-12 lg:flex lg:flex-col lg:justify-between">
        <div class="absolute -right-24 -top-24 h-96 w-96 rounded-full bg-indigo-500/20 blur-3xl"></div><div class="absolute -bottom-32 -left-20 h-80 w-80 rounded-full bg-cyan-400/10 blur-3xl"></div>
        <div class="relative flex items-center gap-3 text-white"><span class="flex h-11 w-11 items-center justify-center rounded-xl bg-indigo-500 text-xl font-black">A</span><span><span class="block text-xl font-bold">AksiSoft</span><span class="text-xs font-semibold uppercase tracking-[.25em] text-indigo-300">POS Platform</span></span></div>
        <div class="relative max-w-md"><p class="mb-4 text-sm font-semibold uppercase tracking-[.2em] text-indigo-300">Retail operations, controlled</p><h1 class="text-4xl font-bold leading-tight text-white">Run every counter with clarity, speed, and accountability.</h1><p class="mt-5 leading-7 text-slate-300">AksiSoft unifies cashier operations, inventory movements, purchasing, and management reporting in one auditable workspace.</p></div>
        <div class="relative grid grid-cols-3 gap-4 text-center text-xs text-slate-300"><div class="rounded-xl border border-white/10 bg-white/5 p-3"><b class="block text-lg text-white">RBAC</b>Protected access</div><div class="rounded-xl border border-white/10 bg-white/5 p-3"><b class="block text-lg text-white">Ledger</b>Traceable stock</div><div class="rounded-xl border border-white/10 bg-white/5 p-3"><b class="block text-lg text-white">POS</b>Fast checkout</div></div>
    </section>
    <section class="flex items-center justify-center bg-slate-50 p-6 sm:p-10">
        <div class="w-full max-w-xl"><div class="mb-8 lg:hidden"><div class="flex items-center gap-3"><span class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-600 text-lg font-black text-white">A</span><span class="text-lg font-bold text-slate-900">AksiSoft POS</span></div></div><div class="card p-7 sm:p-8"><div class="mb-7"><p class="text-sm font-semibold text-indigo-600">WELCOME BACK</p><h2 class="mt-1 text-2xl font-bold text-slate-900">Sign in to your workspace</h2><p class="mt-2 text-sm leading-6 text-slate-500">Use your email address or username. Login attempts are rate limited for account protection.</p></div>
            @if($errors->any())<div class="mb-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ $errors->first() }}</div>@endif
            <form method="POST" action="{{ route('login.attempt') }}" class="space-y-5">@csrf
                <div><label class="field-label" for="login">Email or username</label><input id="login" name="login" value="{{ old('login') }}" autocomplete="username" required autofocus class="w-full px-3 py-2.5" placeholder="admin@aksisoft.web.id"></div>
                <div><label class="field-label" for="password">Password</label><input id="password" name="password" type="password" autocomplete="current-password" required class="w-full px-3 py-2.5" placeholder="Enter your password"></div>
                <label class="flex cursor-pointer items-center gap-2 text-sm text-slate-600"><input type="checkbox" class="rounded border-slate-300 text-indigo-600">Remember this device</label>
                <button class="btn-primary w-full py-3">Sign in securely <span>→</span></button>
            </form>
            <section class="mt-7 border-t border-slate-100 pt-6" aria-labelledby="demo-accounts-title"><div class="flex items-start justify-between gap-3"><div><p class="text-xs font-bold uppercase tracking-[.15em] text-indigo-600">Demo environment</p><h3 id="demo-accounts-title" class="mt-1 font-bold text-slate-900">Click an account to fill the form</h3></div><span class="badge badge-info">Demo password</span></div><p class="mt-2 text-xs leading-5 text-slate-500">All listed accounts use <code class="rounded bg-slate-100 px-1.5 py-0.5 font-semibold text-slate-700">DemoAksiSoft2026!</code>. Select an account, then click <strong>Sign in securely</strong>.</p><div class="mt-4 grid gap-2 sm:grid-cols-2">@foreach($demoAccounts as $account)<button type="button" data-demo-login="{{ $account['login'] }}" data-demo-password="DemoAksiSoft2026!" class="demo-account group rounded-xl border border-slate-200 bg-white p-3 text-left transition hover:border-indigo-300 hover:bg-indigo-50 focus:outline-none focus:ring-2 focus:ring-indigo-500"><span class="flex items-start justify-between gap-3"><span><span class="block text-sm font-semibold text-slate-900 group-hover:text-indigo-700">{{ $account['name'] }}</span><span class="mt-0.5 block text-xs text-slate-500">{{ $account['role'] }}</span></span><span class="text-indigo-500">→</span></span><span class="mt-2 block truncate text-xs font-medium text-slate-600">{{ $account['login'] }}</span></button>@endforeach</div><p id="demo-selection" class="mt-3 hidden rounded-lg bg-emerald-50 px-3 py-2 text-xs font-medium text-emerald-800" role="status"></p></section>
            <div class="mt-6 border-t border-slate-100 pt-5 text-center text-xs leading-5 text-slate-500">Contact your system administrator if you cannot access your account. Never share your credentials or session.</div></div>
        </div>
    </section>
</div>
<script>
document.querySelectorAll('[data-demo-login]').forEach((account) => {
    account.addEventListener('click', () => {
        document.getElementById('login').value = account.dataset.demoLogin;
        document.getElementById('password').value = account.dataset.demoPassword;
        const notice = document.getElementById('demo-selection');
        notice.textContent = `Demo account selected: ${account.dataset.demoLogin}. You can now sign in.`;
        notice.classList.remove('hidden');
        document.getElementById('password').focus();
    });
});
</script>
</body></html>
