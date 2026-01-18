<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Memo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MemoController extends Controller
{
    public function index(Request $request)
    {
        $bookIds = $request->user()->books()->pluck('id');
        
        $memos = Memo::whereIn('book_id', $bookIds)
            ->where('is_deleted', false)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $memos
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'book_id' => 'required|exists:books,id',
            'title' => 'required|string',
            'content' => 'required|string',
            'date' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $book = Book::find($request->book_id);
        if ($book->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $memo = Memo::create($request->all());

        return response()->json([
            'success' => true,
            'data' => $memo
        ]);
    }

    public function show(Request $request, $id)
    {
        $memo = Memo::find($id);

        if (!$memo) {
            return response()->json(['message' => 'Not found'], 404);
        }

        if ($memo->book->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $memo
        ]);
    }

    public function update(Request $request, $id)
    {
        $memo = Memo::find($id);

        if (!$memo) {
            return response()->json(['message' => 'Not found'], 404);
        }

        if ($memo->book->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $memo->update($request->all());

        return response()->json([
            'success' => true,
            'data' => $memo
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $memo = Memo::find($id);

        if (!$memo) {
            return response()->json(['message' => 'Not found'], 404);
        }

        if ($memo->book->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $memo->update(['is_deleted' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Memo marked as deleted'
        ]);
    }
}
