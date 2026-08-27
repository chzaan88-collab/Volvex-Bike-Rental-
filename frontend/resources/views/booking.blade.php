<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Velex {{ $checkout ? 'Checkout' : 'Bookings' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:wght@600;700&family=Work+Sans:wght@400;500&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" rel="stylesheet">
</head>
<body class="bg-gray-50 text-gray-900 font-sans">
    @include('partials.sidebar')

    <div class="lg:pl-[280px] min-h-screen flex flex-col">
        <main class="flex-1 p-4 md:p-8 lg:p-10 max-w-6xl mx-auto w-full">
            @if (session('status'))
            <div class="mb-6 bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm rounded-lg p-4">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
            <div class="mb-6 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg p-4">{{ $errors->first() }}</div>
            @endif

            @if ($checkout && $bike)
            <section class="mb-8">
                <h1 class="text-4xl font-black">{{ $bike->name }}</h1>
                <p class="text-gray-500 mt-2">{{ $bike->description }}</p>
            </section>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 items-start">
                <div>
                    <img src="{{ $bike->image_url ?? 'https://placehold.co/800x500' }}" alt="{{ $bike->name }}" class="w-full rounded-xl shadow-lg aspect-video object-cover" />
                    <div class="grid grid-cols-3 gap-4 mt-6">
                        <div class="bg-white p-4 rounded-xl border"><div class="text-xs text-gray-400 uppercase">Engine</div><div class="font-bold">{{ $bike->engine_cc }}</div></div>
                        <div class="bg-white p-4 rounded-xl border"><div class="text-xs text-gray-400 uppercase">Fuel</div><div class="font-bold">{{ $bike->fuel_type }}</div></div>
                        <div class="bg-white p-4 rounded-xl border"><div class="text-xs text-gray-400 uppercase">City</div><div class="font-bold">{{ $bike->city }}</div></div>
                    </div>
                </div>

                <div class="bg-[#0B132B] text-white p-8 rounded-xl shadow-xl">
                    <h2 class="text-xl font-bold uppercase tracking-widest mb-6">Complete Booking</h2>
                    <form id="booking-form" action="{{ route('rides.start', $bike->id) }}" method="POST" class="space-y-4">
                        @csrf
                        <div>
                            <label class="text-xs uppercase text-white/60">Booking Type</label>
                            <select name="booking_type" id="booking_type" class="w-full mt-1 bg-white/10 border border-white/20 rounded-lg px-4 py-3 text-white" required>
                                <option value="Hourly">Hourly (Rs. {{ number_format($bike->hourly_rate, 2) }}/hr)</option>
                                <option value="Daily">Daily (Rs. {{ number_format($bike->daily_rate, 2) }}/day)</option>
                                <option value="Monthly">Monthly (Rs. {{ number_format($bike->monthly_rate, 2) }}/mo)</option>
                            </select>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="text-xs uppercase text-white/60">Start Date</label>
                                <input type="date" name="start_date" id="start_date" value="{{ old('start_date', date('Y-m-d')) }}" class="w-full mt-1 bg-white/10 border border-white/20 rounded-lg px-4 py-3 text-white" required />
                            </div>
                            <div>
                                <label class="text-xs uppercase text-white/60">End Date</label>
                                <input type="date" name="end_date" id="end_date" value="{{ old('end_date', date('Y-m-d')) }}" class="w-full mt-1 bg-white/10 border border-white/20 rounded-lg px-4 py-3 text-white" required />
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="text-xs uppercase text-white/60">Start Time</label>
                                <input type="time" name="start_time" id="start_time" value="{{ old('start_time', '09:00') }}" class="w-full mt-1 bg-white/10 border border-white/20 rounded-lg px-4 py-3 text-white" required />
                            </div>
                            <div>
                                <label class="text-xs uppercase text-white/60">End Time</label>
                                <input type="time" name="end_time" id="end_time" value="{{ old('end_time', '17:00') }}" class="w-full mt-1 bg-white/10 border border-white/20 rounded-lg px-4 py-3 text-white" required />
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="text-xs uppercase text-white/60">Phone Number</label>
                                <input type="tel" name="phone" value="{{ old('phone', session('fastapi_user.phone', '')) }}" placeholder="03XX-XXXXXXX" class="w-full mt-1 bg-white/10 border border-white/20 rounded-lg px-4 py-3 text-white" required />
                            </div>
                            <div>
                                <label class="text-xs uppercase text-white/60">CNIC</label>
                                <input type="text" name="cnic" value="{{ old('cnic', session('fastapi_user.cnic', '')) }}" placeholder="XXXXX-XXXXXXX-X" class="w-full mt-1 bg-white/10 border border-white/20 rounded-lg px-4 py-3 text-white" required />
                            </div>
                        </div>
                        <div>
                            <label class="text-xs uppercase text-white/60">Discount / Offer</label>
                            <select name="offer_code" id="offer_code" class="w-full mt-1 bg-white/10 border border-white/20 rounded-lg px-4 py-3 text-white">
                                <option value="">No discount</option>
                                @foreach ($offers ?? [] as $offer)
                                    <option value="{{ $offer['code'] }}">
                                        {{ $offer['code'] }} — {{ $offer['title'] ?? $offer['code'] }}
                                        ({{ (strtolower($offer['discount_type'] ?? 'percent') === 'percent') ? $offer['discount_value'] . '%' : 'Rs. ' . $offer['discount_value'] }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        @if (session('fastapi_token'))
                        <div class="bg-white/5 border border-white/10 rounded-lg p-4 mt-2">
                            <div class="text-xs uppercase text-white/60 mb-2">Price breakdown</div>
                            <div id="price-breakdown" class="space-y-1 text-sm">
                                <div class="flex justify-between"><span class="text-white/60">Pick date & time to see the price</span><span></span></div>
                            </div>
                            <div class="flex justify-between font-bold text-lg mt-2 pt-2 border-t border-white/10">
                                <span>Total</span>
                                <span id="total-amount">Rs. —</span>
                            </div>
                        </div>
                        @endif

                        <p class="text-sm text-white/60">Pickup from <span class="font-bold">{{ $bike->city }}</span>. Insurance included. Prices vary by time of day <strong>and city</strong>: off-peak (night) is cheaper, while morning/evening commutes and <span class="font-bold">high-demand city rush hours</span> cost more.</p>
                        <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-500 text-white font-bold py-4 rounded-lg flex items-center justify-center gap-2">
                            Complete Booking
                            <span class="material-symbols-outlined">arrow_forward</span>
                        </button>
                    </form>
                </div>
            </div>
            @else
            <h1 class="text-3xl font-black mb-6">My Bookings</h1>
            <div class="space-y-4">
                @forelse ($rides as $ride)
                <div class="bg-white border border-gray-100 rounded-xl p-6 flex justify-between items-center shadow-sm">
                    <div>
                        <h3 class="font-bold text-lg">{{ $ride['bike_name'] }}</h3>
                        <p class="text-sm text-gray-500">{{ $ride['start_date'] }} {{ $ride['start_time'] }} &rarr; {{ $ride['end_date'] }} {{ $ride['end_time'] }}</p>
                        <span class="inline-block mt-2 text-xs font-bold px-2 py-1 rounded-full {{ $ride['status'] === 'Approved' ? 'bg-emerald-100 text-emerald-700' : ($ride['status'] === 'Pending' ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-600') }}">{{ $ride['status'] }}</span>
                        @if (!empty($ride['discount_code']))
                            <p class="text-xs text-gray-500 mt-1">Rs. {{ number_format($ride['base_amount'], 2) }} base &minus; Rs. {{ number_format($ride['discount_amount'], 2) }} "{{ $ride['discount_code'] }}" discount</p>
                        @endif
                    </div>
                    <div class="text-right">
                        <div class="text-2xl font-black">Rs. {{ number_format($ride['total_amount'], 2) }}</div>
                        <div class="text-xs text-gray-400">{{ $ride['booking_type'] }}</div>
                        @if ($ride['status'] === 'Approved')
                        <div class="mt-3 flex gap-2 justify-end">
                            <form action="{{ route('agreements.generate', $ride['id']) }}" method="POST">
                                @csrf
                                <button type="submit" class="inline-flex items-center gap-1 bg-emerald-700 hover:bg-emerald-800 text-white px-3 py-1.5 rounded-lg text-xs font-bold transition-colors">
                                    <span class="material-symbols-outlined text-sm">description</span>
                                    Generate Agreement
                                </button>
                            </form>
                            <a href="{{ route('agreements.download', $ride['id']) }}" class="inline-flex items-center gap-1 bg-blue-700 hover:bg-blue-800 text-white px-3 py-1.5 rounded-lg text-xs font-bold transition-colors">
                                <span class="material-symbols-outlined text-sm">download</span>
                                Download PDF
                            </a>
                        </div>
                        @endif
                    </div>
                </div>
                @empty
                <p class="text-gray-400 text-center py-12">No bookings yet. <a href="{{ route('catalog.index') }}" class="text-emerald-700 font-bold">Browse bikes</a></p>
                @endforelse
            </div>
            @endif
        </main>
    </div>

    @if ($checkout && $bike && session('fastapi_token'))
    <script>
    (function () {
        var form = document.getElementById('booking-form');
        if (!form) return;
        var quoteUrl = "{{ route('booking.quote', $bike->id) }}";
        var fields = ['booking_type', 'start_date', 'end_date', 'start_time', 'end_time', 'offer_code'];

        function money(v) { return 'Rs. ' + (Number(v) || 0).toFixed(2); }

        function line(label, value) {
            return '<div class="flex justify-between"><span>' + label + '</span><span>' + value + '</span></div>';
        }

        function fetchQuote() {
            var data = new FormData(form);
            fetch(quoteUrl, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: data })
                .then(function (r) { return r.json(); })
                .then(renderQuote)
                .catch(function () {});
        }

        function renderQuote(b) {
            var box = document.getElementById('price-breakdown');
            var total = document.getElementById('total-amount');
            if (!box || !total) return;
            if (b && b.detail) {
                box.innerHTML = '<div class="text-red-300">' + b.detail + '</div>';
                total.textContent = '—';
                return;
            }
            if (!b || b.total_amount == null) {
                box.innerHTML = '<div class="text-white/60">No price available yet.</div>';
                total.textContent = '—';
                return;
            }
            var html = '';
            html += line('Base (' + (b.quantity || 1) + ' ' + (b.unit || 'unit') + ')', money(b.base_amount));
            html += line('Demand (' + (b.time_label || 'Standard') + ' \xD7' + (Number(b.time_multiplier) || 1).toFixed(2) + ')', money(b.subtotal));
            if ((b.long_term_discount || 0) > 0) {
                html += line('<span class="text-emerald-400">Long-term (' + (b.long_term_label || 'discount') + ')</span>', '- ' + money(b.long_term_discount));
            }
            if ((b.discount_amount || 0) > 0) {
                html += line('<span class="text-emerald-400">Discount ' + (b.discount_code ? '(' + b.discount_code + ')' : '') + '</span>', '- ' + money(b.discount_amount));
            }
            box.innerHTML = html;
            total.textContent = money(b.total_amount);
        }

        fields.forEach(function (id) {
            var el = document.getElementById(id);
            if (el) el.addEventListener('change', fetchQuote);
        });
        fetchQuote();
    })();
    </script>
    @endif
</body>
</html>
