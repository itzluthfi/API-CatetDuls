<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Category;
use App\Models\Book;
use Exception;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;

class CategoryController extends Controller
{
    public function publicIndex()
    {
        return response()->json([
            'success' => true,
            'data' => Category::all()
        ]);
    }


    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // --- 1. Ambil Parameter dari Request ---
        $bookId = $request->query('book_id');
        $type = $request->query('type'); // PEMASUKAN / PENGELUARAN
        $userId = Auth::id(); // Dapatkan ID pengguna yang sedang login


        // --- 2. Inisiasi Query Builder ---
        $query = DB::table('categories')
            ->select('categories.*'); // Pilih semua kolom dari tabel categories

        try {
            // --- 3. LOGIC FILTERING ---

            if ($bookId) {
                // A. FILTER BERDASARKAN book_id (VERIFIKASI KEPEMILIKAN DULU)
                $book = Book::find($bookId);

                // Cek apakah buku ditemukan DAN milik pengguna yang login
                if (!$book || $book->user_id !== $userId) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Unauthorized or Book not found'
                    ], 403);
                }

                // Tambahkan kondisi WHERE
                $query->where('categories.book_id', $bookId);
            } else {
                // B. FILTER KATEGORI DARI SEMUA BUKU MILIK PENGGUNA SAAT INI

                // Join dengan tabel books untuk memfilter berdasarkan user_id
                $query->join('books', 'categories.book_id', '=', 'books.id')
                    ->where('books.user_id', $userId);
            }

            // C. FILTER BERDASARKAN TYPE (PEMASUKAN / PENGELUARAN)
            if ($type) {
                $query->where('categories.type', $type);
            }

            // --- 4. Eksekusi Query ---
            $categories = $query->orderBy('name')->get();

            // --- 5. Berhasil ---
            return response()->json([
                'success' => true,
                'data' => $categories
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching data.',
                'error_details' => $e->getMessage() // Sertakan detail error untuk debugging (Hapus di mode Production!)
            ], 500);
        }
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'book_id' => 'required|exists:books,id',
                'name' => 'required|string|max:255',
                'type' => 'required|in:PEMASUKAN,PENGELUARAN',
                'color' => 'nullable|string|max:7',
                'icon' => 'nullable|string|max:10',
                'is_default' => 'boolean',
            ]);

            $userId = Auth::id();

            // Check authorization
            $book = DB::selectOne("
                SELECT * FROM books 
                WHERE id = ? AND user_id = ?
            ", [$validated['book_id'], $userId]);

            if (!$book) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized or book not found'
                ], 403);
            }

            DB::beginTransaction();

            // Insert category
            $categoryId = DB::table('categories')->insertGetId([
                'book_id' => $validated['book_id'],
                'name' => $validated['name'],
                'type' => $validated['type'],
                'color' => $validated['color'] ?? null,
                'icon' => $validated['icon'] ?? null,
                'is_default' => $validated['is_default'] ?? false,
                'created_at_ts' => time(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Get created category
            $category = DB::selectOne("
                SELECT * FROM categories WHERE id = ?
            ", [$categoryId]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Category created successfully',
                'data' => $category
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
                'message' => 'Failed to create category',
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

            // Get category with book check
            $category = DB::selectOne("
                SELECT c.*
                FROM categories c
                INNER JOIN books b ON c.book_id = b.id
                WHERE c.id = ? AND b.user_id = ?
            ", [$id, $userId]);

            if (!$category) {
                return response()->json([
                    'success' => false,
                    'message' => 'Category not found or unauthorized'
                ], 404);
            }

            // Get transactions
            $transactions = DB::select("
                SELECT * FROM transactions 
                WHERE category_id = ?
                ORDER BY transaction_date DESC
                LIMIT 10
            ", [$id]);

            $category->transactions = $transactions;
            $category->transactions_count = count($transactions);

            return response()->json([
                'success' => true,
                'data' => $category
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve category',
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

            // Check authorization
            $category = DB::selectOne("
                SELECT c.*
                FROM categories c
                INNER JOIN books b ON c.book_id = b.id
                WHERE c.id = ? AND b.user_id = ?
            ", [$id, $userId]);

            if (!$category) {
                return response()->json([
                    'success' => false,
                    'message' => 'Category not found or unauthorized'
                ], 404);
            }

            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'type' => 'sometimes|in:PEMASUKAN,PENGELUARAN',
                'color' => 'nullable|string|max:7',
                'icon' => 'nullable|string|max:10',
                'is_default' => 'boolean',
            ]);

            DB::beginTransaction();

            // Build update query dynamically
            $updateFields = [];
            $params = [];

            if (isset($validated['name'])) {
                $updateFields[] = "name = ?";
                $params[] = $validated['name'];
            }
            if (isset($validated['type'])) {
                $updateFields[] = "type = ?";
                $params[] = $validated['type'];
            }
            if (array_key_exists('color', $validated)) {
                $updateFields[] = "color = ?";
                $params[] = $validated['color'];
            }
            if (array_key_exists('icon', $validated)) {
                $updateFields[] = "icon = ?";
                $params[] = $validated['icon'];
            }
            if (isset($validated['is_default'])) {
                $updateFields[] = "is_default = ?";
                $params[] = $validated['is_default'];
            }

            $updateFields[] = "updated_at = ?";
            $params[] = now();
            $params[] = $id;

            if (!empty($updateFields)) {
                DB::update("
                    UPDATE categories 
                    SET " . implode(', ', $updateFields) . "
                    WHERE id = ?
                ", $params);
            }

            // Get updated category
            $updatedCategory = DB::selectOne("
                SELECT * FROM categories WHERE id = ?
            ", [$id]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Category updated successfully',
                'data' => $updatedCategory
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
                'message' => 'Failed to update category',
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

            // Check authorization
            $category = DB::selectOne("
                SELECT c.*
                FROM categories c
                INNER JOIN books b ON c.book_id = b.id
                WHERE c.id = ? AND b.user_id = ?
            ", [$id, $userId]);

            if (!$category) {
                return response()->json([
                    'success' => false,
                    'message' => 'Category not found or unauthorized'
                ], 404);
            }

            // Cegah hapus jika masih ada transaksi
            $transactionCount = DB::selectOne("
                SELECT COUNT(*) as total 
                FROM transactions 
                WHERE category_id = ?
            ", [$id]);

            if ($transactionCount->total > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete category with existing transactions'
                ], 400);
            }

            DB::beginTransaction();

            // Delete category
            DB::delete("
                DELETE FROM categories 
                WHERE id = ?
            ", [$id]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Category deleted successfully'
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete category',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}