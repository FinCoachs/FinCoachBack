<?php

namespace App\Http\Controllers\Compte;

use App\DTOs\Comptes\CreateCompteDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Compte\CompteRequest;
use App\Http\Resources\Compte\CompteResource;
use App\Models\Compte;
use App\Services\Compte\CompteService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class CompteController extends Controller
{
    public function __construct(
        private readonly CompteService $compteService
    ) {}

    public function index(): JsonResponse
    {
        try {
            $comptes = $this->compteService->comptes();

            return response()->json([
                'success' => true,
                'data'    => CompteResource::collection($comptes),
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(CompteRequest $request): JsonResponse
    {
        try {
            $compte = $this->compteService->create(
                CreateCompteDTO::fromRequest($request)
            );

            return response()->json([
                'success' => true,
                'message' => 'Compte créé avec succès',
                'data'    => new CompteResource($compte),
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(string $id): JsonResponse
    {
        try {
            $compte = Compte::where('id', $id)
                ->where('user_id', Auth::id())
                ->firstOrFail();

            $compte->delete();

            return response()->json(['success' => true, 'message' => 'Compte supprimé']);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
