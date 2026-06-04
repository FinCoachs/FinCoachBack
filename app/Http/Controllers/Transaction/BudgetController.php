<?php

namespace App\Http\Controllers\Transaction;

use App\Http\Controllers\Controller;
use App\Http\Requests\BudgetRequest;
use App\Http\Resources\Transaction\BudgetResource;
use App\Services\Transaction\BudgetService;
use App\DTOs\Transaction\CreateBudgetDTO;
use App\Models\Categorie;
use Exception;
use GuzzleHttp\Psr7\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class BudgetController extends Controller
{
    public function __construct(
        private readonly BudgetService $budgetService
    ) {}

    /**
     * Créer un nouveau budget (catégorie avec plafond).
     */
    public function store(BudgetRequest $request): JsonResponse
    {
        try {
            $budget = $this->budgetService->createBudget(
                CreateBudgetDTO::fromRequest($request)
            );

            return response()->json([
                'success' => true,
                'message' => 'Budget créé avec succès',
                'data' => new BudgetResource($budget),
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Lister les catégories de l'utilisateur connecté.
     */
    public function categories(): JsonResponse
    {
        $categories = Categorie::where('user_id', Auth::id())->get();

        if ($categories->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Aucune catégorie trouvée'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $categories
        ], 200);
    }

    // public function getCategorieByName(Request $request){
    //     $categorie_id =
    // }
}
