<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Exception;

class WalletController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $bookId = $request->query('book_id');
            $userId = Auth::id();

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

                // Get wallets by book_id
                $wallets = DB::select("
                    SELECT 
                        w.*,
                        (SELECT COUNT(*) FROM transactions t WHERE t.wallet_id = w.id) as transactions_count
                    FROM wallets w
                    WHERE w.book_id = ?
                    ORDER BY w.is_default DESC, w.created_at DESC
                ", [$bookId]);
            } else {
                // Get wallets from user's books
                $wallets = DB::select("
                    SELECT 
                        w.*,
                        b.name as book_name,
                        (SELECT COUNT(*) FROM transactions t WHERE t.wallet_id = w.id) as transactions_count
                    FROM wallets w
                    INNER JOIN books b ON w.book_id = b.id
                    WHERE b.user_id = ?
                    ORDER BY w.is_default DESC, w.created_at DESC
                ", [$userId]);
            }

            return response()->json([
                'success' => true,
                'data' => $wallets
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve wallets',
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
                'bookId' => 'book_id',
                'initialBalance' => 'initial_balance',
                'isDefault' => 'is_default',
                'serverId' => 'server_id',
                'createdAt' => 'created_at',
                'updatedAt' => 'updated_at',
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
                'type' => 'required|in:CASH,BANK,E_WALLET',
                'icon' => 'nullable|string|max:10',
                'color' => 'nullable|string|max:7',
                'initial_balance' => 'nullable|numeric|min:0',
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

            $isDefault = $validated['is_default'] ?? false;

            // Jika is_default true, set semua wallet lain di book ini jadi false
            if ($isDefault) {
                DB::update("
                    UPDATE wallets 
                    SET is_default = 0 
                    WHERE book_id = ?
                ", [$validated['book_id']]);
            }

            // 1. Cek Duplikat (Idempotency Key: book_id + name + type)
            $existing = DB::table('wallets')
                ->where('book_id', $validated['book_id'])
                ->where('name', $validated['name'])
                ->where('type', $validated['type'])
                ->first();

            if ($existing) {
                // Jika sudah ada, return data lama dengan ID servernya
                $existing->server_id = $existing->id;
                return response()->json([
                    'success' => true,
                    'message' => 'Wallet already exists',
                    'data' => $existing
                ], 200);
            }

            // 2. Jika belum ada, baru insert
            $initialBalance = $validated['initial_balance'] ?? 0;
            $walletId = DB::table('wallets')->insertGetId([
                'book_id' => $validated['book_id'],
                'name' => $validated['name'],
                'type' => $validated['type'],
                'icon' => $validated['icon'] ?? null,
                'color' => $validated['color'] ?? null,
                'initial_balance' => $initialBalance,
                'current_balance' => $initialBalance, // Set current_balance sama dengan initial_balance
                'is_default' => $isDefault,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Get created wallet
            $wallet = DB::selectOne("
                SELECT *, id as server_id FROM wallets WHERE id = ?
            ", [$walletId]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Wallet created successfully',
                'data' => $wallet
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
                'message' => 'Failed to create wallet',
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

            // Get wallet with authorization check
            $wallet = DB::selectOne("
                SELECT w.*
                FROM wallets w
                INNER JOIN books b ON w.book_id = b.id
                WHERE w.id = ? AND b.user_id = ?
            ", [$id, $userId]);

            if (!$wallet) {
                return response()->json([
                    'success' => false,
                    'message' => 'Wallet not found or unauthorized'
                ], 404);
            }

            // Get transactions
            $transactions = DB::select("
                SELECT 
                    t.*,
                    c.name as category_name,
                    c.color as category_color
                FROM transactions t
                LEFT JOIN categories c ON t.category_id = c.id
                WHERE t.wallet_id = ?
                ORDER BY t.created_at_ms DESC
                LIMIT 20
            ", [$id]);

            $wallet->transactions = $transactions;
            $wallet->transactions_count = count($transactions);

            return response()->json([
                'success' => true,
                'data' => $wallet
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve wallet',
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
            $wallet = DB::selectOne("
                SELECT w.*, b.user_id
                FROM wallets w
                INNER JOIN books b ON w.book_id = b.id
                WHERE w.id = ? AND b.user_id = ?
            ", [$id, $userId]);

            if (!$wallet) {
                return response()->json([
                    'success' => false,
                    'message' => 'Wallet not found or unauthorized'
                ], 404);
            }

            // Mapping camelCase inputs (from Android) to snake_case
            $input = $request->all();
            
            // Map keys
            $mappings = [
                'initialBalance' => 'initial_balance',
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
                'type' => 'sometimes|in:CASH,BANK,E_WALLET',
                'icon' => 'nullable|string|max:10',
                'color' => 'nullable|string|max:7',
                'initial_balance' => 'nullable|numeric|min:0',
                'is_default' => 'boolean',
            ]);

            DB::beginTransaction();

            // Jika is_default true, set semua wallet lain di book ini jadi false
            if (isset($validated['is_default']) && $validated['is_default']) {
                DB::update("
                    UPDATE wallets 
                    SET is_default = 0 
                    WHERE book_id = ? AND id != ?
                ", [$wallet->book_id, $id]);
            }

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
            if (array_key_exists('icon', $validated)) {
                $updateFields[] = "icon = ?";
                $params[] = $validated['icon'];
            }
            if (array_key_exists('color', $validated)) {
                $updateFields[] = "color = ?";
                $params[] = $validated['color'];
            }
            if (isset($validated['initial_balance'])) {
                $updateFields[] = "initial_balance = ?";
                $params[] = $validated['initial_balance'];
                
                // Update current_balance juga jika initial_balance berubah
                // Hitung selisih dan adjust current_balance
                $difference = $validated['initial_balance'] - $wallet->initial_balance;
                $newCurrentBalance = $wallet->current_balance + $difference;
                
                $updateFields[] = "current_balance = ?";
                $params[] = $newCurrentBalance;
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
                    UPDATE wallets 
                    SET " . implode(', ', $updateFields) . "
                    WHERE id = ?
                ", $params);
            }

            // Get updated wallet
            $updatedWallet = DB::selectOne("
                SELECT * FROM wallets WHERE id = ?
            ", [$id]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Wallet updated successfully',
                'data' => $updatedWallet
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
                'message' => 'Failed to update wallet',
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
            $wallet = DB::selectOne("
                SELECT w.*
                FROM wallets w
                INNER JOIN books b ON w.book_id = b.id
                WHERE w.id = ? AND b.user_id = ?
            ", [$id, $userId]);

            if (!$wallet) {
                return response()->json([
                    'success' => false,
                    'message' => 'Wallet not found or unauthorized'
                ], 404);
            }

            // Check if wallet has transactions
            $transactionCount = DB::selectOne("
                SELECT COUNT(*) as total 
                FROM transactions 
                WHERE wallet_id = ?
            ", [$id]);

            if ($transactionCount->total > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete wallet with existing transactions'
                ], 400);
            }

            DB::beginTransaction();

            // Delete wallet
            DB::delete("
                DELETE FROM wallets 
                WHERE id = ?
            ", [$id]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Wallet deleted successfully'
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete wallet',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}