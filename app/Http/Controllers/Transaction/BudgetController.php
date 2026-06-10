<?php

namespace App\Http\Controllers\Transaction;

use App\Http\Controllers\Controller;
use App\Http\Requests\BudgetRequest;
use App\Http\Resources\Transaction\BudgetResource;
use App\Services\Transaction\BudgetService;
use App\DTOs\Transaction\CreateBudgetDTO;
use Exception;
use Illuminate\Http\JsonResponse;

class BudgetController extends Controller
{
    public function __construct(
        private readonly BudgetService $budgetService
    ) {}

    public function store(BudgetRequest $request): JsonResponse
    {
        try {
            $budget = $this->budgetService->createBudget(
                CreateBudgetDTO::fromRequest($request)
            );

            return response()->json([
                'success' => true,
                'message' => 'Budget créé avec succès',
                'data'    => new BudgetResource($budget),
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function categories(): JsonResponse
    {
        $categories = $this->budgetService->getCategories();

        if ($categories->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Aucune catégorie trouvée',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => BudgetResource::collection($categories),
        ], 200);
    }
}
