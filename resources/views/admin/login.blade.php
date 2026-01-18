<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - CatetDuls</title>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://unpkg.com/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        @keyframes float {
            0% { transform: translateY(0px) rotate(6deg); }
            50% { transform: translateY(-10px) rotate(10deg); }
            100% { transform: translateY(0px) rotate(6deg); }
        }
        .animate-float { animation: float 3s ease-in-out infinite; }
        
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-slide-up { animation: slideUp 0.8s cubic-bezier(0.4, 0, 0.2, 1) forwards; }
    </style>
</head>
<body class="bg-slate-50 flex items-center justify-center min-h-screen p-4 overflow-hidden" x-data="{ showPw: false, quickAccess: false, email: '', password: '' }">
    <!-- Abstract Background shapes -->
    <div class="fixed -top-24 -left-24 w-96 h-96 bg-indigo-100 rounded-full mix-blend-multiply filter blur-3xl opacity-70 animate-pulse"></div>
    <div class="fixed -bottom-24 -right-24 w-96 h-96 bg-purple-100 rounded-full mix-blend-multiply filter blur-3xl opacity-70 animate-pulse" style="animation-delay: 1s"></div>

    <!-- Quick Access Toggle -->
    <button 
        @click="quickAccess = !quickAccess"
        class="fixed right-0 top-1/2 -translate-y-1/2 bg-indigo-600 text-white p-4 rounded-l-2xl shadow-2xl z-50 hover:pr-6 transition-all group flex items-center gap-3"
    >
        <i class="fas fa-bolt group-hover:rotate-12 transition-transform"></i>
        <span class="font-black text-xs uppercase tracking-widest hidden md:block">Quick Access</span>
    </button>

    <!-- Quick Access Panel -->
    <div 
        x-show="quickAccess" 
        x-cloak
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="translate-x-full"
        class="fixed right-0 top-0 h-full w-80 bg-white shadow-[-20px_0_50px_rgba(0,0,0,0.1)] z-40 p-10 border-l border-indigo-50 flex flex-col"
    >
        <div class="mb-10 flex items-center justify-between">
            <h3 class="text-xl font-black text-slate-800 tracking-tight">Quick Access</h3>
            <button @click="quickAccess = false" class="text-slate-400 hover:text-red-500 transition-colors">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="space-y-4">
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4">Select Account</p>
            
            <button 
                @click="email = 'admin@catetduls.com'; password = 'password'; quickAccess = false"
                class="w-full p-6 bg-slate-50 hover:bg-indigo-50 rounded-3xl border-2 border-transparent hover:border-indigo-100 transition-all text-left group"
            >
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 bg-indigo-600 text-white rounded-xl flex items-center justify-center text-lg shadow-lg group-hover:rotate-6 transition-transform">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <div>
                        <p class="text-sm font-black text-slate-800">Admin Account</p>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Master Access</p>
                    </div>
                </div>
            </button>
        </div>

        <div class="mt-auto pt-10 border-t border-slate-50">
            <p class="text-[10px] font-medium text-slate-400 leading-relaxed italic text-center">
                Click an account to pre-fill the login credentials instantly.
            </p>
        </div>
    </div>

    <div class="bg-white/80 backdrop-blur-xl p-10 rounded-[40px] shadow-2xl w-full max-w-md border border-white relative overflow-hidden animate-slide-up z-10">
        <div class="absolute top-0 left-0 w-full h-2 bg-indigo-600"></div>
        
        <div class="text-center mb-10">
            <div class="w-24 h-24 bg-indigo-600 text-white rounded-3xl flex items-center justify-center text-5xl mx-auto mb-8 shadow-2xl shadow-indigo-200 animate-float">
                <i class="fas fa-lock"></i>
            </div>
            <h1 class="text-4xl font-black text-slate-800 tracking-tight mb-2">Admin Login</h1>
            <p class="text-slate-400 font-bold uppercase text-[10px] tracking-[0.3em]">CatetDuls Management Hub</p>
        </div>

        @if($errors->any())
        <div class="bg-red-50 border-2 border-red-100 text-red-600 p-4 mb-8 rounded-2xl animate-shake" role="alert">
            <div class="flex items-center gap-3">
                <i class="fas fa-exclamation-triangle text-xl"></i>
                <div class="text-sm font-bold">{{ $errors->first() }}</div>
            </div>
        </div>
        @endif

        <form action="{{ route('admin.login') }}" method="POST" class="space-y-8">
            @csrf
            <div class="space-y-2">
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1" for="email">E-mail Address</label>
                <div class="relative group">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-5 text-slate-400 group-focus-within:text-indigo-600 transition-colors">
                        <i class="fas fa-envelope"></i>
                    </span>
                    <input type="email" name="email" id="email" x-model="email" required 
                        class="w-full pl-12 pr-6 py-5 bg-slate-100/50 border-2 border-transparent focus:border-indigo-600 focus:bg-white rounded-[25px] transition-all outline-none font-bold text-slate-700 placeholder-slate-300"
                        placeholder="admin@catetduls.com">
                </div>
            </div>

            <div class="space-y-2">
                <div class="flex justify-between items-center ml-1">
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest" for="password">Password</label>
                    <a href="#" class="text-[10px] font-black text-indigo-500 hover:text-indigo-700 uppercase tracking-widest transition-colors">Lupa Password?</a>
                </div>
                <div class="relative group">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-5 text-slate-400 group-focus-within:text-indigo-600 transition-colors">
                        <i class="fas fa-key"></i>
                    </span>
                    <input :type="showPw ? 'text' : 'password'" name="password" id="password" x-model="password" required 
                        class="w-full pl-12 pr-14 py-5 bg-slate-100/50 border-2 border-transparent focus:border-indigo-600 focus:bg-white rounded-[25px] transition-all outline-none font-bold text-slate-700 placeholder-slate-300"
                        placeholder="••••••••">
                    <button type="button" @click="showPw = !showPw" class="absolute inset-y-0 right-0 flex items-center pr-5 text-slate-300 hover:text-indigo-600 transition-colors">
                        <i class="fas" :class="showPw ? 'fa-eye-slash' : 'fa-eye'"></i>
                    </button>
                </div>
            </div>

            <button type="submit" 
                class="w-full bg-indigo-600 hover:bg-slate-900 text-white font-black py-5 rounded-[25px] shadow-2xl shadow-indigo-200 transition-all active:scale-95 flex items-center justify-center gap-4 group">
                Masuk ke Dashboard <i class="fas fa-paper-plane group-hover:translate-x-2 transition-transform"></i>
            </button>
        </form>

        <div class="mt-12 pt-8 border-t border-slate-100 text-center">
            <a href="{{ route('home') }}" class="inline-flex items-center gap-2 px-6 py-3 bg-slate-100 hover:bg-slate-200 text-slate-500 rounded-full text-xs font-black transition-all active:scale-95">
                <i class="fas fa-arrow-left"></i> Kembali ke Beranda
            </a>
        </div>
    </div>

    <style>
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
        .animate-shake { animation: shake 0.2s ease-in-out infinite; }
    </style>
</body>
</html>
