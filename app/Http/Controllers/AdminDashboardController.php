<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    /**
     * Home / Landing Page
     */
    public function home()
    {
        return view('landing.home');
    }

    /**
     * Show Admin Login Form
     */
    public function showLogin()
    {
        if (Auth::check() && Auth::user()->is_admin) {
            return redirect()->route('admin.dashboard');
        }
        return view('admin.login');
    }

    /**
     * Handle Admin Login
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials)) {
            if (Auth::user()->is_admin) {
                $request->session()->regenerate();
                return redirect()->intended('/admin/dashboard');
            }
            
            Auth::logout();
            return back()->withErrors([
                'email' => 'Anda tidak memiliki akses administrator.',
            ]);
        }

        return back()->withErrors([
            'email' => 'Kredensial yang diberikan tidak cocok dengan data kami.',
        ]);
    }

    /**
     * Handle Admin Logout
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/admin/login');
    }

    /**
     * Admin Dashboard
     */
    public function dashboard()
    {
        $stats = [
            'total_users' => User::count(),
            'total_transactions' => Transaction::count(),
            'total_amount' => Transaction::sum('amount'),
            'recent_users' => User::latest()->take(5)->get(),
        ];

        return view('admin.dashboard', compact('stats'));
    }

    /**
     * User Management
     */
    public function users()
    {
        return view('admin.users.index');
    }

    /**
     * API for Admin Users List (jQuery datatable style)
     */
    public function getUsersApi()
    {
        $users = User::withCount(['books'])->latest()->get();
        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }

    /**
     * API Documentation Page
     */
    public function apiDocs()
    {
        return view('admin.api-docs');
    }
}
