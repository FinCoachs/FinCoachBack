<?php

namespace App\Http\Controllers\Transaction;

use App\Http\Controllers\Controller;
use App\Http\Requests\BudgetRequest;
use App\Services\Transaction\BudgetService;
use Exception;
use App\DTOs\Transaction\CreateBudgetDTOs;
use App\Models\Categorie;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class BudgetController extends Controller
{
    private BudgetService $budget;

    public function __construct(BudgetService $budget)
    {
        $this->budget = $budget;
    }

    //Fonction pour la création du budget
    public function createBudget(BudgetRequest $request ) : JsonResponse{
        try{

            return $this->budget->CreateBudget(CreateBudgetDTOs::FromValidation($request));
        }
        catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    //Fonction pour lister les categorie d'un utilisateur connecté
    public function getCategorie() : JsonResponse{
        $user_id = Auth::id();

        $categories = Categorie::where('user_id', $user_id)->get();

        if($categories->count() == 0){
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

}
