<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\BookClosing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BookClosingController extends Controller
{
    public function index(Request $request)
    {
        // Get all closings from all books owned by user
        $bookIds = $request->user()->books()->pluck('id');
        
        $closings = BookClosing::whereIn('book_id', $bookIds)
            ->where('is_deleted', false) // Optionally filter deleted
            ->get();

        return response()->json([
            'success' => true,
            'data' => $closings
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'book_id' => 'required|exists:books,id',
            'period_start' => 'required|numeric',
            'period_end' => 'required|numeric',
            'period_label' => 'required|string',
            'closed_at' => 'required|numeric',
            'final_balance' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Verify ownership
        $book = Book::find($request->book_id);
        if ($book->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $closing = BookClosing::create($request->all());

        return response()->json([
            'success' => true,
            'data' => $closing
        ]);
    }

    public function show(Request $request, $id)
    {
        $closing = BookClosing::find($id);

        if (!$closing) {
            return response()->json(['message' => 'Not found'], 404);
        }

        if ($closing->book->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $closing
        ]);
    }

    public function update(Request $request, $id)
    {
        $closing = BookClosing::find($id);

        if (!$closing) {
            return response()->json(['message' => 'Not found'], 404);
        }

        if ($closing->book->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $closing->update($request->all());

        return response()->json([
            'success' => true,
            'data' => $closing
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $closing = BookClosing::find($id);

        if (!$closing) {
            return response()->json(['message' => 'Not found'], 404);
        }

        if ($closing->book->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Soft delete for sync purposes
        $closing->update(['is_deleted' => true]);
        
        // Or hard delete if desired, but user asked for "sync ready"
        // keeping it simple for now, standard CRUD usually deletes. 
        // But for sync, we usually set is_deleted flags.
        // Let's stick to update is_deleted.
        
        return response()->json([
            'success' => true,
            'message' => 'Book closing marked as deleted'
        ]);
    }
}
