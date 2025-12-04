<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;


use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Register new user
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        // Create default book for new user
        $book = $user->books()->create([
            'name' => 'Buku Utama',
            'description' => 'Buku keuangan utama',
            'icon' => '📖',
            'color' => '#4CAF50',
            'is_default' => true,
        ]);

        // Create default wallet
        $book->wallets()->create([
            'name' => 'Tunai',
            'type' => 'CASH',
            'icon' => '💵',
            'color' => '#4CAF50',
            'initial_balance' => 0,
            'is_default' => true,
        ]);

        // Create default categories
        $categories = [
            // Pengeluaran
            ['name' => 'Makanan & Minuman', 'icon' => '🍔', 'type' => 'PENGELUARAN'],
            ['name' => 'Transport', 'icon' => '🚌', 'type' => 'PENGELUARAN'],
            ['name' => 'Belanja', 'icon' => '🛒', 'type' => 'PENGELUARAN'],
            ['name' => 'Hiburan', 'icon' => '🎮', 'type' => 'PENGELUARAN'],
            ['name' => 'Lainnya (Pengeluaran)', 'icon' => '⚙️', 'type' => 'PENGELUARAN'],

            // Pemasukan
            ['name' => 'Gaji', 'icon' => '💼', 'type' => 'PEMASUKAN'],
            ['name' => 'Bonus', 'icon' => '💰', 'type' => 'PEMASUKAN'],
            ['name' => 'Lainnya (Pemasukan)', 'icon' => '⚙️', 'type' => 'PEMASUKAN'],
        ];

        foreach ($categories as $cat) {
            $book->categories()->create([
                'name' => $cat['name'],
                'icon' => $cat['icon'],
                'type' => $cat['type'],
                'is_default' => true,
                'created_at_ts' => time(),
            ]);
        }

        // Create token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'User registered successfully',
            'data' => [
                'user' => $user,
                'token' => $token,
                'token_type' => 'Bearer',
            ]
        ], 201);
    }

    /**
     * Login user
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Delete old tokens
        $user->tokens()->delete();

        // Create new token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'user' => $user,
                'token' => $token,
                'token_type' => 'Bearer',
            ]
        ]);
    }

    /**
     * Logout user
     */
    public function logout(Request $request)
    {
        // Delete current token
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout successful'
        ]);
    }

    /**
     * Logout from all devices
     */
    public function logoutAll(Request $request)
    {
        // Delete all tokens
        $request->user()->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out from all devices'
        ]);
    }

    /**
     * Refresh token
     */
    public function refresh(Request $request)
    {
        $user = $request->user();

        // Delete current token
        $request->user()->currentAccessToken()->delete();

        // Create new token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Token refreshed successfully',
            'data' => [
                'token' => $token,
                'token_type' => 'Bearer',
            ]
        ]);
    }

    /**
     * Get authenticated user
     */
    public function me(Request $request)
    {
        $user = $request->user();
        $user->load(['books.wallets', 'books.categories']);

        return response()->json([
            'success' => true,
            'data' => $user
        ]);
    }

    /**
     * Change password
     */
    public function changePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => 'required',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = $request->user();

        // Check current password
        if (!Hash::check($validated['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The current password is incorrect.'],
            ]);
        }

        // Update password
        $user->update([
            'password' => Hash::make($validated['password'])
        ]);

        // Delete all tokens (force re-login)
        $user->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Password changed successfully. Please login again.'
        ]);
    }

    /**
     * Forgot password - send reset link (placeholder)
     */
    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        // TODO: Implement email sending logic
        // Password::sendResetLink($request->only('email'));

        return response()->json([
            'success' => true,
            'message' => 'Password reset link sent to your email'
        ]);
    }

    /**
     * Reset password (placeholder)
     */
    public function resetPassword(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|exists:users,email',
            'token' => 'required',
            'password' => 'required|string|min:8|confirmed',
        ]);

        // TODO: Implement password reset logic
        // Password::reset($validated, function ($user, $password) {
        //     $user->password = Hash::make($password);
        //     $user->save();
        // });

        return response()->json([
            'success' => true,
            'message' => 'Password reset successful'
        ]);
    }
}
