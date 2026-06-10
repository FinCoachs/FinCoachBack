<?php

namespace App\Http\Controllers\Authentication;

use App\Http\Controllers\Controller;
use App\Services\Authentication\GoogleAuthService;
use Illuminate\Http\Request;

class GoogleController extends Controller
{
    public function __construct(
        private readonly GoogleAuthService $googleAuthService
    ) {}

    public function __invoke(Request $request)
    {
        return $this->googleAuthService->handleGoogleCallback($request);
    }
}
