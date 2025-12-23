<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;


use App\Models\Book;
use App\Models\User;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        $bookId = $request->query('book_id');
        $type = $request->query('type');
        $userId = Auth::id();

        $query = DB::table('categories')
            ->select('categories.*');

        try {

            if ($bookId) {
                $book = Book::find($bookId);

                if (!$book || $book->user_id !== $userId) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Unauthorized or Book not found'
                    ], 403);
                }

                $query->where('categories.book_id', $bookId);
            } else {

                $query->join('books', 'categories.book_id', '=', 'books.id')
                    ->where('books.user_id', $userId);
            }

            if ($type) {
                $query->where('categories.type', $type);
            }

            $categories = $query->orderBy('categories.name')->get();

            return response()->json([
                'success' => true,
                'data' => $categories
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching data.',
                'error_details' => $e->getMessage()
            ], 500);
        }
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Mapping camelCase inputs (from Android) to snake_case
        $input = $request->all();
        
        // Map keys
        $mappings = [
            'bookId' => 'book_id',
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
            'book_id' => 'required|exists:books,id',
            'name' => 'required|string|max:255',
            'type' => 'required|in:PEMASUKAN,PENGELUARAN,TRANSFER',
            'color' => 'nullable|string|max:7',
            'icon' => 'nullable|string|max:10',
            'is_default' => 'boolean',
        ]);

        // Check authorization
        $book = Book::find($validated['book_id']);
        if ($book->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $validated['created_at_ts'] = time();

        // 1. Cek Duplikat (Idempotency Key: book_id + name + type)
        $existing = Category::where('book_id', $validated['book_id'])
            ->where('name', $validated['name'])
            ->where('type', $validated['type'])
            ->first();

        if ($existing) {
            $data = $existing->toArray();
            $data['server_id'] = $existing->id;

            return response()->json([
                'success' => true,
                'message' => 'Category already exists',
                'data' => $data
            ], 200);
        }

        // 2. Jika belum ada, create baru
        $category = Category::create($validated);
        
        $data = $category->toArray();
        $data['server_id'] = $category->id;

        return response()->json([
            'success' => true,
            'message' => 'Category created successfully',
            'data' => $data
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Category $category)
    {
        // Authorization
        if ($category->book->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $category->load('transactions');

        return response()->json([
            'success' => true,
            'data' => $category
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Category $category)
    {
        // Authorization
        if ($category->book->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
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
            'type' => 'sometimes|in:PEMASUKAN,PENGELUARAN,TRANSFER',
            'color' => 'nullable|string|max:7',
            'icon' => 'nullable|string|max:10',
            'is_default' => 'boolean',
        ]);

        $category->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Category updated successfully',
            'data' => $category
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Category $category)
    {
        // Authorization
        if ($category->book->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        // Cegah hapus jika masih ada transaksi
        if ($category->transactions()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete category with existing transactions'
            ], 400);
        }

        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Category deleted successfully'
        ]);
    }
}
