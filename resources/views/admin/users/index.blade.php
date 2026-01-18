@extends('admin.layout')

@section('title', 'User Management')
@section('header_title', 'Identity Registry')

@section('content')
<div class="space-y-8 animate-fade-in">
    <!-- Action Header -->
    <div class="bg-white p-8 rounded-[40px] shadow-2xl border border-slate-100 flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
            <h2 class="text-3xl font-black text-slate-800 tracking-tight mb-1">Human Entity Manager</h2>
            <p class="text-slate-400 font-bold uppercase text-[10px] tracking-[0.2em]">Oversight of all registered core nodes</p>
        </div>
        <div class="flex gap-4">
            <button onclick="loadUsers()" class="px-8 py-4 bg-indigo-600 hover:bg-slate-900 text-white font-black rounded-2xl shadow-xl shadow-indigo-100 transition-all active:scale-95 flex items-center gap-3 group">
                <i class="fas fa-sync group-hover:rotate-180 transition-transform duration-500"></i> Refresh Registry
            </button>
        </div>
    </div>

    <!-- Main Registry Table -->
    <div class="bg-white rounded-[40px] shadow-2xl border border-slate-100 overflow-hidden transition-all duration-500">
        <div class="overflow-x-auto">
            <table class="w-full text-left" id="users-table">
                <thead>
                    <tr class="bg-slate-50/50 border-b border-slate-100">
                        <th class="px-10 py-6 text-[10px] font-black uppercase text-slate-400 tracking-[0.2em]">Entity Identifier</th>
                        <th class="px-10 py-6 text-[10px] font-black uppercase text-slate-400 tracking-[0.2em]">Synchronization Mail</th>
                        <th class="px-10 py-6 text-[10px] font-black uppercase text-slate-400 tracking-[0.2em]">Ledgers Count</th>
                        <th class="px-10 py-6 text-[10px] font-black uppercase text-slate-400 tracking-[0.2em]">Operational Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50" id="users-body">
                    <!-- Data loaded via AJAX -->
                    <tr>
                        <td colspan="4" class="px-10 py-20 text-center">
                            <div class="flex flex-col items-center gap-4">
                                <i class="fas fa-spinner fa-spin text-4xl text-indigo-300"></i>
                                <span class="font-black text-slate-300 uppercase tracking-widest text-xs">Querying Nexus Database...</span>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function loadUsers() {
        $('#users-body').html(`
            <tr>
                <td colspan="4" class="px-10 py-20 text-center">
                    <div class="flex flex-col items-center gap-4">
                        <i class="fas fa-spinner fa-spin text-4xl text-indigo-300"></i>
                        <span class="font-black text-slate-300 uppercase tracking-widest text-xs">Synchronizing Entity Data...</span>
                    </div>
                </td>
            </tr>
        `);

        $.get("{{ route('admin.api.users') }}", function(response) {
            let html = '';
            if (response.success && response.data.length > 0) {
                response.data.forEach(user => {
                    html += `
                        <tr class="group hover:bg-slate-50 transition-colors duration-300">
                            <td class="px-10 py-6">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 bg-indigo-50 text-indigo-700 rounded-2xl flex items-center justify-center font-black group-hover:scale-110 transition-transform">
                                        ${user.name.charAt(0)}
                                    </div>
                                    <span class="font-black text-slate-700 tracking-tight">${user.name}</span>
                                </div>
                            </td>
                            <td class="px-10 py-6 font-bold text-slate-400 text-sm whitespace-nowrap">${user.email}</td>
                            <td class="px-10 py-6">
                                <span class="px-6 py-2 bg-slate-100 text-slate-600 font-black rounded-full text-[10px] uppercase tracking-widest">
                                    ${user.books_count} Ledgers
                                </span>
                            </td>
                            <td class="px-10 py-6">
                                <div class="flex items-center gap-2">
                                    <div class="w-2 h-2 bg-emerald-500 rounded-full"></div>
                                    <span class="text-xs font-black text-slate-800 uppercase tracking-wider">Operational</span>
                                </div>
                            </td>
                        </tr>
                    `;
                });
            } else {
                html = '<tr><td colspan="4" class="px-10 py-10 text-center text-slate-400 font-bold">No binary entities found in registry.</td></tr>';
            }
            $('#users-body').html(html);
        });
    }

    $(document).ready(function() {
        loadUsers();
    });
</script>
@endpush
