<?php
    namespace App\Services\Transaction;

    use App\DTOs\Transaction\CreateBudgetDTOs;
    use App\Http\Resources\Transaction\BudgetRessource;
    use App\Models\Categorie;
    use Illuminate\Http\JsonResponse;
    use Illuminate\Support\Facades\Auth;

    class BudgetService {


        public function CreateBudget(CreateBudgetDTOs $budgetDTOs) : JsonResponse{
            try{
                $budget = Categorie::create([
                    "libelle" => $budgetDTOs->libelle,
                    "plafond" => $budgetDTOs->plafond,
                    "user_id" => Auth::id()
                ]);

                if($budget){
                    return response()->json([
                        'success' => true,
                        'message' => 'Budget créé avec succès',
                        'data' => new BudgetRessource($budget),
                    ], 200);
                }
                else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Erreur lors de la création du budget',
                    ], 500);
                }
            }
            catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 500);
            }

        }

    }
