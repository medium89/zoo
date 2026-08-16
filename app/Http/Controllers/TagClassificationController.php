<?php

namespace App\Http\Controllers;

use App\Services\AitunnelService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class TagClassificationController extends Controller
{
    public function __invoke(Request $request, AitunnelService $aitunnel): JsonResponse
    {
        $data = $request->validate([
            'tag' => ['required', 'string', 'max:60'],
        ]);

        try {
            return response()->json($aitunnel->classifyTag($data['tag']));
        } catch (Throwable) {
            return response()->json([
                'type' => 'negative',
                'reason' => 'Не удалось определить автоматически — проверьте тег вручную.',
                'fallback' => true,
            ]);
        }
    }
}
