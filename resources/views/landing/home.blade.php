<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CatetDuls - Simple Financial Tracker</title>
    <script src="https://unpkg.com/@tailwindcss/browser@4"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .glass { background: rgba(255, 255, 255, 0.7); backdrop-filter: blur(10px); }
        .hero-gradient { background: radial-gradient(circle at 10% 20%, rgb(255, 252, 252) 0%, rgb(235, 242, 255) 100%); }
    </style>
</head>
<body class="hero-gradient text-slate-800">
    <!-- Navbar -->
    <nav class="fixed top-0 w-full z-50 glass border-b border-white/20">
        <div class="max-w-7xl mx-auto px-6 h-20 flex items-center justify-between">
            <div class="flex items-center gap-2 font-extrabold text-2xl text-indigo-600">
                <i class="fas fa-wallet rotate-12"></i> CatetDuls
            </div>
            <div class="hidden md:flex items-center gap-8 font-bold text-slate-600">
                <a href="#features" class="hover:text-indigo-600 transition-colors">Features</a>
                <a href="#showcase" class="hover:text-indigo-600 transition-colors">Showcase</a>
                <a href="{{ url('/api/documentation') }}" class="hover:text-indigo-600 transition-colors">API Docs</a>
                <a href="{{ route('admin.login') }}" class="bg-indigo-600 text-white px-6 py-3 rounded-2xl hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-100">Admin Login</a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="pt-40 pb-20 px-6">
        <div class="max-w-7xl mx-auto grid lg:grid-cols-2 gap-12 items-center">
            <div>
                <span class="inline-block px-4 py-2 bg-indigo-50 text-indigo-600 rounded-full font-bold text-sm mb-6 border border-indigo-100">
                    💰 #1 Personal Finance Manager
                </span>
                <h1 class="text-6xl md:text-7xl font-extrabold text-slate-900 leading-[1.1] mb-8">
                    Catet Dulu, <br>
                    <span class="text-indigo-600">Untung Kemudian.</span>
                </h1>
                <p class="text-xl text-slate-500 mb-10 leading-relaxed max-w-lg">
                    Kelola pengeluaran dan pemasukanmu dengan cara paling simpel. Hemat lebih banyak, stress lebih sedikit.
                </p>
                <div class="flex flex-wrap gap-4">
                    <a href="#" class="bg-slate-900 text-white px-8 py-4 rounded-2xl font-bold flex items-center gap-3 hover:scale-105 transition-all shadow-xl shadow-slate-200">
                        <i class="fab fa-google-play text-xl"></i>
                        <div class="text-left">
                            <p class="text-[10px] uppercase font-bold text-slate-400 leading-none">Get it on</p>
                            <p class="text-lg leading-none mt-1">Google Play</p>
                        </div>
                    </a>
                    <a href="#" class="bg-white text-slate-900 border-2 border-slate-100 px-8 py-4 rounded-2xl font-bold flex items-center gap-3 hover:bg-slate-50 transition-all">
                        <i class="fas fa-file-download text-xl"></i>
                        <div class="text-left">
                            <p class="text-[10px] uppercase font-bold text-slate-400 leading-none">Direct Link</p>
                            <p class="text-lg leading-none mt-1">Download APK</p>
                        </div>
                    </a>
                </div>
            </div>
            <div class="relative flex justify-center">
                <div class="absolute -top-10 -right-10 w-64 h-64 bg-indigo-200 rounded-full blur-3xl opacity-30 animate-pulse"></div>
                <!-- App Mockup -->
                <div class="relative w-[500px] h-[500px] bg-white rounded-[40px] shadow-3xl border-[8px] border-slate-900 overflow-hidden transform lg:rotate-6 hover:rotate-0 transition-transform duration-700">
                    <div class="absolute top-0 w-full h-8 bg-slate-900 flex justify-center py-1">
                        <div class="w-20 h-4 bg-slate-800 rounded-full"></div>
                    </div>
                    <div class="p-8 pt-12">
                        <div class="bg-indigo-600 h-48 rounded-3xl p-6 text-white mb-6">
                            <p class="text-indigo-100 font-bold mb-2">Total Balance</p>
                            <h2 class="text-3xl font-extrabold">Rp 12.500.000</h2>
                            <div class="mt-8 flex gap-4">
                                <div class="bg-white/20 px-4 py-2 rounded-xl flex items-center gap-2">
                                    <i class="fas fa-arrow-down"></i> Rp 2jt
                                </div>
                                <div class="bg-white/20 px-4 py-2 rounded-xl flex items-center gap-2">
                                    <i class="fas fa-arrow-up"></i> Rp 5jt
                                </div>
                            </div>
                        </div>
                        <h4 class="font-bold text-slate-800 mb-4">Recent Transactions</h4>
                        <div class="space-y-4">
                            <div class="flex items-center justify-between p-4 bg-slate-50 rounded-2xl border border-slate-100">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 bg-amber-100 text-amber-600 rounded-xl flex items-center justify-center"><i class="fas fa-hamburger"></i></div>
                                    <div><p class="font-bold text-sm">Makan Siang</p><p class="text-xs text-slate-400">Food & Drink</p></div>
                                </div>
                                <p class="font-bold text-red-500">-50k</p>
                            </div>
                            <div class="flex items-center justify-between p-4 bg-slate-50 rounded-2xl border border-slate-100">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 bg-emerald-100 text-emerald-600 rounded-xl flex items-center justify-center"><i class="fas fa-money-bill-wave"></i></div>
                                    <div><p class="font-bold text-sm">Gaji Bulanan</p><p class="text-xs text-slate-400">Salary</p></div>
                                </div>
                                <p class="font-bold text-emerald-500">+5jt</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features -->
    <section id="features" class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-6 text-center">
            <h2 class="text-4xl font-extrabold text-slate-900 mb-4">Kenapa Pakai CatetDuls?</h2>
            <p class="text-slate-500 mb-16 max-w-2xl mx-auto">Kami merancang aplikasi ini agar siapapun bisa mulai mencatat keuangan hanya dalam hitungan detik.</p>
            <div class="grid md:grid-cols-3 gap-8">
                <div class="p-8 rounded-3xl bg-slate-50 hover:bg-white hover:shadow-xl transition-all border border-slate-100 group">
                    <div class="w-16 h-16 bg-blue-100 text-blue-600 rounded-2xl flex items-center justify-center text-3xl mb-8 group-hover:rotate-12 transition-transform">
                        <i class="fas fa-bolt"></i>
                    </div>
                    <h3 class="text-xl font-extrabold mb-4">Super Cepat</h3>
                    <p class="text-slate-500 leading-relaxed">Input transaksi hanya butuh 3 detik. Tidak ribet, tidak bertele-tele.</p>
                </div>
                <div class="p-8 rounded-3xl bg-slate-50 hover:bg-white hover:shadow-xl transition-all border border-slate-100 group">
                    <div class="w-16 h-16 bg-emerald-100 text-emerald-600 rounded-2xl flex items-center justify-center text-3xl mb-8 group-hover:rotate-12 transition-transform">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3 class="text-xl font-extrabold mb-4">Data Aman</h3>
                    <p class="text-slate-500 leading-relaxed">Data kamu tersimpan aman di cloud dan tersinkronisasi di semua perangkat.</p>
                </div>
                <div class="p-8 rounded-3xl bg-slate-50 hover:bg-white hover:shadow-xl transition-all border border-slate-100 group">
                    <div class="w-16 h-16 bg-amber-100 text-amber-600 rounded-2xl flex items-center justify-center text-3xl mb-8 group-hover:rotate-12 transition-transform">
                        <i class="fas fa-chart-pie"></i>
                    </div>
                    <h3 class="text-xl font-extrabold mb-4">Insight Cerdas</h3>
                    <p class="text-slate-500 leading-relaxed">Lihat ke mana uangmu pergi dengan grafik kategori yang mudah dipahami.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="py-12 border-t border-slate-100">
        <div class="max-w-7xl mx-auto px-6 text-center">
            <p class="text-slate-400 font-bold">&copy; 2026 CatetDuls Team. Built for Pemrograman Mobile.</p>
        </div>
    </footer>
</body>
</html>
