<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Velex Wallet</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" rel="stylesheet">
</head>
<body class="bg-gray-50 text-gray-900 font-sans">
    @include('partials.sidebar')

    <div class="lg:pl-[280px] min-h-screen flex flex-col">
        <main class="flex-1 p-4 md:p-8 lg:p-10 max-w-5xl mx-auto w-full">
            @if (session('status'))
            <div class="mb-6 bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm rounded-lg p-4">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
            <div class="mb-6 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg p-4">{{ $errors->first() }}</div>
            @endif

            <div class="flex justify-between items-end mb-10">
                <div>
                    <span class="text-xs font-bold text-gray-400 uppercase tracking-widest">Available Balance</span>
                    <h1 class="text-5xl font-black mt-1">Rs. {{ number_format($current_balance, 2) }}</h1>
                </div>
                <button onclick="document.getElementById('topup-modal').classList.remove('hidden')" class="flex items-center gap-2 bg-emerald-700 text-white px-6 py-3 rounded-lg font-bold hover:bg-emerald-800">
                    <span class="material-symbols-outlined">add_circle</span> TOP UP
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-10">
                <div class="bg-gradient-to-br from-[#0B132B] to-[#1d2b53] text-white p-8 rounded-xl shadow-xl">
                    <span class="text-xs tracking-widest opacity-80">PREMIUM RIDER</span>
                    <div class="mt-8">
                        <div class="text-xs opacity-50 uppercase">Card Holder</div>
                        <div class="text-lg font-bold uppercase">{{ $user_name }}</div>
                    </div>
                </div>
                <div class="bg-white border border-gray-100 p-6 rounded-xl">
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-2">Lifetime Spend</h3>
                    <div class="text-3xl font-black">Rs. {{ number_format($lifetime_spend, 2) }}</div>
                </div>
            </div>

            <h2 class="text-lg font-bold mb-4">Transaction History</h2>
            <div class="bg-white border border-gray-100 rounded-xl overflow-hidden">
                @forelse ($transactions as $tx)
                <div class="flex justify-between items-center px-6 py-4 border-b border-gray-50 last:border-0">
                    <div>
                        <div class="font-semibold">{{ $tx['description'] ?? 'Transaction' }}</div>
                        <div class="text-xs text-gray-400">{{ $tx['created_at'] ?? '' }}</div>
                    </div>
                    <div class="font-bold {{ ($tx['amount'] ?? 0) >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                        {{ ($tx['amount'] ?? 0) >= 0 ? '+' : '' }}Rs. {{ number_format(abs($tx['amount'] ?? 0), 2) }}
                    </div>
                </div>
                @empty
                <div class="px-6 py-12 text-center text-gray-400">No transactions yet.</div>
                @endforelse
            </div>
        </main>
    </div>

    <div id="topup-modal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
        <div class="bg-white rounded-xl p-8 w-full max-w-md shadow-2xl">
            <h3 class="text-xl font-bold mb-4">Top Up Wallet</h3>
            <form action="{{ route('wallet.topup') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="text-sm font-semibold text-gray-500">Amount (Rs.)</label>
                    <input type="number" name="amount" min="1" max="10000" step="0.01" value="100" required class="w-full mt-1 border border-gray-200 rounded-lg px-4 py-3 focus:outline-none focus:border-emerald-500" />
                </div>
                <div class="flex gap-3">
                    <button type="button" onclick="document.getElementById('topup-modal').classList.add('hidden')" class="flex-1 border border-gray-200 py-3 rounded-lg font-bold">Cancel</button>
                    <button type="submit" class="flex-1 bg-emerald-700 text-white py-3 rounded-lg font-bold hover:bg-emerald-800">Confirm</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
