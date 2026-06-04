<?php   
    use App\Services\Transaction;
    use App\DTOs\Transaction\CreateBudgetDTOs;

    
    class BudgetService {
        

        public function CreateBudget(CreateBudgetDTOs $budgetDTOs){
            try{
                $budget = Budget::create([
                    "libelle" => $budgetDTOs->libelle,
                    "plafond" => $budgetDTOs->plafond,
                    "user_id" => Auth::user()->id,
                ]);

                if($budget){
                    return response()->json([
                        'success' => true,
                        'message' => 'Budget créé avec succès',
                    ], 200);
                }
                else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Erreur lors de la création du budget',
                    ], 500);
                }
            }
            catch (Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 500);
            }
            
        }
    }