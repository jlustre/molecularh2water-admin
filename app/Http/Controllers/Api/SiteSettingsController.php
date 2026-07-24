<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SettingsService;
use Illuminate\Http\JsonResponse;

class SiteSettingsController extends Controller
{
    public function __construct(
        private readonly SettingsService $settings,
    ) {}

    public function __invoke(): JsonResponse
    {
        return response()->json([
            'data' => $this->settings->publicWebsiteContent(),
        ]);
    }
}
