<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Exception;

class BookController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $userId = Auth::id();
            
            $books = DB::select("
                SELECT b.*, 
                    (SELECT COUNT(*) FROM wallets w WHERE w.book_id = b.id) as wallets_count,
                    (SELECT COUNT(*) FROM categories c WHERE c.book_id = b.id) as categories_count
                FROM books b
                WHERE b.user_id = ?
                ORDER BY b.is_default DESC, b.created_at DESC
            ", [$userId]);

            return response()->json([
                'success' => true,
                'data' => $books
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve books',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            // Mapping camelCase inputs (from Android) to snake_case
            $input = $request->all();
            
            // Map keys
            $mappings = [
                'isDefault' => 'is_default',
                'createdAt' => 'created_at',
                'updatedAt' => 'updated_at',
                'serverId' => 'server_id',
            ];

            foreach ($mappings as $camel => $snake) {
                if (isset($input[$camel]) && !isset($input[$snake])) {
                    $input[$snake] = $input[$camel];
                }
            }
            
            // Replace request input with mapped data
            $request->replace($input);

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'icon' => 'nullable|string|max:10',
                'color' => 'nullable|string|max:7',
                'is_default' => 'boolean',
            ]);

            DB::beginTransaction();

            $userId = Auth::id();
            $isDefault = $validated['is_default'] ?? false;

            // Jika is_default true, set semua book lain jadi false
            if ($isDefault) {
                DB::update("
                    UPDATE books 
                    SET is_default = 0 
                    WHERE user_id = ?
                ", [$userId]);
            }

            // 1. Cek apakah buku dengan nama yang sama SUDAH ADA (untuk mencegah duplikat)
            $existingBook = DB::table('books')
                ->where('user_id', $userId)
                ->where('name', $validated['name'])
                ->first();

            if ($existingBook) {
                // Jika sudah ada, kembalikan buku tersebut (Idempotency)
                return response()->json([
                    'success' => true,
                    'message' => 'Book already exists',
                    'data' => $existingBook,
                    // Tambahkan server_id agar Android bisa mapping
                    'server_id' => $existingBook->id 
                ], 200);
            }

            // 2. Jika belum ada, baru insert
            $bookId = DB::table('books')->insertGetId([
                'user_id' => $userId,
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'icon' => $validated['icon'] ?? null,
                'color' => $validated['color'] ?? null,
                'is_default' => $isDefault,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Get book yang baru dibuat
            $book = DB::selectOne("
                SELECT *, id as server_id FROM books WHERE id = ?
            ", [$bookId]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Book created successfully',
                'data' => $book
            ], 201);

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
                'message' => 'Failed to create book',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        try {
            $userId = Auth::id();

            // Get book dengan relasi
            $book = DB::selectOne("
                SELECT b.*,
                    (SELECT COUNT(*) FROM wallets w WHERE w.book_id = b.id) as wallets_count,
                    (SELECT COUNT(*) FROM categories c WHERE c.book_id = b.id) as categories_count,
                    (SELECT COUNT(*) FROM transactions t WHERE t.book_id = b.id) as transactions_count
                FROM books b
                WHERE b.id = ? AND b.user_id = ?
            ", [$id, $userId]);

            if (!$book) {
                return response()->json([
                    'success' => false,
                    'message' => 'Book not found or unauthorized'
                ], 404);
            }

            // Get wallets
            $wallets = DB::select("
                SELECT * FROM wallets 
                WHERE book_id = ?
                ORDER BY created_at DESC
            ", [$id]);

            // Get categories
            $categories = DB::select("
                SELECT * FROM categories 
                WHERE book_id = ?
                ORDER BY created_at DESC
            ", [$id]);

            // Get transactions
            $transactions = DB::select("
                SELECT * FROM transactions 
                WHERE book_id = ?
                ORDER BY transaction_date DESC
                LIMIT 10
            ", [$id]);

            $book->wallets = $wallets;
            $book->categories = $categories;
            $book->transactions = $transactions;

            return response()->json([
                'success' => true,
                'data' => $book
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve book',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        try {
            $userId = Auth::id();

            // Check apakah book ada dan milik user
            $book = DB::selectOne("
                SELECT * FROM books 
                WHERE id = ? AND user_id = ?
            ", [$id, $userId]);

            if (!$book) {
                return response()->json([
                    'success' => false,
                    'message' => 'Book not found or unauthorized'
                ], 404);
            }

            // Mapping camelCase inputs (from Android) to snake_case
            $input = $request->all();
            
            // Map keys
            $mappings = [
                'isDefault' => 'is_default',
            ];

            foreach ($mappings as $camel => $snake) {
                if (isset($input[$camel]) && !isset($input[$snake])) {
                    $input[$snake] = $input[$camel];
                }
            }
            
            // Replace request input with mapped data
            $request->replace($input);

            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'description' => 'nullable|string',
                'icon' => 'nullable|string|max:10',
                'color' => 'nullable|string|max:7',
                'is_default' => 'boolean',
            ]);

            DB::beginTransaction();

            // Jika is_default true, set semua book lain jadi false
            if (isset($validated['is_default']) && $validated['is_default']) {
                DB::update("
                    UPDATE books 
                    SET is_default = 0 
                    WHERE user_id = ? AND id != ?
                ", [$userId, $id]);
            }

            // Build update query dynamically
            $updateFields = [];
            $params = [];

            if (isset($validated['name'])) {
                $updateFields[] = "name = ?";
                $params[] = $validated['name'];
            }
            if (array_key_exists('description', $validated)) {
                $updateFields[] = "description = ?";
                $params[] = $validated['description'];
            }
            if (array_key_exists('icon', $validated)) {
                $updateFields[] = "icon = ?";
                $params[] = $validated['icon'];
            }
            if (array_key_exists('color', $validated)) {
                $updateFields[] = "color = ?";
                $params[] = $validated['color'];
            }
            if (isset($validated['is_default'])) {
                $updateFields[] = "is_default = ?";
                $params[] = $validated['is_default'];
            }

            $updateFields[] = "updated_at = ?";
            $params[] = now();
            $params[] = $id;
            $params[] = $userId;

            if (!empty($updateFields)) {
                DB::update("
                    UPDATE books 
                    SET " . implode(', ', $updateFields) . "
                    WHERE id = ? AND user_id = ?
                ", $params);
            }

            // Get updated book
            $updatedBook = DB::selectOne("
                SELECT * FROM books WHERE id = ?
            ", [$id]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Book updated successfully',
                'data' => $updatedBook
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
                'message' => 'Failed to update book',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            $userId = Auth::id();

            // Check apakah book ada dan milik user
            $book = DB::selectOne("
                SELECT * FROM books 
                WHERE id = ? AND user_id = ?
            ", [$id, $userId]);

            if (!$book) {
                return response()->json([
                    'success' => false,
                    'message' => 'Book not found or unauthorized'
                ], 404);
            }

            // Cegah hapus jika book default dan hanya punya 1 book
            if ($book->is_default) {
                $bookCount = DB::selectOne("
                    SELECT COUNT(*) as total FROM books 
                    WHERE user_id = ?
                ", [$userId]);

                if ($bookCount->total == 1) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Cannot delete the only default book'
                    ], 400);
                }
            }

            DB::beginTransaction();

            // Delete book
            DB::delete("
                DELETE FROM books 
                WHERE id = ? AND user_id = ?
            ", [$id, $userId]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Book deleted successfully'
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete book',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}