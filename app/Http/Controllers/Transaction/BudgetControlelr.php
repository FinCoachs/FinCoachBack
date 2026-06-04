<?php

namespace App\Http\Controllers\Transaction;

use Illuminate\Http\Request;
use App\Actions\Http\Controllers\Controller;
use App\Http\Requests\BudgetRequest;
use App\Services\Transaction\BudgetService;
use Exception;
use Illuminate\Http\JsonResponse;

class BudgetControlelr extends Controller
{
    //Fonction pour la création du budget
    public function createBudget(BudgetRequest $request , BudgetService $budgetService) : JsonResponse{
        try{
            return $budgetService->CreateBudget(CreateBudgetDTOs::FromValidation($request));
        }
        catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
