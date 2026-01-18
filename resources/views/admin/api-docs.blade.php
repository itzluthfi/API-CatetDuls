@extends('admin.layout')

@section('title', 'API Documentation')

@section('content')
<div class="space-y-6">
    <!-- Header dengan Glassmorphism -->
    <div class="bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500 rounded-2xl shadow-xl p-8 text-white relative overflow-hidden">
        <div class="absolute inset-0 bg-white bg-opacity-10 backdrop-blur-sm"></div>
        <div class="relative z-10">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-4xl font-bold mb-2">📚 API Documentation</h1>
                    <p class="text-indigo-100">Interactive REST API documentation for CatetDuls</p>
                </div>
                <div class="bg-white bg-opacity-20 backdrop-blur-md rounded-xl px-6 py-4 border border-white border-opacity-30">
                    <div class="text-sm text-indigo-100">Total Endpoints</div>
                    <div class="text-3xl font-bold" id="endpoint-count">...</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-blue-500 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 font-medium">Auth</p>
                    <p class="text-2xl font-bold text-gray-800">6</p>
                </div>
                <div class="bg-blue-100 rounded-full p-3">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                    </svg>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-green-500 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 font-medium">Books</p>
                    <p class="text-2xl font-bold text-gray-800">5</p>
                </div>
                <div class="bg-green-100 rounded-full p-3">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                    </svg>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-yellow-500 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 font-medium">Transactions</p>
                    <p class="text-2xl font-bold text-gray-800">8</p>
                </div>
                <div class="bg-yellow-100 rounded-full p-3">
                    <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-purple-500 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 font-medium">Categories</p>
                    <p class="text-2xl font-bold text-gray-800">5</p>
                </div>
                <div class="bg-purple-100 rounded-full p-3">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- API Docs Iframe with Premium Styling -->
    <div class="bg-white rounded-2xl shadow-2xl overflow-hidden border border-gray-200">
        <!-- Tabs Header -->
        <div class="bg-gradient-to-r from-gray-50 to-gray-100 border-b border-gray-200 px-6 py-4">
            <div class="flex items-center space-x-4">
                <button class="px-6 py-2 bg-indigo-600 text-white rounded-lg font-semibold shadow-md hover:bg-indigo-700 transition-all duration-200">
                    📖 Interactive Docs
                </button>
                <a href="/docs/api.json" target="_blank" class="px-6 py-2 bg-white text-gray-700 rounded-lg font-semibold shadow hover:shadow-md transition-all duration-200 border border-gray-300 hover:border-indigo-500">
                    { } JSON Spec
                </a>
                <div class="flex-1"></div>
                <div class="flex items-center space-x-2 text-sm text-gray-600">
                    <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
                    <span>Live Documentation</span>
                </div>
            </div>
        </div>

        <!-- Loading Overlay -->
        <div id="loading-overlay" class="absolute inset-0 bg-white flex items-center justify-center z-50">
            <div class="text-center">
                <div class="inline-block animate-spin rounded-full h-12 w-12 border-4 border-indigo-500 border-t-transparent"></div>
                <p class="mt-4 text-gray-600 font-medium">Loading API Documentation...</p>
            </div>
        </div>

        <!-- Iframe Container -->
        <div class="relative bg-gray-50" style="height: calc(100vh - 400px); min-height: 600px;">
            <iframe 
                id="api-docs-iframe"
                src="/docs/api" 
                class="w-full h-full border-0 opacity-0 transition-opacity duration-500"
                onload="document.getElementById('loading-overlay').style.display='none'; this.classList.remove('opacity-0');"
            ></iframe>
        </div>
    </div>

    <!-- Footer Info -->
    <div class="bg-gradient-to-r from-indigo-50 to-purple-50 rounded-xl p-6 border border-indigo-100">
        <div class="flex items-start space-x-4">
            <div class="bg-indigo-100 rounded-lg p-3">
                <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <div class="flex-1">
                <h3 class="font-bold text-gray-800 mb-2">💡 Pro Tips</h3>
                <ul class="space-y-2 text-sm text-gray-700">
                    <li class="flex items-start">
                        <span class="text-indigo-600 mr-2">•</span>
                        <span>Use the <strong>"Try it out"</strong> button to test endpoints directly from the browser</span>
                    </li>
                    <li class="flex items-start">
                        <span class="text-indigo-600 mr-2">•</span>
                        <span>Authentication required for most endpoints - use <strong>Bearer token</strong> format</span>
                    </li>
                    <li class="flex items-start">
                        <span class="text-indigo-600 mr-2">•</span>
                        <span>Download the JSON spec for import into Postman or Insomnia</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<style>
    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .space-y-6 > * {
        animation: slideIn 0.5s ease-out forwards;
    }
    
    .space-y-6 > *:nth-child(2) {
        animation-delay: 0.1s;
    }
    
    .space-y-6 > *:nth-child(3) {
        animation-delay: 0.2s;
    }
    
    .space-y-6 > *:nth-child(4) {
        animation-delay: 0.3s;
    }
</style>

<script>
    // Count endpoints from JSON
    fetch('/docs/api.json')
        .then(res => res.json())
        .then(data => {
            const pathCount = Object.keys(data.paths || {}).length;
            document.getElementById('endpoint-count').textContent = pathCount;
        })
        .catch(() => {
            document.getElementById('endpoint-count').textContent = '20+';
        });
</script>
@endsection
