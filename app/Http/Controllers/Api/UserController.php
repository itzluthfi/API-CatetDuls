<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Get user profile
     */
    public function profile(Request $request)
    {
        $user = $request->user();
        $user->load(['books.wallets', 'books.categories']);

        // Get statistics
        $stats = [
            'total_books' => $user->books()->count(),
            'total_transactions' => $user->books()->withCount('transactions')->get()->sum('transactions_count'),
            'default_book' => $user->defaultBook,
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'user' => $user,
                'stats' => $stats,
            ]
        ]);
    }

    /**
     * Update user profile
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => [
                'sometimes',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id),
            ],
        ]);

        $user->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => $user
        ]);
    }

    /**
     * Upload profile photo (jika ingin ada fitur foto profil)
     */
    public function uploadPhoto(Request $request)
    {
        $request->validate([
            'photo' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $user = $request->user();

        // Delete old photo if exists
        if ($user->photo_url) {
            $oldPath = str_replace(Storage::url(''), '', $user->photo_url); // Penyesuaian: pastikan hanya path relatif yang dihapus
            Storage::disk('public')->delete($oldPath);
        }

        // Upload new photo
        $path = $request->file('photo')->store('profiles', 'public');
        $photoUrl = Storage::url($path);

        $user->update(['photo_url' => $photoUrl]);

        return response()->json([
            'success' => true,
            'message' => 'Photo uploaded successfully',
            'data' => [
                'photo_url' => $photoUrl
            ]
        ]);
    }

    /**
     * Delete profile photo
     */
    public function deletePhoto(Request $request)
    {
        $user = $request->user();

        if ($user->photo_url) {
            $oldPath = str_replace(Storage::url(''), '', $user->photo_url); // Penyesuaian: pastikan hanya path relatif yang dihapus
            Storage::disk('public')->delete($oldPath);

            $user->update(['photo_url' => null]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Photo deleted successfully'
        ]);
    }

    /**
     * Get user statistics
     */
    public function statistics(Request $request)
    {
        $user = $request->user();

        // Get all books
        $books = $user->books()->with('transactions')->get();

        // Calculate total income and expense
        $totalIncome = 0;
        $totalExpense = 0;
        $transactionCount = 0;

        foreach ($books as $book) {
            $totalIncome += $book->transactions()->where('type', 'PEMASUKAN')->sum('amount');
            $totalExpense += $book->transactions()->where('type', 'PENGELUARAN')->sum('amount');
            $transactionCount += $book->transactions()->count();
        }

        $balance = $totalIncome - $totalExpense;

        // Get wallet balances
        $wallets = [];
        foreach ($books as $book) {
            foreach ($book->wallets as $wallet) {
                $wallets[] = [
                    'id' => $wallet->id,
                    'name' => $wallet->name,
                    'type' => $wallet->type,
                    'current_balance' => $wallet->current_balance,
                ];
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'total_books' => $books->count(),
                'total_transactions' => $transactionCount,
                'total_income' => $totalIncome,
                'total_expense' => $totalExpense,
                'balance' => $balance,
                'wallets' => $wallets,
            ]
        ]);
    }

    /**
     * Update user preferences (untuk settings seperti currency, language, dll)
     */
    public function updatePreferences(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'currency' => 'sometimes|string|max:10',
            'language' => 'sometimes|string|max:10',
            'theme' => 'sometimes|in:light,dark,auto',
            'notifications_enabled' => 'sometimes|boolean',
        ]);

        // Simpan preferences sebagai JSON (perlu tambah column preferences di migration)
        $preferences = $user->preferences ?? [];
        $preferences = array_merge($preferences, $validated);

        // Catatan: Jika 'preferences' belum ada di kolom migration dan fillable, ini akan gagal.
        // Asumsi: Anda akan menambahkan kolom 'preferences' (json) ke tabel 'users' jika diperlukan.
        $user->update(['preferences' => $preferences]);

        return response()->json([
            'success' => true,
            'message' => 'Preferences updated successfully',
            'data' => [
                'preferences' => $preferences
            ]
        ]);
    }

    /**
     * Get user preferences
     */
    public function getPreferences(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'data' => [
                'preferences' => $user->preferences ?? []
            ]
        ]);
    }

    /**
     * Delete user account
     */
    public function deleteAccount(Request $request)
    {
        $request->validate([
            'password' => 'required',
        ]);

        $user = $request->user();

        // Verify password
        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Incorrect password'
            ], 403);
        }

        // Delete profile photo if exists
        if ($user->photo_url) {
            $oldPath = str_replace(Storage::url(''), '', $user->photo_url); // Penyesuaian: pastikan hanya path relatif yang dihapus
            Storage::disk('public')->delete($oldPath);
        }

        // Delete all transaction images
        // Perlu memuat relasi transactions pada books
        $user->load('books.transactions');
        foreach ($user->books as $book) {
            foreach ($book->transactions as $transaction) {
                if ($transaction->image_url) {
                    $oldPath = str_replace(Storage::url(''), '', $transaction->image_url); // Penyesuaian: pastikan hanya path relatif yang dihapus
                    Storage::disk('public')->delete($oldPath);
                }
            }
        }

        // Delete user (cascade akan hapus books, wallets, categories, transactions)
        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Account deleted successfully'
        ]);
    }

    /**
     * Get all users (admin only - optional)
     */
    public function index()
    {
        // TODO: Add admin check middleware
        $users = User::withCount(['books'])->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }

    /**
     * Get single user (admin only - optional)
     */
    public function show(User $user)
    {
        // TODO: Add admin check middleware
        $user->load(['books.wallets', 'books.categories']);

        return response()->json([
            'success' => true,
            'data' => $user
        ]);
    }
}
