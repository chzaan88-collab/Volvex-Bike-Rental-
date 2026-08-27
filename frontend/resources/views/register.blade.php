<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Velex | Create Account</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-slate-950 text-white antialiased font-sans flex items-center justify-center min-h-screen">

    <div class="w-full max-w-md bg-slate-900 border border-slate-800 rounded-3xl p-8 shadow-xl">
        <div class="flex items-center gap-2 mb-8 text-emerald-400 font-bold text-xl tracking-wider">
            VELEX
        </div>

        <h1 class="text-2xl font-black mb-1">Create your account</h1>
        <p class="text-slate-400 text-sm mb-6">Start riding in minutes.</p>

        @if ($errors->any())
            <div class="bg-red-950 border border-red-900 text-red-300 text-xs font-semibold rounded-xl p-3 mb-4 space-y-1">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form action="{{ route('register') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="text-xs font-bold text-slate-400 uppercase tracking-wider">Full Name</label>
                <input type="text" name="name" value="{{ old('name') }}" required
                    class="w-full mt-1 bg-slate-800 border border-slate-700 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-emerald-500">
            </div>
            <div>
                <label class="text-xs font-bold text-slate-400 uppercase tracking-wider">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required
                    class="w-full mt-1 bg-slate-800 border border-slate-700 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-emerald-500">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="text-xs font-bold text-slate-400 uppercase tracking-wider">Phone Number</label>
                    <input type="tel" name="phone" value="{{ old('phone') }}" required placeholder="03XX-XXXXXXX"
                        class="w-full mt-1 bg-slate-800 border border-slate-700 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-emerald-500">
                </div>
                <div>
                    <label class="text-xs font-bold text-slate-400 uppercase tracking-wider">CNIC</label>
                    <input type="text" name="cnic" value="{{ old('cnic') }}" required placeholder="XXXXX-XXXXXXX-X"
                        class="w-full mt-1 bg-slate-800 border border-slate-700 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-emerald-500">
                </div>
            </div>
            <div>
                <label class="text-xs font-bold text-slate-400 uppercase tracking-wider">Your Location <span class="text-emerald-500">(for bike recommendations)</span></label>
                <select name="location" required
                    class="w-full mt-1 bg-slate-800 border border-slate-700 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-emerald-500">
                    <option value="" disabled {{ old('location') ? '' : 'selected' }}>Select your city...</option>
                    <option value="Karachi" @selected(old('location') === 'Karachi')>Karachi</option>
                    <option value="Lahore" @selected(old('location') === 'Lahore')>Lahore</option>
                    <option value="Islamabad" @selected(old('location') === 'Islamabad')>Islamabad</option>
                    <option value="Rawalpindi" @selected(old('location') === 'Rawalpindi')>Rawalpindi</option>
                    <option value="Faisalabad" @selected(old('location') === 'Faisalabad')>Faisalabad</option>
                    <option value="Multan" @selected(old('location') === 'Multan')>Multan</option>
                    <option value="Peshawar" @selected(old('location') === 'Peshawar')>Peshawar</option>
                    <option value="Quetta" @selected(old('location') === 'Quetta')>Quetta</option>
                    <option value="Hyderabad" @selected(old('location') === 'Hyderabad')>Hyderabad</option>
                    <option value="Sialkot" @selected(old('location') === 'Sialkot')>Sialkot</option>
                    <option value="Gujranwala" @selected(old('location') === 'Gujranwala')>Gujranwala</option>
                    <option value="Sargodha" @selected(old('location') === 'Sargodha')>Sargodha</option>
                    <option value="Bahawalpur" @selected(old('location') === 'Bahawalpur')>Bahawalpur</option>
                    <option value="Abbottabad" @selected(old('location') === 'Abbottabad')>Abbottabad</option>
                </select>
            </div>
            <div>
                <label class="text-xs font-bold text-slate-400 uppercase tracking-wider">Password</label>
                <input type="password" name="password" required
                    class="w-full mt-1 bg-slate-800 border border-slate-700 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-emerald-500">
            </div>
            <div>
                <label class="text-xs font-bold text-slate-400 uppercase tracking-wider">Confirm Password</label>
                <input type="password" name="password_confirmation" required
                    class="w-full mt-1 bg-slate-800 border border-slate-700 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-emerald-500">
            </div>
            <button type="submit"
                class="w-full bg-emerald-600 hover:bg-emerald-500 text-white font-bold py-3 px-4 rounded-xl text-sm transition">
                Create Account
            </button>
        </form>

        <p class="text-slate-500 text-xs mt-6 text-center">
            Already have an account?
            <a href="{{ route('login') }}" class="text-emerald-400 hover:underline">Sign in</a>
        </p>
    </div>

</body>

</html>
