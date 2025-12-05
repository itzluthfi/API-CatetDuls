<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Exception;

class UserController extends Controller
{
    /**
     * Get user profile
     */
    public function profile()
    {
        try {
            $userId = Auth::id();

            // Get user
            $user = DB::selectOne("
                SELECT * FROM users WHERE id = ?
            ", [$userId]);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            // Get books with wallets and categories
            $books = DB::select("
                SELECT 
                    b.*,
                    (SELECT COUNT(*) FROM wallets w WHERE w.book_id = b.id) as wallets_count,
                    (SELECT COUNT(*) FROM categories c WHERE c.book_id = b.id) as categories_count
                FROM books b
                WHERE b.user_id = ?
            ", [$userId]);

            // Get statistics
            $totalBooks = count($books);
            
            $transactionStats = DB::selectOne("
                SELECT COUNT(*) as total_transactions
                FROM transactions t
                INNER JOIN books b ON t.book_id = b.id
                WHERE b.user_id = ?
            ", [$userId]);

            $defaultBook = DB::selectOne("
                SELECT * FROM books 
                WHERE user_id = ? AND is_default = 1
                LIMIT 1
            ", [$userId]);

            $stats = [
                'total_books' => $totalBooks,
                'total_transactions' => (int)$transactionStats->total_transactions,
                'default_book' => $defaultBook,
            ];

            // Decode preferences if JSON
            if (isset($user->preferences) && is_string($user->preferences)) {
                $user->preferences = json_decode($user->preferences, true);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'user' => $user,
                    'books' => $books,
                    'stats' => $stats,
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve profile',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update user profile
     */
    public function updateProfile(Request $request)
    {
        try {
            $userId = Auth::id();

            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'email' => 'sometimes|email|max:255',
            ]);

            // Check if email already exists (except current user)
            if (isset($validated['email'])) {
                $existingUser = DB::selectOne("
                    SELECT id FROM users 
                    WHERE email = ? AND id != ?
                ", [$validated['email'], $userId]);

                if ($existingUser) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Email already taken',
                        'errors' => ['email' => ['The email has already been taken.']]
                    ], 422);
                }
            }

            DB::beginTransaction();

            // Build update query
            $updateFields = [];
            $params = [];

            if (isset($validated['name'])) {
                $updateFields[] = "name = ?";
                $params[] = $validated['name'];
            }
            if (isset($validated['email'])) {
                $updateFields[] = "email = ?";
                $params[] = $validated['email'];
            }

            if (!empty($updateFields)) {
                $updateFields[] = "updated_at = ?";
                $params[] = now();
                $params[] = $userId;

                DB::update("
                    UPDATE users 
                    SET " . implode(', ', $updateFields) . "
                    WHERE id = ?
                ", $params);
            }

            // Get updated user
            $user = DB::selectOne("
                SELECT * FROM users WHERE id = ?
            ", [$userId]);

            // Decode preferences
            if (isset($user->preferences) && is_string($user->preferences)) {
                $user->preferences = json_decode($user->preferences, true);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully',
                'data' => $user
            ]);

        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update profile',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload profile photo
     */
    public function uploadPhoto(Request $request)
    {
        try {
            $request->validate([
                'photo' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            ]);

            $userId = Auth::id();

            // Get current user
            $user = DB::selectOne("
                SELECT photo_url FROM users WHERE id = ?
            ", [$userId]);

            DB::beginTransaction();

            // Delete old photo
            if ($user->photo_url) {
                $oldPath = str_replace('/storage/', '', $user->photo_url);
                Storage::disk('public')->delete($oldPath);
            }

            // Upload new photo
            $path = $request->file('photo')->store('profiles', 'public');
            $photoUrl = Storage::url($path);

            // Update photo_url
            DB::update("
                UPDATE users 
                SET photo_url = ?, updated_at = ?
                WHERE id = ?
            ", [$photoUrl, now(), $userId]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Photo uploaded successfully',
                'data' => [
                    'photo_url' => $photoUrl
                ]
            ]);

        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload photo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete profile photo
     */
    public function deletePhoto()
    {
        try {
            $userId = Auth::id();

            // Get current user
            $user = DB::selectOne("
                SELECT photo_url FROM users WHERE id = ?
            ", [$userId]);

            if (!$user->photo_url) {
                return response()->json([
                    'success' => false,
                    'message' => 'No photo to delete'
                ], 400);
            }

            DB::beginTransaction();

            // Delete photo file
            $oldPath = str_replace('/storage/', '', $user->photo_url);
            Storage::disk('public')->delete($oldPath);

            // Update database
            DB::update("
                UPDATE users 
                SET photo_url = NULL, updated_at = ?
                WHERE id = ?
            ", [now(), $userId]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Photo deleted successfully'
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete photo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user statistics
     */
    public function statistics()
    {
        try {
            $userId = Auth::id();

            // Get financial statistics
            $financialStats = DB::selectOne("
                SELECT 
                    COUNT(DISTINCT b.id) as total_books,
                    COUNT(t.id) as total_transactions,
                    COALESCE(SUM(CASE WHEN t.type = 'PEMASUKAN' THEN t.amount ELSE 0 END), 0) as total_income,
                    COALESCE(SUM(CASE WHEN t.type = 'PENGELUARAN' THEN t.amount ELSE 0 END), 0) as total_expense
                FROM books b
                LEFT JOIN transactions t ON b.id = t.book_id
                WHERE b.user_id = ?
            ", [$userId]);

            $balance = $financialStats->total_income - $financialStats->total_expense;

            // Get wallets
            $wallets = DB::select("
                SELECT 
                    w.id,
                    w.name,
                    w.type,
                    w.current_balance,
                    b.name as book_name
                FROM wallets w
                INNER JOIN books b ON w.book_id = b.id
                WHERE b.user_id = ?
                ORDER BY w.name
            ", [$userId]);

            return response()->json([
                'success' => true,
                'data' => [
                    'total_books' => (int)$financialStats->total_books,
                    'total_transactions' => (int)$financialStats->total_transactions,
                    'total_income' => (int)$financialStats->total_income,
                    'total_expense' => (int)$financialStats->total_expense,
                    'balance' => (int)$balance,
                    'wallets' => $wallets,
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update user preferences
     */
    public function updatePreferences(Request $request)
    {
        try {
            $userId = Auth::id();

            $validated = $request->validate([
                'currency' => 'sometimes|string|max:10',
                'language' => 'sometimes|string|max:10',
                'theme' => 'sometimes|in:light,dark,auto',
                'notifications_enabled' => 'sometimes|boolean',
            ]);

            // Get current preferences
            $user = DB::selectOne("
                SELECT preferences FROM users WHERE id = ?
            ", [$userId]);

            $preferences = [];
            if ($user->preferences) {
                $preferences = is_string($user->preferences) 
                    ? json_decode($user->preferences, true) 
                    : $user->preferences;
            }

            // Merge with new preferences
            $preferences = array_merge($preferences, $validated);

            DB::beginTransaction();

            // Update preferences
            DB::update("
                UPDATE users 
                SET preferences = ?, updated_at = ?
                WHERE id = ?
            ", [json_encode($preferences), now(), $userId]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Preferences updated successfully',
                'data' => [
                    'preferences' => $preferences
                ]
            ]);

        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update preferences',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get preferences
     */
    public function getPreferences()
    {
        try {
            $userId = Auth::id();

            $user = DB::selectOne("
                SELECT preferences FROM users WHERE id = ?
            ", [$userId]);

            $preferences = [];
            if ($user->preferences) {
                $preferences = is_string($user->preferences) 
                    ? json_decode($user->preferences, true) 
                    : $user->preferences;
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'preferences' => $preferences
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve preferences',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete user account
     */
    public function deleteAccount(Request $request)
    {
        try {
            $request->validate(['password' => 'required']);

            $userId = Auth::id();

            // Get user
            $user = DB::selectOne("
                SELECT * FROM users WHERE id = ?
            ", [$userId]);

            // Check password
            if (!Hash::check($request->password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Incorrect password'
                ], 403);
            }

            DB::beginTransaction();

            // Delete user profile photo
            if ($user->photo_url) {
                $oldPath = str_replace('/storage/', '', $user->photo_url);
                Storage::disk('public')->delete($oldPath);
            }

            // Get all transaction images
            $transactions = DB::select("
                SELECT t.image_url
                FROM transactions t
                INNER JOIN books b ON t.book_id = b.id
                WHERE b.user_id = ? AND t.image_url IS NOT NULL
            ", [$userId]);

            // Delete transaction images
            foreach ($transactions as $transaction) {
                if ($transaction->image_url) {
                    $oldPath = str_replace('/storage/', '', $transaction->image_url);
                    Storage::disk('public')->delete($oldPath);
                }
            }

            // Delete transactions
            DB::delete("
                DELETE t FROM transactions t
                INNER JOIN books b ON t.book_id = b.id
                WHERE b.user_id = ?
            ", [$userId]);

            // Delete categories
            DB::delete("
                DELETE c FROM categories c
                INNER JOIN books b ON c.book_id = b.id
                WHERE b.user_id = ?
            ", [$userId]);

            // Delete wallets
            DB::delete("
                DELETE w FROM wallets w
                INNER JOIN books b ON w.book_id = b.id
                WHERE b.user_id = ?
            ", [$userId]);

            // Delete books
            DB::delete("
                DELETE FROM books WHERE user_id = ?
            ", [$userId]);

            // Delete user tokens
            DB::delete("
                DELETE FROM personal_access_tokens WHERE tokenable_id = ?
            ", [$userId]);

            // Delete user
            DB::delete("
                DELETE FROM users WHERE id = ?
            ", [$userId]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Account deleted successfully'
            ]);

        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete account',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all users (Admin only)
     */
    public function index()
    {
        try {
            $perPage = request()->query('per_page', 15);
            $page = request()->query('page', 1);
            $offset = ($page - 1) * $perPage;

            // Get total count
            $total = DB::selectOne("
                SELECT COUNT(*) as total FROM users
            ")->total;

            // Get users with books count
            $users = DB::select("
                SELECT 
                    u.*,
                    (SELECT COUNT(*) FROM books b WHERE b.user_id = u.id) as books_count
                FROM users u
                ORDER BY u.created_at DESC
                LIMIT ? OFFSET ?
            ", [$perPage, $offset]);

            // Decode preferences for each user
            foreach ($users as $user) {
                if (isset($user->preferences) && is_string($user->preferences)) {
                    $user->preferences = json_decode($user->preferences, true);
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'data' => $users,
                    'current_page' => (int)$page,
                    'per_page' => (int)$perPage,
                    'total' => (int)$total,
                    'last_page' => ceil($total / $perPage),
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve users',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Single user show (Admin only)
     */
    public function show($id)
    {
        try {
            // Get user
            $user = DB::selectOne("
                SELECT * FROM users WHERE id = ?
            ", [$id]);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            // Get books with wallets and categories
            $books = DB::select("
                SELECT 
                    b.*,
                    (SELECT COUNT(*) FROM wallets w WHERE w.book_id = b.id) as wallets_count,
                    (SELECT COUNT(*) FROM categories c WHERE c.book_id = b.id) as categories_count
                FROM books b
                WHERE b.user_id = ?
            ", [$id]);

            // Decode preferences
            if (isset($user->preferences) && is_string($user->preferences)) {
                $user->preferences = json_decode($user->preferences, true);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'user' => $user,
                    'books' => $books,
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve user',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}