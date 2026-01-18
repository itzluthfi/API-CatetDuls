<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') - CatetDuls Admin</title>
    <!-- Alpine.js for Interactivity -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <!-- Tailwind CSS v4 -->
    <script src="https://unpkg.com/@tailwindcss/browser@4"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        [x-cloak] { display: none !important; }
        .sidebar-item:hover { background-color: rgba(255,255,255,0.1); transform: translateX(5px); }
        .sidebar-item.active { background-color: rgba(255,255,255,0.2); border-right: 4px solid #fff; }
        .sidebar-item { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        
        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 5px; }
        ::-webkit-scrollbar-track { background: #f1f1f1; }
        ::-webkit-scrollbar-thumb { background: #888; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #555; }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in { animation: fadeIn 0.5s ease-out forwards; }
    </style>
</head>
<body class="bg-slate-50 text-slate-900 overflow-hidden" x-data="{ sidebarOpen: true, logoutModal: false }">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <aside 
            :class="sidebarOpen ? 'w-72' : 'w-20'"
            class="bg-indigo-700 text-white flex-shrink-0 flex flex-col transition-all duration-500 ease-in-out relative shadow-2xl z-40"
        >
            <!-- Toggle Button -->
            <button 
                @click="sidebarOpen = !sidebarOpen"
                class="absolute -right-3 top-20 bg-white text-indigo-700 w-6 h-6 rounded-full flex items-center justify-center shadow-lg hover:scale-110 transition-transform z-50 border border-indigo-100"
            >
                <i class="fas fa-chevron-left text-[10px] transition-transform duration-500" :style="sidebarOpen ? '' : 'transform: rotate(180deg)'"></i>
            </button>

            <div class="p-6 h-20 flex items-center overflow-hidden whitespace-nowrap">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-white text-indigo-700 rounded-xl flex items-center justify-center shadow-lg shadow-black/20 shrink-0">
                        <i class="fas fa-wallet text-xl"></i>
                    </div>
                    <span class="text-2xl font-black tracking-tight transition-opacity duration-300" :class="sidebarOpen ? 'opacity-100' : 'opacity-0'">CatetDuls</span>
                </div>
            </div>

            <nav class="flex-1 px-4 py-6 space-y-3 overflow-y-auto">
                <div class="text-[10px] font-black uppercase tracking-[0.2em] text-indigo-300 mb-4 px-2 overflow-hidden" x-show="sidebarOpen" x-transition>Main Console</div>
                
                <a href="{{ route('admin.dashboard') }}" 
                   class="sidebar-item flex items-center gap-3 p-4 rounded-xl {{ request()->routeIs('admin.dashboard') ? 'active text-white' : 'text-indigo-100' }}">
                    <i class="fas fa-chart-line w-6 text-xl"></i>
                    <span class="font-bold transition-opacity whitespace-nowrap" :class="sidebarOpen ? 'opacity-100' : 'opacity-0 w-0 h-0 overflow-hidden'">Dashboard</span>
                </a>

                <a href="{{ route('admin.users') }}" 
                   class="sidebar-item flex items-center gap-3 p-4 rounded-xl {{ request()->routeIs('admin.users') ? 'active text-white' : 'text-indigo-100' }}">
                    <i class="fas fa-users w-6 text-xl"></i>
                    <span class="font-bold transition-opacity whitespace-nowrap" :class="sidebarOpen ? 'opacity-100' : 'opacity-0 w-0 h-0 overflow-hidden'">User Management</span>
                </a>

                <a href="{{ route('admin.api-docs') }}" 
                   class="sidebar-item flex items-center gap-3 p-4 rounded-xl {{ request()->routeIs('admin.api-docs') ? 'active text-white' : 'text-indigo-100' }}">
                    <i class="fas fa-terminal w-6 text-xl"></i>
                    <span class="font-bold transition-opacity whitespace-nowrap" :class="sidebarOpen ? 'opacity-100' : 'opacity-0 w-0 h-0 overflow-hidden'">API Terminal</span>
                </a>

                <div class="pt-6 border-t border-indigo-600/50 mt-6">
                    <a href="{{ route('home') }}" target="_blank" 
                       class="sidebar-item flex items-center gap-3 p-4 rounded-xl text-indigo-100">
                        <i class="fas fa-rocket w-6 text-xl"></i>
                        <span class="font-bold transition-opacity whitespace-nowrap" :class="sidebarOpen ? 'opacity-100' : 'opacity-0 w-0 h-0 overflow-hidden'">Launch App</span>
                    </a>
                </div>
            </nav>

            <div class="p-6 border-t border-indigo-600/50 bg-indigo-800/50">
                <button 
                    @click="logoutModal = true"
                    class="group flex items-center gap-3 w-full p-4 rounded-xl text-red-100 hover:bg-red-500/10 hover:text-red-400 transition-all duration-300"
                >
                    <i class="fas fa-power-off w-6 text-xl group-hover:rotate-12 transition-transform"></i>
                    <span class="font-black whitespace-nowrap transition-opacity" :class="sidebarOpen ? 'opacity-100' : 'opacity-0 w-0 h-0 overflow-hidden'">Logout</span>
                </button>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 flex flex-col min-w-0 bg-slate-50">
            <!-- Header -->
            <header class="bg-white/80 backdrop-blur-md h-20 flex items-center justify-between px-10 flex-shrink-0 z-30 border-b border-slate-200">
                <div class="flex items-center gap-4">
                    <h1 class="text-2xl font-black text-slate-800 tracking-tight">@yield('header_title', 'System Oversight')</h1>
                </div>
                
                <div class="flex items-center gap-8">
                    <div class="hidden md:flex flex-col items-end">
                        <span class="text-sm font-black text-slate-800 tracking-tight">{{ auth()->user()->name }}</span>
                        <span class="text-[10px] font-black uppercase text-indigo-600 tracking-widest">Administrator</span>
                    </div>
                    <div class="group relative">
                        <div class="w-12 h-12 bg-indigo-600 rounded-2xl flex items-center justify-center text-white font-black text-xl shadow-lg shadow-indigo-200 group-hover:rotate-6 transition-transform cursor-pointer overflow-hidden border-2 border-white">
                            {{ substr(auth()->user()->name, 0, 1) }}
                        </div>
                    </div>
                </div>
            </header>

            <!-- Scrollable Content -->
            <div class="flex-1 overflow-y-auto p-10 animate-fade-in">
                @yield('content')
            </div>
        </main>
    </div>

    <!-- Logout Confirmation Modal -->
    <div 
        x-show="logoutModal" 
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
    >
        <div 
            @click.away="logoutModal = false"
            class="bg-white rounded-3xl p-10 max-w-sm w-full shadow-2xl text-center relative overflow-hidden"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-95 translate-y-4"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
        >
            <div class="absolute top-0 left-0 w-full h-2 bg-red-500"></div>
            <div class="w-20 h-20 bg-red-100 text-red-600 rounded-2xl flex items-center justify-center text-3xl mx-auto mb-6">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h3 class="text-2xl font-black text-slate-800 mb-3 tracking-tight">Keluar Sesi?</h3>
            <p class="text-slate-500 font-medium mb-10 text-sm leading-relaxed">Apakah Anda yakin ingin keluar dari sesi administrasi saat ini?</p>
            
            <div class="flex flex-col gap-3">
                <form action="{{ route('admin.logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full bg-red-500 hover:bg-red-600 text-white font-black py-4 rounded-2xl shadow-lg shadow-red-100 transition-all active:scale-95">
                        Ya, Logout Sekarang
                    </button>
                </form>
                <button 
                    @click="logoutModal = false"
                    class="w-full py-4 text-slate-400 font-bold hover:text-slate-600 transition-colors"
                >
                    Batal
                </button>
            </div>
        </div>
    </div>

    @stack('scripts')
</body>
</html>
