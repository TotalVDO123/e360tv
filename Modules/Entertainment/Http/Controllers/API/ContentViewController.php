<?php

namespace Modules\Entertainment\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Entertainment\Http\Requests\SaveContentViewRequest;
use Modules\Entertainment\Services\ContentViewService;

class ContentViewController extends Controller
{
    public function __construct(protected ContentViewService $contentViewService)
    {
    }

    public function store(SaveContentViewRequest $request): JsonResponse
    {
        $user = $request->user('sanctum') ?? $request->user();
        $userId = $user?->id ? (int) $user->id : null;
        if (! $userId && $request->filled('user_id')) {
            $userId = (int) $request->input('user_id');
        }

        $result = $this->contentViewService->recordFromRequest($request, $userId);

        $payload = [
            'status' => $result['ok'],
            'message' => $result['message'],
        ];

        if (! empty($result['data'])) {
            $payload['data'] = $result['data'];
        }

        return response()->json($payload, $result['http']);
    }

    public function summary(Request $request): JsonResponse
    {
        $year = $request->filled('year') ? (int) $request->query('year') : (int) now()->year;

        if ($year < 2000 || $year > 2100) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid year',
            ], 422);
        }

        return response()->json([
            'status' => true,
            'data' => $this->contentViewService->getSummaryPayload($year),
        ]);
    }
}
