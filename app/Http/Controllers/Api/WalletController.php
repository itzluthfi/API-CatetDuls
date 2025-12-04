<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WalletController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $bookId = $request->query('book_id');

        $query = Wallet::query();

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
            // Get wallets from user's books
            $query->whereHas('book', function ($q) {
                $q->where('user_id', Auth::id());
            });
        }

        $wallets = $query->get();

        return response()->json([
            'success' => true,
            'data' => $wallets
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'book_id' => 'required|exists:books,id',
            'name' => 'required|string|max:255',
            'type' => 'required|in:CASH,BANK,E_WALLET',
            'icon' => 'nullable|string|max:10',
            'color' => 'nullable|string|max:7',
            'initial_balance' => 'nullable|numeric|min:0',
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

        // Jika is_default true, set semua wallet lain di book ini jadi false
        if ($validated['is_default'] ?? false) {
            Wallet::where('book_id', $validated['book_id'])->update(['is_default' => false]);
        }

        $wallet = Wallet::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Wallet created successfully',
            'data' => $wallet
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Wallet $wallet)
    {
        // Authorization
        if ($wallet->book->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $wallet->load('transactions');

        return response()->json([
            'success' => true,
            'data' => $wallet
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Wallet $wallet)
    {
        // Authorization
        if ($wallet->book->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'type' => 'sometimes|in:CASH,BANK,E_WALLET',
            'icon' => 'nullable|string|max:10',
            'color' => 'nullable|string|max:7',
            'initial_balance' => 'nullable|numeric|min:0',
            'is_default' => 'boolean',
        ]);

        // Jika is_default true, set semua wallet lain di book ini jadi false
        if (isset($validated['is_default']) && $validated['is_default']) {
            Wallet::where('book_id', $wallet->book_id)
                ->where('id', '!=', $wallet->id)
                ->update(['is_default' => false]);
        }

        $wallet->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Wallet updated successfully',
            'data' => $wallet
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Wallet $wallet)
    {
        // Authorization
        if ($wallet->book->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $wallet->delete();

        return response()->json([
            'success' => true,
            'message' => 'Wallet deleted successfully'
        ]);
    }
}
