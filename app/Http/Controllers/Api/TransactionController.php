<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class TransactionController extends Controller
{
    /**
     * Display a listing of transactions with filters
     */
    public function index(Request $request)
    {
        $bookId = $request->query('book_id');
        $walletId = $request->query('wallet_id');
        $categoryId = $request->query('category_id');
        $type = $request->query('type'); // PEMASUKAN / PENGELUARAN
        $startDate = $request->query('start_date'); // timestamp ms
        $endDate = $request->query('end_date'); // timestamp ms
        $search = $request->query('search');
        $perPage = $request->query('per_page', 15);

        $query = Transaction::with(['wallet', 'category']);

        // Filter by book
        if ($bookId) {
            $book = Book::find($bookId);
            if (!$book || $book->user_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }
            $query->where('book_id', $bookId);
        } else {
            // Get transactions from user's books
            $query->whereHas('book', function ($q) {
                $q->where('user_id', Auth::id());
            });
        }

        // Filter by wallet
        if ($walletId) {
            $query->where('wallet_id', $walletId);
        }

        // Filter by category
        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        // Filter by type
        if ($type) {
            $query->where('type', $type);
        }

        // Filter by date range
        if ($startDate && $endDate) {
            $query->whereBetween('created_at_ms', [$startDate, $endDate]);
        }

        // Search by note
        if ($search) {
            $query->where('note', 'like', '%' . $search . '%');
        }

        // Order by date desc
        $query->orderBy('created_at_ms', 'desc');

        $transactions = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $transactions
        ]);
    }

    /**
     * Store a newly created transaction
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'book_id' => 'required|exists:books,id',
            'wallet_id' => 'required|exists:wallets,id',
            'category_id' => 'required|exists:categories,id',
            'type' => 'required|in:PEMASUKAN,PENGELUARAN',
            'amount' => 'required|integer|min:0',
            'note' => 'nullable|string|max:500',
            'created_at_ms' => 'nullable|integer',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        // Check authorization
        $book = Book::find($validated['book_id']);
        if ($book->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        // Set timestamp jika tidak ada
        if (!isset($validated['created_at_ms'])) {
            $validated['created_at_ms'] = round(microtime(true) * 1000);
        }

        // Handle image upload
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('transactions', 'public');
            $validated['image_url'] = Storage::url($path);
        }

        $transaction = Transaction::create($validated);
        $transaction->load(['wallet', 'category']);

        return response()->json([
            'success' => true,
            'message' => 'Transaction created successfully',
            'data' => $transaction
        ], 201);
    }

    /**
     * Display the specified transaction
     */
    public function show(Transaction $transaction)
    {
        // Authorization
        if ($transaction->book->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $transaction->load(['wallet', 'category', 'book']);

        return response()->json([
            'success' => true,
            'data' => $transaction
        ]);
    }

    /**
     * Update the specified transaction
     */
    public function update(Request $request, Transaction $transaction)
    {
        // Authorization
        if ($transaction->book->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $validated = $request->validate([
            'wallet_id' => 'sometimes|exists:wallets,id',
            'category_id' => 'sometimes|exists:categories,id',
            'type' => 'sometimes|in:PEMASUKAN,PENGELUARAN',
            'amount' => 'sometimes|integer|min:0',
            'note' => 'nullable|string|max:500',
            'created_at_ms' => 'sometimes|integer',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($transaction->image_url) {
                $oldPath = str_replace('/storage/', '', $transaction->image_url);
                Storage::disk('public')->delete($oldPath);
            }

            $path = $request->file('image')->store('transactions', 'public');
            $validated['image_url'] = Storage::url($path);
        }

        $transaction->update($validated);
        $transaction->load(['wallet', 'category']);

        return response()->json([
            'success' => true,
            'message' => 'Transaction updated successfully',
            'data' => $transaction
        ]);
    }

    /**
     * Remove the specified transaction
     */
    public function destroy(Transaction $transaction)
    {
        // Authorization
        if ($transaction->book->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        // Delete image if exists
        if ($transaction->image_url) {
            $oldPath = str_replace('/storage/', '', $transaction->image_url);
            Storage::disk('public')->delete($oldPath);
        }

        $transaction->delete();

        return response()->json([
            'success' => true,
            'message' => 'Transaction deleted successfully'
        ]);
    }

    /**
     * Get transaction summary (income, expense, balance)
     */
    public function summary(Request $request)
    {
        $bookId = $request->query('book_id');
        $walletId = $request->query('wallet_id');
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        $query = Transaction::query();

        // Filter by book
        if ($bookId) {
            $book = Book::find($bookId);
            if (!$book || $book->user_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }
            $query->where('book_id', $bookId);
        } else {
            $query->whereHas('book', function ($q) {
                $q->where('user_id', Auth::id());
            });
        }

        // Filter by wallet
        if ($walletId) {
            $query->where('wallet_id', $walletId);
        }

        // Filter by date range
        if ($startDate && $endDate) {
            $query->whereBetween('created_at_ms', [$startDate, $endDate]);
        }

        $income = (clone $query)->where('type', 'PEMASUKAN')->sum('amount');
        $expense = (clone $query)->where('type', 'PENGELUARAN')->sum('amount');
        $balance = $income - $expense;

        return response()->json([
            'success' => true,
            'data' => [
                'income' => $income,
                'expense' => $expense,
                'balance' => $balance,
            ]
        ]);
    }

    /**
     * Get transactions grouped by category
     */
    public function byCategory(Request $request)
    {
        $bookId = $request->query('book_id');
        $type = $request->query('type');
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        $query = Transaction::select(
            'category_id',
            DB::raw('SUM(amount) as total_amount'),
            DB::raw('COUNT(*) as transaction_count')
        )
            ->with('category');

        // Filter by book
        if ($bookId) {
            $book = Book::find($bookId);
            if (!$book || $book->user_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }
            $query->where('book_id', $bookId);
        } else {
            $query->whereHas('book', function ($q) {
                $q->where('user_id', Auth::id());
            });
        }

        // Filter by type
        if ($type) {
            $query->where('type', $type);
        }

        // Filter by date range
        if ($startDate && $endDate) {
            $query->whereBetween('created_at_ms', [$startDate, $endDate]);
        }

        $data = $query->groupBy('category_id')
            ->orderBy('total_amount', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * Get transactions grouped by date
     */
    public function byDate(Request $request)
    {
        $bookId = $request->query('book_id');
        $walletId = $request->query('wallet_id');
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        $query = Transaction::with(['wallet', 'category']);

        // Filter by book
        if ($bookId) {
            $book = Book::find($bookId);
            if (!$book || $book->user_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }
            $query->where('book_id', $bookId);
        } else {
            $query->whereHas('book', function ($q) {
                $q->where('user_id', Auth::id());
            });
        }

        // Filter by wallet
        if ($walletId) {
            $query->where('wallet_id', $walletId);
        }

        // Filter by date range
        if ($startDate && $endDate) {
            $query->whereBetween('created_at_ms', [$startDate, $endDate]);
        }

        $transactions = $query->orderBy('created_at_ms', 'desc')->get();

        // Group by date
        $grouped = $transactions->groupBy(function ($transaction) {
            return date('Y-m-d', $transaction->created_at_ms / 1000);
        });

        return response()->json([
            'success' => true,
            'data' => $grouped
        ]);
    }

    /**
     * Bulk delete transactions
     */
    public function bulkDelete(Request $request)
    {
        $validated = $request->validate([
            'transaction_ids' => 'required|array',
            'transaction_ids.*' => 'exists:transactions,id',
        ]);

        $transactions = Transaction::whereIn('id', $validated['transaction_ids'])
            ->whereHas('book', function ($q) {
                $q->where('user_id', Auth::id());
            })
            ->get();

        if ($transactions->count() !== count($validated['transaction_ids'])) {
            return response()->json([
                'success' => false,
                'message' => 'Some transactions not found or unauthorized'
            ], 403);
        }

        // Delete images
        foreach ($transactions as $transaction) {
            if ($transaction->image_url) {
                $oldPath = str_replace('/storage/', '', $transaction->image_url);
                Storage::disk('public')->delete($oldPath);
            }
        }

        Transaction::whereIn('id', $validated['transaction_ids'])->delete();

        return response()->json([
            'success' => true,
            'message' => 'Transactions deleted successfully'
        ]);
    }
}
