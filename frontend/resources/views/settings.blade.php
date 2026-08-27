<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Velex | Settings</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" rel="stylesheet">
</head>
<body class="bg-slate-50 text-slate-900 antialiased font-sans">
    @include('partials.sidebar')

    <div class="lg:pl-[280px] min-h-screen">
        <main class="max-w-3xl mx-auto py-8 md:py-16 px-4 md:px-10">
            <h1 class="text-3xl font-black mb-1">Account Settings</h1>
            <p class="text-slate-500 mb-10">Manage your Velex rider profile.</p>

            @if (session('status'))
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm font-semibold rounded-xl p-4 mb-6">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 text-sm font-semibold rounded-xl p-4 mb-6">{{ $errors->first() }}</div>
            @endif

            <div class="bg-white border border-slate-200 rounded-2xl p-8 space-y-6 mb-8">
                <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                    <span class="text-sm font-semibold text-slate-500 uppercase tracking-wider">Account Mode</span>
                    <span class="text-slate-900 font-medium">{{ ucfirst(strtolower($user->account_mode)) }}</span>
                </div>
                <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                    <span class="text-sm font-semibold text-slate-500 uppercase tracking-wider">Rider Status</span>
                    <span class="text-slate-900 font-medium">{{ ucfirst(strtolower($user->rider_status)) }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm font-semibold text-slate-500 uppercase tracking-wider">Wallet Balance</span>
                    <span class="text-slate-900 font-medium">Rs. {{ number_format($user->current_balance, 2) }}</span>
                </div>
            </div>

            <div class="bg-white border border-slate-200 rounded-2xl p-8 mb-8">
                <h2 class="text-lg font-bold mb-4">Edit Profile</h2>
                <form action="{{ route('settings.profile') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Name</label>
                        <input type="text" name="name" value="{{ old('name', $user->name) }}" required class="w-full mt-1 bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-emerald-500" />
                    </div>
                    <div>
                        <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Email</label>
                        <input type="email" value="{{ $user->email }}" readonly class="w-full mt-1 bg-slate-100 border border-slate-200 rounded-xl px-4 py-3 text-sm text-slate-500 cursor-not-allowed" />
                    </div>
                    <div>
                        <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Phone</label>
                        <input type="text" name="phone" value="{{ old('phone', $user->phone ?? '') }}" class="w-full mt-1 bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-emerald-500" />
                    </div>
                    <div>
                        <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">CNIC</label>
                        <input type="text" name="cnic" value="{{ old('cnic', $user->cnic ?? '') }}" placeholder="XXXXX-XXXXXXX-X" class="w-full mt-1 bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-emerald-500" />
                    </div>
                    <div>
                        <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Location <span class="text-emerald-600 normal-case">(used for bike recommendations)</span></label>
                        <select name="location" class="w-full mt-1 bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-emerald-500">
                            <option value="" disabled>Select your city...</option>
                            <option value="Karachi" @selected(($user->location ?? 'Karachi') === 'Karachi')>Karachi</option>
                            <option value="Lahore" @selected(($user->location ?? '') === 'Lahore')>Lahore</option>
                            <option value="Islamabad" @selected(($user->location ?? '') === 'Islamabad')>Islamabad</option>
                            <option value="Rawalpindi" @selected(($user->location ?? '') === 'Rawalpindi')>Rawalpindi</option>
                            <option value="Faisalabad" @selected(($user->location ?? '') === 'Faisalabad')>Faisalabad</option>
                            <option value="Multan" @selected(($user->location ?? '') === 'Multan')>Multan</option>
                            <option value="Peshawar" @selected(($user->location ?? '') === 'Peshawar')>Peshawar</option>
                            <option value="Quetta" @selected(($user->location ?? '') === 'Quetta')>Quetta</option>
                            <option value="Hyderabad" @selected(($user->location ?? '') === 'Hyderabad')>Hyderabad</option>
                            <option value="Sialkot" @selected(($user->location ?? '') === 'Sialkot')>Sialkot</option>
                            <option value="Gujranwala" @selected(($user->location ?? '') === 'Gujranwala')>Gujranwala</option>
                            <option value="Sargodha" @selected(($user->location ?? '') === 'Sargodha')>Sargodha</option>
                            <option value="Bahawalpur" @selected(($user->location ?? '') === 'Bahawalpur')>Bahawalpur</option>
                            <option value="Abbottabad" @selected(($user->location ?? '') === 'Abbottabad')>Abbottabad</option>
                        </select>
                    </div>
                    <button type="submit" class="bg-emerald-600 hover:bg-emerald-500 text-white font-bold py-3 px-6 rounded-xl text-sm transition">Save Profile</button>
                </form>
            </div>

            <div class="bg-white border border-slate-200 rounded-2xl p-8">
                <h2 class="text-lg font-bold mb-4">Change Password</h2>
                <form action="{{ route('settings.password') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Current Password</label>
                        <input type="password" name="current_password" required class="w-full mt-1 bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-emerald-500" />
                    </div>
                    <div>
                        <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">New Password</label>
                        <input type="password" name="password" required class="w-full mt-1 bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-emerald-500" />
                    </div>
                    <div>
                        <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Confirm New Password</label>
                        <input type="password" name="password_confirmation" required class="w-full mt-1 bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-emerald-500" />
                    </div>
                    <button type="submit" class="bg-slate-900 hover:bg-slate-800 text-white font-bold py-3 px-6 rounded-xl text-sm transition">Update Password</button>
                </form>
            </div>
        </main>
    </div>
</body>
</html>
