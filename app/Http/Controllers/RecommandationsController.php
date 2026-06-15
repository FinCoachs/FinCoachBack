<?php

namespace App\Http\Controllers;

use App\Models\Categorie;
use App\Models\RapportMensuel;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class RecommandationsController extends Controller
{
    public function index()
    {
        $user  = Auth::user();
        $now   = Carbon::now();
        $debut = $now->copy()->startOfMonth();

        $categories = Categorie::where('user_id', $user->id)
            ->withSum(['transactions as depenses_mois' => fn($q) =>
                $q->where('type', 'depense')->where('date', '>=', $debut)
            ], 'montant')
            ->get();

        $dernierRapport = RapportMensuel::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->first();

        $recommandations = $this->recommandationsRuleBased($categories, $dernierRapport, $now);

        return response()->json([
            'success' => true,
            'data'    => array_values($recommandations),
        ]);
    }

    private function recommandationsRuleBased($categories, $dernierRapport, Carbon $now): array
    {
        $recs = [];

        foreach ($categories as $cat) {
            if (!$cat->plafond || $cat->plafond <= 0) continue;

            $dep = (float) ($cat->depenses_mois ?? 0);
            $pct = round(($dep / $cat->plafond) * 100);

            if ($pct >= 100) {
                $recs[] = $this->rec('Alertes', 'BUDGET DÉPASSÉ', 'lightning-bolt', 'community',
                    "Votre budget « {$cat->libelle} » est dépassé ({$pct}%). Limitez les dépenses dans cette catégorie.", 'maintenant');
            } elseif ($pct >= 80) {
                $recs[] = $this->rec('Alertes', 'BUDGET CRITIQUE', 'lightning-bolt', 'community',
                    "Votre budget « {$cat->libelle} » est à {$pct}%. Il reste " .
                    number_format($cat->plafond - $dep, 0, ',', ' ') . " FCFA.", 'maintenant');
            }
        }

        if ($dernierRapport) {
            $moisLabel = Carbon::parse($dernierRapport->mois)->translatedFormat('F Y');
            $recs[] = $this->rec('Rapports', 'RAPPORT MENSUEL', 'chart-bar', 'community',
                "Votre rapport de {$moisLabel} est disponible.", $this->tempsRelatif($dernierRapport->created_at));
        }

        if (!$categories->contains(fn($c) => str_contains(mb_strtolower($c->libelle), 'épargne') || str_contains(mb_strtolower($c->libelle), 'epargne'))) {
            $recs[] = $this->rec('Épargne', 'CONSEIL ÉPARGNE', 'piggy-bank', 'community',
                "Créez un budget Épargne pour suivre vos objectifs d'épargne mensuelle.", 'conseil');
        }

        if ($now->diffInDays($now->copy()->endOfMonth()) <= 5) {
            $recs[] = $this->rec('Alertes', 'FIN DE MOIS', 'time-outline', 'ionicons',
                "Il reste " . $now->diffInDays($now->copy()->endOfMonth()) .
                " jour(s) avant la fin du mois. Vérifiez vos budgets.", "bientôt");
        }

        return $recs;
    }

    private function rec(string $categorie, string $label, string $icon, string $iconLib, string $texte, string $temps): array
    {
        return compact('categorie', 'label', 'icon', 'iconLib', 'texte', 'temps');
    }

    private function tempsRelatif(\DateTimeInterface|string|null $date): string
    {
        $diff = Carbon::parse($date)->diffInHours(now());
        if ($diff < 1)  return 'à l\'instant';
        if ($diff < 24) return "Il y a {$diff}h";
        return "Il y a " . round($diff / 24) . "j";
    }
}
