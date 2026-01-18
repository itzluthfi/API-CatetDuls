<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TagController extends Controller
{
    public function index(Request $request)
    {
        $tags = Tag::where('user_id', $request->user()->id)
            ->where('is_deleted', false)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $tags
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'color' => 'integer',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $data = $request->all();
        $data['user_id'] = $request->user()->id;

        $tag = Tag::create($data);

        return response()->json([
            'success' => true,
            'data' => $tag
        ]);
    }

    public function show(Request $request, $id)
    {
        $tag = Tag::where('user_id', $request->user()->id)->find($id);

        if (!$tag) {
            return response()->json(['message' => 'Not found'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $tag
        ]);
    }

    public function update(Request $request, $id)
    {
        $tag = Tag::where('user_id', $request->user()->id)->find($id);

        if (!$tag) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $tag->update($request->all());

        return response()->json([
            'success' => true,
            'data' => $tag
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $tag = Tag::where('user_id', $request->user()->id)->find($id);

        if (!$tag) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $tag->update(['is_deleted' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Tag marked as deleted'
        ]);
    }
}
