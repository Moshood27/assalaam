<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\AssalaamScoreService;

class ScoreController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();
        $service = app(AssalaamScoreService::class);
        $score = $service->scoreForUser($user);
        $score['tips'] = $service->getScoreTips($user);
        return response()->json($score);
    }
}
