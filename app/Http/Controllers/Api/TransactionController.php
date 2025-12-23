<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Exception;

class TransactionController extends Controller
{
    /**
     * Display a listing of transactions with filters
     */
    public function index(Request $request)
    {
        try {
            $bookId = $request->query('book_id');
            $walletId = $request->query('wallet_id');
            $categoryId = $request->query('category_id');
            $type = $request->query('type');
            $startDate = $request->query('start_date');
            $endDate = $request->query('end_date');
            $search = $request->query('search');
            $perPage = $request->query('per_page', 15);
            $page = $request->query('page', 1);
            $userId = Auth::id();

            // Build WHERE conditions
            $conditions = [];
            $params = [];

            if ($bookId) {
                // Check authorization
                $book = DB::selectOne("
                    SELECT * FROM books 
                    WHERE id = ? AND user_id = ?
                ", [$bookId, $userId]);

                if (!$book) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Unauthorized or book not found'
                    ], 403);
                }

                $conditions[] = "t.book_id = ?";
                $params[] = $bookId;
            } else {
                // Get transactions from user's books only
                $conditions[] = "b.user_id = ?";
                $params[] = $userId;
            }

            if ($walletId) {
                $conditions[] = "t.wallet_id = ?";
                $params[] = $walletId;
            }

            if ($categoryId) {
                $conditions[] = "t.category_id = ?";
                $params[] = $categoryId;
            }

            if ($type) {
                $conditions[] = "t.type = ?";
                $params[] = $type;
            }

            if ($startDate && $endDate) {
                $conditions[] = "t.created_at_ms BETWEEN ? AND ?";
                $params[] = $startDate;
                $params[] = $endDate;
            }

            if ($search) {
                $conditions[] = "t.note LIKE ?";
                $params[] = '%' . $search . '%';
            }

            $whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

