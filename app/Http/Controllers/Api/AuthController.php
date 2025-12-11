<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class AuthController extends Controller
{
    /**
     * Register new user
     */
    public function register(Request $request)
    {
        try {
            DB::beginTransaction();

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

            $book = $user->books()->create([
                'name' => 'Buku Utama',
                'description' => 'Buku keuangan utama',
                'icon' => '📖',
                'color' => '#4CAF50',
                'is_default' => true,
            ]);

            $book->wallets()->create([
                'name' => 'Tunai',
                'type' => 'CASH',
                'icon' => '💵',
                'color' => '#4CAF50',
                'initial_balance' => 0,
                'is_default' => true,
            ]);

            $categories = [
                ['name' => 'Makanan & Minuman', 'icon' => '🍔', 'type' => 'PENGELUARAN'],
                ['name' => 'Transport', 'icon' => '🚌', 'type' => 'PENGELUARAN'],
                ['name' => 'Belanja', 'icon' => '🛒', 'type' => 'PENGELUARAN'],
                ['name' => 'Hiburan', 'icon' => '🎮', 'type' => 'PENGELUARAN'],
                ['name' => 'Lainnya (Pengeluaran)', 'icon' => '⚙️', 'type' => 'PENGELUARAN'],

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

            $token = $user->createToken('auth_token')->plainTextToken;

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'User registered successfully',
                'data' => [
                    'user' => $user,
                    'token' => $token,
                    'token_type' => 'Bearer',
                ]
            ], 201);
        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Registration failed,' . $e->getMessage(),
                'error' => $e->getMessage(),
            ], 500);
        }
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

    // ===================================================================
    // ⭐ FORGOT PASSWORD - MENGGUNAKAN LINK RESET
    // ===================================================================

    /**
     * Kirim link reset password ke email user
     * Endpoint: POST /api/auth/forgot-password
     */
    public function forgotPassword(Request $request)
    {
        // Validasi email
        $request->validate(['email' => 'required|email']);

        // Cek apakah user dengan email tersebut ada
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Email tidak terdaftar dalam sistem.'
            ], 404);
        }

        // Kirim reset link menggunakan Laravel Password Broker
        $status = Password::sendResetLink(
            $request->only('email')
        );

        // Jika berhasil
        if ($status === Password::RESET_LINK_SENT) {
            return response()->json([
                'success' => true,
                'message' => 'Link reset password telah dikirim ke email Anda. Silakan cek inbox atau spam folder.'
            ], 200);
        }

        // Jika gagal
        return response()->json([
            'success' => false,
            'message' => 'Gagal mengirim link reset password. ' . trans($status)
        ], 400);
    }

    /**
     * Tampilkan form request reset password (untuk Web)
     * Route: GET /password/reset
     */
    public function showLinkRequestForm()
    {
        return view('auth.forgot-password');
    }

    /**
     * Tampilkan form reset password (setelah klik link di email)
     * Route: GET /password/reset/{token}
     */
    public function showResetForm(Request $request, $token = null)
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->email
        ]);
    }

    /**
     * Handle proses reset password
     * Endpoint: POST /password/reset
     */
    public function resetPassword(Request $request)
    {
        // Validasi input
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|confirmed|min:8',
        ]);

        // Reset password menggunakan Laravel Password Broker
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->save();

                // Delete all tokens (force re-login)
                $user->tokens()->delete();
            }
        );

        // Jika berhasil
        if ($status === Password::PASSWORD_RESET) {
            return response()->json([
                'success' => true,
                'message' => 'Password berhasil direset. Silakan login dengan password baru.'
            ], 200);
        }

        // Jika gagal
        return response()->json([
            'success' => false,
            'message' => 'Gagal mereset password. ' . trans($status)
        ], 400);
    }

    /**
     * Verify reset code (Optional - jika pakai kode OTP)
     */
    public function verifyResetCode(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required|string',
        ]);

        // Implementasi verifikasi kode jika Anda menggunakan sistem OTP
        // Untuk saat ini, return not implemented
        return response()->json([
            'success' => false,
            'message' => 'Fitur verifikasi kode belum diimplementasikan. Gunakan link reset via email.'
        ], 501);
    }
}
