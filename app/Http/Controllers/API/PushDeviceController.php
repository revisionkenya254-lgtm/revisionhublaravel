<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\PushDeviceToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PushDeviceController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        if (auth()->user()->role !== 'student') {
            return response()->json(['status' => 'error', 'message' => 'Only student accounts can register push devices.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'token' => ['required', 'string', 'max:4096'],
            'platform' => ['required', 'in:android'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors(),
            ], 422);
        }

        $token = $validator->validated()['token'];
        PushDeviceToken::query()->updateOrCreate(
            ['token_hash' => hash('sha256', $token)],
            [
                'user_id' => auth()->id(),
                'token' => $token,
                'platform' => 'android',
                'last_registered_at' => now(),
            ]
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Push device registered.',
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'token' => ['required', 'string', 'max:4096'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors(),
            ], 422);
        }

        PushDeviceToken::query()
            ->where('user_id', auth()->id())
            ->where('token_hash', hash('sha256', $validator->validated()['token']))
            ->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Push device unregistered.',
        ]);
    }
}