            if ($request->query('no_pagination')) {
                // Untuk keperluan Sync / List tanpa pagination
                $transactions = DB::select("
                    SELECT 
                        t.*,
                        w.name as wallet_name,
                        w.type as wallet_type,
                        c.name as category_name,
                        c.type as category_type,
                        c.color as category_color,
                        c.icon as category_icon
                    FROM transactions t
                    INNER JOIN books b ON t.book_id = b.id
                    LEFT JOIN wallets w ON t.wallet_id = w.id
                    LEFT JOIN categories c ON t.category_id = c.id
                    $whereClause
                    ORDER BY t.created_at_ms DESC
                ", $params);

                return response()->json([
                    'success' => true,
                    'data' => $transactions
                ]);
            }

            // Get total count
            $totalQuery = "
                SELECT COUNT(*) as total
                FROM transactions t
                INNER JOIN books b ON t.book_id = b.id
                $whereClause
            ";
            $total = DB::selectOne($totalQuery, $params)->total;

            // Get paginated data
            $offset = ($page - 1) * $perPage;
            $dataQuery = "
                SELECT 
                    t.*,
                    w.name as wallet_name,
                    w.type as wallet_type,
                    c.name as category_name,
                    c.type as category_type,
                    c.color as category_color,
                    c.icon as category_icon
                FROM transactions t
                INNER JOIN books b ON t.book_id = b.id
                LEFT JOIN wallets w ON t.wallet_id = w.id
                LEFT JOIN categories c ON t.category_id = c.id
                $whereClause
                ORDER BY t.created_at_ms DESC
                LIMIT ? OFFSET ?
            ";
            $params[] = $perPage;
            $params[] = $offset;

            $transactions = DB::select($dataQuery, $params);

            return response()->json([
                'success' => true,
                'data' => [
                    'data' => $transactions,
                    'current_page' => (int)$page,
                    'per_page' => (int)$perPage,
                    'total' => (int)$total,
                    'last_page' => ceil($total / $perPage),
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve transactions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created transaction
     */
    public function store(Request $request)
    {
        try {
            // Mapping camelCase inputs (from Android) to snake_case
            $input = $request->all();
            
            // Map keys
            $mappings = [
                'walletId' => 'wallet_id',
                'categoryId' => 'category_id',
                'createdAt' => 'created_at_ms',
                'lastSyncAt' => 'last_sync_at',
                'serverId' => 'server_id',
            ];

            foreach ($mappings as $camel => $snake) {
                if (isset($input[$camel]) && !isset($input[$snake])) {
                    $input[$snake] = $input[$camel];
                }
            }
            
            // Replace request input with mapped data
            $request->replace($input);

            // Infer book_id from wallet_id if not present
            if (!$request->has('book_id') && $request->has('wallet_id')) {
                $walletAndBook = DB::table('wallets')
                    ->where('id', $request->input('wallet_id'))
                    ->select('book_id')
                    ->first();
                    
                if ($walletAndBook) {
                    $request->merge(['book_id' => $walletAndBook->book_id]);
                }
            }

            $validated = $request->validate([
                'book_id' => 'required|exists:books,id',
                'wallet_id' => 'required|exists:wallets,id',
                'category_id' => 'required|exists:categories,id',
                'type' => 'required|in:PEMASUKAN,PENGELUARAN,TRANSFER',
                'amount' => 'required|integer|min:0',
                'note' => 'nullable|string|max:500',
                'created_at_ms' => 'nullable|integer',
                'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
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

            // Set timestamp jika tidak ada
            if (!isset($validated['created_at_ms'])) {
                $validated['created_at_ms'] = round(microtime(true) * 1000);
            }

            // Handle image upload
            $imageUrl = null;
            if ($request->hasFile('image')) {
                $path = $request->file('image')->store('transactions', 'public');
                $imageUrl = Storage::url($path);
            }

            // Insert transaction
            $transactionId = DB::table('transactions')->insertGetId([
                'book_id' => $validated['book_id'],
                'wallet_id' => $validated['wallet_id'],
                'category_id' => $validated['category_id'],
                'type' => $validated['type'],
                'amount' => $validated['amount'],
                'note' => $validated['note'] ?? null,
                'created_at_ms' => $validated['created_at_ms'],
                'image_url' => $imageUrl,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Get created transaction with relations
            $transaction = DB::selectOne("
                SELECT 
                    t.*,
                    t.id as server_id,
                    w.name as wallet_name,
                    c.name as category_name
                FROM transactions t
                LEFT JOIN wallets w ON t.wallet_id = w.id
                LEFT JOIN categories c ON t.category_id = c.id
                WHERE t.id = ?
            ", [$transactionId]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transaction created successfully',
                'data' => $transaction
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
                'message' => 'Failed to create transaction',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified transaction
     */
    public function show($id)
    {
        try {
            $userId = Auth::id();

            // Get transaction with authorization check
            $transaction = DB::selectOne("
                SELECT 
                    t.*,
                    w.name as wallet_name,
                    w.type as wallet_type,
                    c.name as category_name,
                    c.type as category_type,
                    c.color as category_color,
                    b.name as book_name
                FROM transactions t
                INNER JOIN books b ON t.book_id = b.id
                LEFT JOIN wallets w ON t.wallet_id = w.id
                LEFT JOIN categories c ON t.category_id = c.id
                WHERE t.id = ? AND b.user_id = ?
            ", [$id, $userId]);

            if (!$transaction) {
                return response()->json([
                    'success' => false,
                    'message' => 'Transaction not found or unauthorized'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $transaction
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve transaction',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified transaction
     */
    public function update(Request $request, $id)
    {
        try {
            $userId = Auth::id();

            // Check authorization
            $transaction = DB::selectOne("
                SELECT t.*
                FROM transactions t
                INNER JOIN books b ON t.book_id = b.id
                WHERE t.id = ? AND b.user_id = ?
            ", [$id, $userId]);

            if (!$transaction) {
                return response()->json([
                    'success' => false,
                    'message' => 'Transaction not found or unauthorized'
                ], 404);
            }

            // Mapping camelCase inputs (from Android) to snake_case
            $input = $request->all();
            
            // Map keys
            $mappings = [
                'walletId' => 'wallet_id',
                'categoryId' => 'category_id',
                'createdAt' => 'created_at_ms',
            ];

            foreach ($mappings as $camel => $snake) {
                if (isset($input[$camel]) && !isset($input[$snake])) {
                    $input[$snake] = $input[$camel];
                }
            }
            
            // Replace request input with mapped data
            $request->replace($input);

            $validated = $request->validate([
                'wallet_id' => 'sometimes|exists:wallets,id',
                'category_id' => 'sometimes|exists:categories,id',
                'type' => 'sometimes|in:PEMASUKAN,PENGELUARAN,TRANSFER',
                'amount' => 'sometimes|integer|min:0',
                'note' => 'nullable|string|max:500',
                'created_at_ms' => 'sometimes|integer',
                'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            ]);

            DB::beginTransaction();

            // Handle image upload
            $imageUrl = null;
            if ($request->hasFile('image')) {
                // Delete old image if exists
                if ($transaction->image_url) {
                    $oldPath = str_replace('/storage/', '', $transaction->image_url);
                    Storage::disk('public')->delete($oldPath);
                }

                $path = $request->file('image')->store('transactions', 'public');
                $imageUrl = Storage::url($path);
            }

            // Build update query dynamically
            $updateFields = [];
            $params = [];

            if (isset($validated['wallet_id'])) {
                $updateFields[] = "wallet_id = ?";
                $params[] = $validated['wallet_id'];
            }
            if (isset($validated['category_id'])) {
                $updateFields[] = "category_id = ?";
                $params[] = $validated['category_id'];
            }
            if (isset($validated['type'])) {
                $updateFields[] = "type = ?";
                $params[] = $validated['type'];
            }
            if (isset($validated['amount'])) {
                $updateFields[] = "amount = ?";
                $params[] = $validated['amount'];
            }
            if (array_key_exists('note', $validated)) {
                $updateFields[] = "note = ?";
                $params[] = $validated['note'];
            }
            if (isset($validated['created_at_ms'])) {
                $updateFields[] = "created_at_ms = ?";
                $params[] = $validated['created_at_ms'];
            }
            if ($imageUrl) {
                $updateFields[] = "image_url = ?";
                $params[] = $imageUrl;
            }

            $updateFields[] = "updated_at = ?";
            $params[] = now();
            $params[] = $id;

            if (!empty($updateFields)) {
                DB::update("
                    UPDATE transactions 
                    SET " . implode(', ', $updateFields) . "
                    WHERE id = ?
                ", $params);
            }

            // Get updated transaction
            $updatedTransaction = DB::selectOne("
                SELECT 
                    t.*,
                    w.name as wallet_name,
                    c.name as category_name
                FROM transactions t
                LEFT JOIN wallets w ON t.wallet_id = w.id
                LEFT JOIN categories c ON t.category_id = c.id
                WHERE t.id = ?
            ", [$id]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transaction updated successfully',
                'data' => $updatedTransaction
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
                'message' => 'Failed to update transaction',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified transaction
     */
    public function destroy($id)
    {
        try {
            $userId = Auth::id();

            // Check authorization
            $transaction = DB::selectOne("
                SELECT t.*
                FROM transactions t
                INNER JOIN books b ON t.book_id = b.id
                WHERE t.id = ? AND b.user_id = ?
            ", [$id, $userId]);

            if (!$transaction) {
                return response()->json([
                    'success' => false,
                    'message' => 'Transaction not found or unauthorized'
                ], 404);
            }

            DB::beginTransaction();

            // Delete image if exists
            if ($transaction->image_url) {
                $oldPath = str_replace('/storage/', '', $transaction->image_url);
                Storage::disk('public')->delete($oldPath);
            }

            // Delete transaction
            DB::delete("
                DELETE FROM transactions 
                WHERE id = ?
            ", [$id]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transaction deleted successfully'
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete transaction',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get transaction summary (income, expense, balance)
     */
    public function summary(Request $request)
    {
        try {
            $bookId = $request->query('book_id');
            $walletId = $request->query('wallet_id');
            $startDate = $request->query('start_date');
            $endDate = $request->query('end_date');
            $userId = Auth::id();

            // Build WHERE conditions
            $conditions = [];
            $params = [];

            if ($bookId) {
                // Check authorization
                $book = DB::selectOne("
                    SELECT * FROM books 
                    WHERE id = ? AND user_id = ?
                ", [$bookId, $userId]);

                if (!$book) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Unauthorized or book not found'
                    ], 403);
                }

                $conditions[] = "t.book_id = ?";
                $params[] = $bookId;
            } else {
                $conditions[] = "b.user_id = ?";
                $params[] = $userId;
            }

            if ($walletId) {
                $conditions[] = "t.wallet_id = ?";
                $params[] = $walletId;
            }

            if ($startDate && $endDate) {
                $conditions[] = "t.created_at_ms BETWEEN ? AND ?";
                $params[] = $startDate;
                $params[] = $endDate;
            }

            $whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

            // Get summary
            $summary = DB::selectOne("
                SELECT 
                    COALESCE(SUM(CASE WHEN t.type = 'PEMASUKAN' THEN t.amount ELSE 0 END), 0) as income,
                    COALESCE(SUM(CASE WHEN t.type = 'PENGELUARAN' THEN t.amount ELSE 0 END), 0) as expense
                FROM transactions t
                INNER JOIN books b ON t.book_id = b.id
                $whereClause
            ", $params);

            $balance = $summary->income - $summary->expense;

            return response()->json([
                'success' => true,
                'data' => [
                    'income' => (int)$summary->income,
                    'expense' => (int)$summary->expense,
                    'balance' => (int)$balance,
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get summary',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get transactions grouped by category
     */
    public function byCategory(Request $request)
    {
        try {
            $bookId = $request->query('book_id');
            $type = $request->query('type');
            $startDate = $request->query('start_date');
            $endDate = $request->query('end_date');
            $userId = Auth::id();

            // Build WHERE conditions
            $conditions = [];
            $params = [];

            if ($bookId) {
                // Check authorization
                $book = DB::selectOne("
                    SELECT * FROM books 
                    WHERE id = ? AND user_id = ?
                ", [$bookId, $userId]);

                if (!$book) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Unauthorized or book not found'
                    ], 403);
                }

                $conditions[] = "t.book_id = ?";
                $params[] = $bookId;
            } else {
                $conditions[] = "b.user_id = ?";
                $params[] = $userId;
            }

            if ($type) {
                $conditions[] = "t.type = ?";
                $params[] = $type;
            }

            if ($startDate && $endDate) {
                $conditions[] = "t.created_at_ms BETWEEN ? AND ?";
                $params[] = $startDate;
                $params[] = $endDate;
            }

            $whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

            // Get grouped data
            $data = DB::select("
                SELECT 
                    t.category_id,
                    c.name as category_name,
                    c.type as category_type,
                    c.color as category_color,
                    c.icon as category_icon,
                    SUM(t.amount) as total_amount,
                    COUNT(*) as transaction_count
                FROM transactions t
                INNER JOIN books b ON t.book_id = b.id
                LEFT JOIN categories c ON t.category_id = c.id
                $whereClause
                GROUP BY t.category_id, c.name, c.type, c.color, c.icon
                ORDER BY total_amount DESC
            ", $params);

            return response()->json([
                'success' => true,
                'data' => $data
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get transactions by category',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get transactions grouped by date
     */
    public function byDate(Request $request)
    {
        try {
            $bookId = $request->query('book_id');
            $walletId = $request->query('wallet_id');
            $startDate = $request->query('start_date');
            $endDate = $request->query('end_date');
            $userId = Auth::id();

            // Build WHERE conditions
            $conditions = [];
            $params = [];

            if ($bookId) {
                // Check authorization
                $book = DB::selectOne("
                    SELECT * FROM books 
                    WHERE id = ? AND user_id = ?
                ", [$bookId, $userId]);

                if (!$book) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Unauthorized or book not found'
                    ], 403);
                }

                $conditions[] = "t.book_id = ?";
                $params[] = $bookId;
            } else {
                $conditions[] = "b.user_id = ?";
                $params[] = $userId;
            }

            if ($walletId) {
                $conditions[] = "t.wallet_id = ?";
                $params[] = $walletId;
            }

            if ($startDate && $endDate) {
                $conditions[] = "t.created_at_ms BETWEEN ? AND ?";
                $params[] = $startDate;
                $params[] = $endDate;
            }

            $whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

            // Get transactions
            $transactions = DB::select("
                SELECT 
                    t.*,
                    w.name as wallet_name,
                    c.name as category_name,
                    c.color as category_color,
                    DATE(FROM_UNIXTIME(t.created_at_ms / 1000)) as transaction_date
                FROM transactions t
                INNER JOIN books b ON t.book_id = b.id
                LEFT JOIN wallets w ON t.wallet_id = w.id
                LEFT JOIN categories c ON t.category_id = c.id
                $whereClause
                ORDER BY t.created_at_ms DESC
            ", $params);

            // Group by date in PHP
            $grouped = [];
            foreach ($transactions as $transaction) {
                $date = $transaction->transaction_date;
                if (!isset($grouped[$date])) {
                    $grouped[$date] = [];
                }
                $grouped[$date][] = $transaction;
            }

            return response()->json([
                'success' => true,
                'data' => $grouped
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get transactions by date',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk delete transactions
     */
    public function bulkDelete(Request $request)
    {
        try {
            $validated = $request->validate([
                'transaction_ids' => 'required|array',
                'transaction_ids.*' => 'exists:transactions,id',
            ]);

            $userId = Auth::id();
            $ids = $validated['transaction_ids'];
            $placeholders = implode(',', array_fill(0, count($ids), '?'));

            // Get transactions with authorization check
            $params = array_merge($ids, [$userId]);
            $transactions = DB::select("
                SELECT t.*
                FROM transactions t
                INNER JOIN books b ON t.book_id = b.id
                WHERE t.id IN ($placeholders) AND b.user_id = ?
            ", $params);

            if (count($transactions) !== count($ids)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Some transactions not found or unauthorized'
                ], 403);
            }

            DB::beginTransaction();

            // Delete images
            foreach ($transactions as $transaction) {
                if ($transaction->image_url) {
                    $oldPath = str_replace('/storage/', '', $transaction->image_url);
                    Storage::disk('public')->delete($oldPath);
                }
            }

            // Delete transactions
            DB::delete("
                DELETE FROM transactions 
                WHERE id IN ($placeholders)
            ", $ids);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transactions deleted successfully'
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
                'message' => 'Failed to delete transactions',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}