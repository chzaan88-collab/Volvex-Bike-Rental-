<aside id="sidebar" class="fixed left-0 top-0 h-full w-[280px] bg-[#0B132B] text-white z-50 flex flex-col shadow-xl transition-transform duration-300 -translate-x-full lg:translate-x-0">
    <div class="px-6 py-6 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <span class="material-symbols-outlined text-emerald-400 text-[32px]">two_wheeler</span>
            <span class="text-lg font-bold tracking-tighter uppercase">VELEX</span>
        </div>
        <button onclick="document.getElementById('sidebar').classList.add('-translate-x-full')" class="lg:hidden material-symbols-outlined text-white/60 hover:text-white">close</button>
    </div>

    @if(session('fastapi_token'))
    <div class="px-4 mb-6">
        <form action="{{ route('user.switch-mode') }}" method="POST">
            @csrf
            <button type="submit" class="w-full bg-[#0F8A5F] hover:bg-emerald-600 text-white py-2 px-4 rounded-lg flex items-center justify-between transition-colors group">
                <div class="flex flex-col items-start">
                    <span class="text-[10px] opacity-80 uppercase tracking-widest">Switch To</span>
                    <span class="text-sm font-bold">
                        {{ ($accountMode ?? 'rider') === 'owner' ? 'RIDER MODE' : 'OWNER MODE' }}
                    </span>
                </div>
                <span class="material-symbols-outlined transition-transform group-hover:rotate-180">sync_alt</span>
            </button>
        </form>
    </div>
    @endif

    <nav class="flex-1 px-4 overflow-y-auto">
        @if(!session('fastapi_token'))
            <a class="flex items-center px-6 py-3 mb-1 text-white/60 hover:text-white transition-all text-sm" href="{{ route('login') }}">
                <span class="material-symbols-outlined mr-3 text-[20px]">login</span>Sign In
            </a>
            <a class="flex items-center px-6 py-3 mb-1 text-white/60 hover:text-white transition-all text-sm" href="{{ route('register') }}">
                <span class="material-symbols-outlined mr-3 text-[20px]">person_add</span>Create Account
            </a>
        @elseif(($accountMode ?? 'rider') === 'owner')
            <a class="flex items-center px-6 py-3 mb-1 {{ ($activeNav ?? '') === 'owner-dashboard' ? 'bg-white/5 border-l-4 border-emerald-400 text-white' : 'text-white/60 hover:text-white' }} transition-all text-sm" href="{{ route('owner.dashboard') }}">
                <span class="material-symbols-outlined mr-3 text-[20px]">grid_view</span>Owner Dashboard
            </a>
            <a class="flex items-center px-6 py-3 mb-1 {{ ($activeNav ?? '') === 'owner-bikes' ? 'bg-white/5 border-l-4 border-emerald-400 text-white' : 'text-white/60 hover:text-white' }} transition-all text-sm" href="{{ route('owner.bikes') }}">
                <span class="material-symbols-outlined mr-3 text-[20px]">motorcycle</span>My Bikes
            </a>
            <a class="flex items-center px-6 py-3 mb-1 {{ ($activeNav ?? '') === 'owner-bookings' ? 'bg-white/5 border-l-4 border-emerald-400 text-white' : 'text-white/60 hover:text-white' }} transition-all text-sm" href="{{ route('owner.bookings') }}">
                <span class="material-symbols-outlined mr-3 text-[20px]">receipt_long</span>Bookings
            </a>
            <a class="flex items-center px-6 py-3 mb-1 {{ ($activeNav ?? '') === 'owner-earnings' ? 'bg-white/5 border-l-4 border-emerald-400 text-white' : 'text-white/60 hover:text-white' }} transition-all text-sm" href="{{ route('owner.earnings') }}">
                <span class="material-symbols-outlined mr-3 text-[20px]">payments</span>Earnings
            </a>
            <a class="flex items-center px-6 py-3 mb-1 {{ ($activeNav ?? '') === 'owner-analytics' ? 'bg-white/5 border-l-4 border-emerald-400 text-white' : 'text-white/60 hover:text-white' }} transition-all text-sm" href="{{ route('owner.analytics') }}">
                <span class="material-symbols-outlined mr-3 text-[20px]">insights</span>Analytics
            </a>
        @else
            <a class="flex items-center px-6 py-3 mb-1 {{ ($activeNav ?? '') === 'dashboard' ? 'bg-white/5 border-l-4 border-emerald-400 text-white' : 'text-white/60 hover:text-white' }} transition-all text-sm" href="{{ route('dashboard') }}">
                <span class="material-symbols-outlined mr-3 text-[20px]">grid_view</span>Dashboard
            </a>
            <a class="flex items-center px-6 py-3 mb-1 {{ ($activeNav ?? '') === 'catalog' ? 'bg-white/5 border-l-4 border-emerald-400 text-white' : 'text-white/60 hover:text-white' }} transition-all text-sm" href="{{ route('catalog.index') }}">
                <span class="material-symbols-outlined mr-3 text-[20px]">motorcycle</span>Browse Motorbikes
            </a>
            <a class="flex items-center px-6 py-3 mb-1 {{ ($activeNav ?? '') === 'active-rides' ? 'bg-white/5 border-l-4 border-emerald-400 text-white' : 'text-white/60 hover:text-white' }} transition-all text-sm" href="{{ route('dashboard') }}#active-rides">
                <span class="material-symbols-outlined mr-3 text-[20px]">route</span>Active Rides
            </a>
            <a class="flex items-center px-6 py-3 mb-1 {{ ($activeNav ?? '') === 'favorites' ? 'bg-white/5 border-l-4 border-emerald-400 text-white' : 'text-white/60 hover:text-white' }} transition-all text-sm" href="{{ route('favorites.index') }}">
                <span class="material-symbols-outlined mr-3 text-[20px]">favorite</span>Favorites
            </a>
            <a class="flex items-center px-6 py-3 mb-1 {{ ($activeNav ?? '') === 'notifications' ? 'bg-white/5 border-l-4 border-emerald-400 text-white' : 'text-white/60 hover:text-white' }} transition-all text-sm" href="{{ route('notifications.index') }}">
                <span class="material-symbols-outlined mr-3 text-[20px]">notifications</span>Notifications
            </a>
            <a class="flex items-center px-6 py-3 mb-1 {{ ($activeNav ?? '') === 'reviews' ? 'bg-white/5 border-l-4 border-emerald-400 text-white' : 'text-white/60 hover:text-white' }} transition-all text-sm" href="{{ route('reviews.index') }}">
                <span class="material-symbols-outlined mr-3 text-[20px]">star</span>Reviews
            </a>
            <a class="flex items-center px-6 py-3 mb-1 {{ ($activeNav ?? '') === 'wallet' ? 'bg-white/5 border-l-4 border-emerald-400 text-white' : 'text-white/60 hover:text-white' }} transition-all text-sm" href="{{ route('wallet.index') }}">
                <span class="material-symbols-outlined mr-3 text-[20px]">account_balance_wallet</span>Wallet
            </a>
            <a class="flex items-center px-6 py-3 mb-1 {{ ($activeNav ?? '') === 'settings' ? 'bg-white/5 border-l-4 border-emerald-400 text-white' : 'text-white/60 hover:text-white' }} transition-all text-sm" href="{{ route('settings.index') }}">
                <span class="material-symbols-outlined mr-3 text-[20px]">settings</span>Settings
            </a>
        @endif

        @if(session('fastapi_token') && isset($accountMode) && $accountMode === 'rider' && (session('fastapi_user.role') ?? '') === 'admin')
        <div class="mt-4 pt-4 border-t border-white/10">
            <span class="px-6 text-[10px] font-bold uppercase tracking-widest text-white/30">Admin</span>
            <a class="flex items-center px-6 py-3 mb-1 {{ ($activeNav ?? '') === 'admin-dashboard' ? 'bg-white/5 border-l-4 border-emerald-400 text-white' : 'text-white/60 hover:text-white' }} transition-all text-sm" href="{{ route('admin.dashboard') }}">
                <span class="material-symbols-outlined mr-3 text-[20px]">admin_panel_settings</span>Admin Dashboard
            </a>
            <a class="flex items-center px-6 py-3 mb-1 {{ ($activeNav ?? '') === 'admin-bikes' ? 'bg-white/5 border-l-4 border-emerald-400 text-white' : 'text-white/60 hover:text-white' }} transition-all text-sm" href="{{ route('admin.bikes') }}">
                <span class="material-symbols-outlined mr-3 text-[20px]">directions_bike</span>All Bikes
            </a>
            <a class="flex items-center px-6 py-3 mb-1 {{ ($activeNav ?? '') === 'admin-bookings' ? 'bg-white/5 border-l-4 border-emerald-400 text-white' : 'text-white/60 hover:text-white' }} transition-all text-sm" href="{{ route('admin.bookings') }}">
                <span class="material-symbols-outlined mr-3 text-[20px]">receipt_long</span>All Bookings
            </a>
        </div>
        @endif
    </nav>

    <div class="mt-auto p-6 bg-black/20 relative z-10">
        <div class="flex items-center gap-3 mb-3">
            <div class="w-10 h-10 rounded-full bg-emerald-500 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-white text-[20px]">person</span>
            </div>
            <div class="flex-1 min-w-0">
                <div class="text-[16px] font-semibold truncate">{{ $user_name ?? 'Guest' }}</div>
                <div class="text-xs text-white/40 uppercase tracking-wider">
                    {{ ucfirst($accountMode ?? 'rider') }} {{ ($accountMode ?? 'rider') === 'owner' ? 'Account' : 'Rider' }}
                </div>
            </div>
            @if(session('fastapi_token'))
            <button onclick="document.getElementById('sidebar-menu').classList.toggle('hidden')" class="material-symbols-outlined text-white/60 hover:text-white">more_vert</button>
            @endif
        </div>
        @if(session('fastapi_token'))
        <div id="sidebar-menu" class="hidden absolute bottom-full right-6 mb-2 bg-[#111A33] border border-white/10 rounded-lg shadow-xl overflow-hidden w-40">
            <a href="{{ route('settings.index') }}" class="flex items-center gap-2 px-4 py-2 text-xs text-white/70 hover:bg-white/5 hover:text-white transition-colors">
                <span class="material-symbols-outlined text-[18px]">settings</span>Settings
            </a>
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="w-full flex items-center gap-2 px-4 py-2 text-xs text-white/70 hover:bg-white/5 hover:text-white transition-colors text-left">
                    <span class="material-symbols-outlined text-[18px]">logout</span>Log out
                </button>
            </form>
        </div>
        @endif
    </div>
</aside>

<!-- Mobile overlay -->
<div id="sidebar-overlay" class="fixed inset-0 bg-black/50 z-40 hidden lg:hidden" onclick="document.getElementById('sidebar').classList.add('-translate-x-full'); this.classList.add('hidden')"></div>

<!-- Mobile hamburger -->
<button onclick="document.getElementById('sidebar').classList.remove('-translate-x-full'); document.getElementById('sidebar-overlay').classList.remove('hidden')" class="lg:hidden fixed top-4 left-4 z-50 bg-[#0B132B] text-white p-2 rounded-lg shadow-lg">
    <span class="material-symbols-outlined">menu</span>
</button>

@include('partials.ai-chatbot')
