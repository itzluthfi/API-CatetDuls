<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
    public function profile()
    {
        $user = Auth::user();
        $user->load(['books.wallets', 'books.categories']);

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
        $user = Auth::user();

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
     * Upload profile photo
     */
    public function uploadPhoto(Request $request)
    {
        $request->validate([
            'photo' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $user = Auth::user();

        if ($user->photo_url) {
            $oldPath = str_replace(Storage::url(''), '', $user->photo_url);
            Storage::disk('public')->delete($oldPath);
        }

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
    public function deletePhoto()
    {
        $user = Auth::user();

        if ($user->photo_url) {
            $oldPath = str_replace(Storage::url(''), '', $user->photo_url);
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
    public function statistics()
    {
        $user = Auth::user();
        $books = $user->books()->with(['transactions', 'wallets'])->get();

        $totalIncome = 0;
        $totalExpense = 0;
        $transactionCount = 0;

        foreach ($books as $book) {
            $totalIncome += $book->transactions()->where('type', 'PEMASUKAN')->sum('amount');
            $totalExpense += $book->transactions()->where('type', 'PENGELUARAN')->sum('amount');
            $transactionCount += $book->transactions()->count();
        }

        $balance = $totalIncome - $totalExpense;

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
     * Update user preferences
     */
    public function updatePreferences(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'currency' => 'sometimes|string|max:10',
            'language' => 'sometimes|string|max:10',
            'theme' => 'sometimes|in:light,dark,auto',
            'notifications_enabled' => 'sometimes|boolean',
        ]);

        $preferences = $user->preferences ?? [];
        $preferences = array_merge($preferences, $validated);

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
     * Get preferences
     */
    public function getPreferences()
    {
        $user = Auth::user();

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
        $request->validate(['password' => 'required']);

        $user = Auth::user();

        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Incorrect password'
            ], 403);
        }

        if ($user->photo_url) {
            $oldPath = str_replace(Storage::url(''), '', $user->photo_url);
            Storage::disk('public')->delete($oldPath);
        }

        $user->load('books.transactions');
        foreach ($user->books as $book) {
            foreach ($book->transactions as $t) {
                if ($t->image_url) {
                    $oldPath = str_replace(Storage::url(''), '', $t->image_url);
                    Storage::disk('public')->delete($oldPath);
                }
            }
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Account deleted successfully'
        ]);
    }

    /**
     * Get all users (Admin only)
     */
    public function index()
    {
        $users = User::withCount(['books'])->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }

    /**
     * Single user show (Admin only)
     */
    public function show(User $user)
    {
        $user->load(['books.wallets', 'books.categories']);

        return response()->json([
            'success' => true,
            'data' => $user
        ]);
    }
}

