<?php

namespace Javaabu\Activitylog\Tests\TestSupport\Http;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Javaabu\Activitylog\Http\Controllers\ActivityLogsController;

class CustomLogsController extends ActivityLogsController
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(['custom' => true]);
    }
}
