@extends('admin.layout')

@section('title', 'Dashboard')
@section('header_title', 'Operational Intelligence')

@section('content')
<div class="space-y-10 animate-fade-in">
    <!-- Stats Overview -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <div class="bg-white p-8 rounded-[35px] shadow-xl border border-slate-100 group hover:-translate-y-2 transition-all duration-500">
            <div class="flex items-center gap-6">
                <div class="w-16 h-16 bg-indigo-50 text-indigo-600 rounded-2xl flex items-center justify-center text-3xl group-hover:scale-110 transition-transform">
                    <i class="fas fa-users-viewfinder"></i>
                </div>
                <div>
                    <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mb-1">Human Assets</p>
                    <p class="text-3xl font-black text-slate-800 tracking-tight">{{ number_format($stats['total_users']) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white p-8 rounded-[35px] shadow-xl border border-slate-100 group hover:-translate-y-2 transition-all duration-500">
            <div class="flex items-center gap-6">
                <div class="w-16 h-16 bg-emerald-50 text-emerald-600 rounded-2xl flex items-center justify-center text-3xl group-hover:scale-110 transition-transform">
                    <i class="fas fa-bolt-lightning"></i>
                </div>
                <div>
                    <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mb-1">Total Logs</p>
                    <p class="text-3xl font-black text-slate-800 tracking-tight">{{ number_format($stats['total_transactions']) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white p-8 rounded-[35px] shadow-xl border border-slate-100 group hover:-translate-y-2 transition-all duration-500">
            <div class="flex items-center gap-6">
                <div class="w-16 h-16 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center text-3xl group-hover:scale-110 transition-transform">
                    <i class="fas fa-circuits-compass"></i>
                </div>
                <div>
                    <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mb-1">Capital Value</p>
                    <p class="text-3xl font-black text-slate-800 tracking-tight">Rp {{ number_format($stats['total_amount'], 0, ',', '.') }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Visualization Area -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Recent Nexus Contacts -->
        <div class="lg:col-span-1 bg-white p-8 rounded-[40px] shadow-2xl border border-indigo-50">
            <div class="flex items-center justify-between mb-8">
                <h3 class="text-xl font-black text-slate-800 tracking-tight">Recent Nexus</h3>
                <span class="text-[10px] font-black bg-indigo-100 text-indigo-600 px-3 py-1 rounded-full uppercase">Live</span>
            </div>
            <div class="space-y-6">
                @forelse($stats['recent_users'] as $user)
                <div class="flex items-center gap-5 p-4 rounded-3xl hover:bg-slate-50 transition-colors group">
                    <div class="w-12 h-12 bg-slate-100 rounded-2xl flex items-center justify-center text-slate-600 font-black group-hover:bg-indigo-600 group-hover:text-white transition-all">
                        {{ substr($user->name, 0, 1) }}
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-black text-slate-800">{{ $user->name }}</p>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">{{ $user->email }}</p>
                    </div>
                </div>
                @empty
                <div class="text-center py-10">
                    <i class="fas fa-user-slash text-3xl text-slate-200 mb-2"></i>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">No Recent Entities</p>
                </div>
                @endforelse
            </div>
        </div>

        <!-- System Pulse -->
        <div class="lg:col-span-2 bg-gradient-to-br from-indigo-600 to-purple-700 p-10 rounded-[40px] shadow-2xl relative overflow-hidden flex items-end">
            <div class="absolute top-0 left-0 w-full h-full opacity-10">
                <svg width="100%" height="100%" viewBox="0 0 100 100" preserveAspectRatio="none">
                    <path d="M0 100 C 20 80, 40 100, 60 70 S 80 90, 100 60 V 100 H 0" fill="white" />
                </svg>
            </div>
            <div class="relative z-10 text-white w-full">
                <div class="flex justify-between items-start mb-12">
                    <div>
                        <h2 class="text-4xl font-black tracking-tighter mb-4">System Pulse</h2>
                        <p class="text-indigo-100 max-w-md font-medium leading-relaxed opacity-80">All cores operational. Financial synchronization maintained at 99.9% uptime.</p>
                    </div>
                    <div class="w-20 h-20 bg-white/10 backdrop-blur-xl border border-white/20 rounded-3xl flex items-center justify-center text-3xl animate-pulse">
                        <i class="fas fa-satellite-dish"></i>
                    </div>
                </div>
                <div class="flex gap-4">
                    <div class="px-6 py-2 bg-white/20 backdrop-blur-md rounded-full text-[10px] font-black uppercase tracking-[0.2em] border border-white/20">Secure Cluster</div>
                    <div class="px-6 py-2 bg-white/20 backdrop-blur-md rounded-full text-[10px] font-black uppercase tracking-[0.2em] border border-white/20">Auth Layer Active</div>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
