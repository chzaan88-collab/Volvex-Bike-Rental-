<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Velex | Sign In</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-slate-950 text-white antialiased font-sans flex items-center justify-center min-h-screen">

    <div class="w-full max-w-sm bg-slate-900 border border-slate-800 rounded-3xl p-8 shadow-xl">
        <div class="flex items-center gap-2 mb-8 text-emerald-400 font-bold text-xl tracking-wider">
             VELEX
        </div>

        <h1 class="text-2xl font-black mb-1">Sign in</h1>
        <p class="text-slate-400 text-sm mb-6">Access your rider dashboard.</p>

        @if ($errors->any())
            <div class="bg-red-950 border border-red-900 text-red-300 text-xs font-semibold rounded-xl p-3 mb-4">
                {{ $errors->first() }}
            </div>
        @endif

        <form action="{{ route('login') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="text-xs font-bold text-slate-400 uppercase tracking-wider">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required
                    class="w-full mt-1 bg-slate-800 border border-slate-700 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-emerald-500">
            </div>
            <div>
                <label class="text-xs font-bold text-slate-400 uppercase tracking-wider">Password</label>
                <input type="password" name="password" required
                    class="w-full mt-1 bg-slate-800 border border-slate-700 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-emerald-500">
            </div>
            <button type="submit"
                class="w-full bg-emerald-600 hover:bg-emerald-500 text-white font-bold py-3 px-4 rounded-xl text-sm transition">
                Sign In
            </button>
        </form>

        <p class="text-slate-500 text-xs mt-6 text-center">
            Demo account: <span class="text-slate-300">rider@velex.test</span> / <span
                class="text-slate-300">password</span>
        </p>
        <p class="text-slate-500 text-xs mt-3 text-center">
            New here? <a href="{{ route('register') }}" class="text-emerald-400 hover:underline">Create an account</a>
        </p>
    </div>

</body>

</html>
