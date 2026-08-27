<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Velex | Ride the City</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-slate-950 text-white antialiased font-sans min-h-screen flex flex-col">

    <!-- Top Nav -->
    <header class="flex items-center justify-between px-10 py-6">
        <div class="flex items-center gap-2 text-emerald-400 font-bold text-xl tracking-wider">
            VELEX
        </div>
        <nav class="flex items-center gap-4">
            @if (session('fastapi_token'))
            <a href="{{ route('dashboard') }}" class="text-sm font-semibold text-slate-300 hover:text-white transition">
                Dashboard
            </a>
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="bg-white/10 hover:bg-white/20 text-white text-sm font-bold py-2.5 px-5 rounded-xl transition">
                    Log Out
                </button>
            </form>
            @else
            <a href="{{ route('login') }}" class="text-sm font-semibold text-slate-300 hover:text-white transition">
                Sign In
            </a>
            <a href="{{ route('register') }}"
                class="bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-bold py-2.5 px-5 rounded-xl transition">
                Create Account
            </a>
            @endif
        </nav>
    </header>

    <!-- Hero -->
    <main class="flex-1 flex items-center px-10">
        <div class="max-w-2xl">
            <span class="text-emerald-400 text-xs font-bold uppercase tracking-[0.2em]">Micro-mobility, reimagined</span>
            <h1 class="text-5xl md:text-6xl font-black mt-4 mb-6 leading-tight">
                Your city.<br/>
                <span class="text-emerald-400">One ride away.</span>
            </h1>
            <p class="text-slate-400 text-lg mb-8 max-w-lg">
                Rent a motorbike in seconds, track your ride live, and pay straight from your wallet — no keys, no counters, no hassle.
            </p>
            <div class="flex items-center gap-4">
                @if (session('fastapi_token'))
                <a href="{{ route('catalog.index') }}"
                    class="bg-emerald-600 hover:bg-emerald-500 text-white font-bold py-4 px-8 rounded-xl transition">
                    Browse Bikes
                </a>
                <a href="{{ route('dashboard') }}"
                    class="text-slate-300 hover:text-white font-semibold py-4 px-8 transition">
                    Go to Dashboard →
                </a>
                @else
                <a href="{{ route('register') }}"
                    class="bg-emerald-600 hover:bg-emerald-500 text-white font-bold py-4 px-8 rounded-xl transition">
                    Get Started
                </a>
                <a href="{{ route('login') }}"
                    class="text-slate-300 hover:text-white font-semibold py-4 px-8 transition">
                    I already have an account →
                </a>
                @endif
            </div>
        </div>
    </main>

    <footer class="px-10 py-6 text-slate-600 text-xs">
        &copy; {{ date('Y') }} Velex. All rights reserved.
    </footer>

</body>

</html>
