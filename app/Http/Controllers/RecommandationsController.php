<?php

namespace App\Http\Controllers;

use App\Models\Categorie;
use App\Models\RapportMensuel;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class RecommandationsController extends Controller
{
    /**
     * Génère des recommandations personnalisées à partir des données financières
     * réelles de l'utilisateur (budgets, transactions du mois en cours).
     */
    public function index()
    {
        $user  = Auth::user();
        $recs  = [];
        $now   = Carbon::now();
        $debut = $now->copy()->startOfMonth();

        // ── 1. Alertes de seuil budgétaire ───────────────────────────────────
        $categories = Categorie::where('user_id', $user->id)
            ->withSum(['transactions as depenses_mois' => function ($q) use ($debut) {
                $q->where('type', 'depense')->where('date', '>=', $debut);
            }], 'montant')
            ->get();

        foreach ($categories as $cat) {
            if (! $cat->plafond || $cat->plafond <= 0) continue;

            $depenses = (float) ($cat->depenses_mois ?? 0);
            $pct      = round(($depenses / $cat->plafond) * 100);
            $restant  = $cat->plafond - $depenses;

            if ($pct >= 100) {
                $recs[] = $this->rec(
                    'Alertes', 'BUDGET DÉPASSÉ', 'lightning-bolt', 'community',
                    "Votre budget « {$cat->libelle} » est dépassé ({$pct}% consommé). " .
                    "Vous avez dépensé " . number_format($depenses, 0, ',', ' ') . " FCFA pour un plafond de " .
                    number_format($cat->plafond, 0, ',', ' ') . " FCFA.",
                    'maintenant'
                );
            } elseif ($pct >= 80) {
                $recs[] = $this->rec(
                    'Alertes', 'BUDGET CRITIQUE', 'lightning-bolt', 'community',
                    "Votre budget « {$cat->libelle} » est à {$pct}%. Il vous reste " .
                    number_format($restant, 0, ',', ' ') . " FCFA pour la fin du mois.",
                    'maintenant'
                );
            }
        }

        // ── 2. Rapport mensuel disponible ────────────────────────────────────
        $dernierRapport = RapportMensuel::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->first();

        if ($dernierRapport) {
            $moisLabel = Carbon::parse($dernierRapport->mois)->translatedFormat('F Y');
            $recs[] = $this->rec(
                'Rapports', 'RAPPORT MENSUEL', 'chart-bar', 'community',
                "Votre rapport du mois de {$moisLabel} est disponible. Consultez le résumé de vos revenus, dépenses et solde.",
                $this->tempsRelatif($dernierRapport->created_at)
            );
        }

        // ── 3. Conseil épargne si solde positif sans plafond épargne ─────────
        $aEpargne = $categories->contains(fn($c) =>
            str_contains(mb_strtolower($c->libelle), 'épargne') ||
            str_contains(mb_strtolower($c->libelle), 'epargne')
        );

        if (! $aEpargne) {
            $recs[] = $this->rec(
                'Épargne', 'CONSEIL ÉPARGNE', 'piggy-bank', 'community',
                "Vous n'avez pas encore de budget Épargne. Créez-en un dans la section Budget pour suivre votre objectif d'épargne mensuelle.",
                'conseil'
            );
        }

        // ── 4. Catégories sans transactions ce mois ───────────────────────────
        $sansActivite = $categories->filter(fn($c) =>
            $c->plafond && ($c->depenses_mois ?? 0) == 0
        );

        if ($sansActivite->count() >= 2) {
            $noms = $sansActivite->take(3)->pluck('libelle')->join(', ');
            $recs[] = $this->rec(
                'Dépenses', 'BUDGETS INUTILISÉS', 'trending-down', 'ionicons',
                "Aucune dépense enregistrée ce mois dans : {$noms}. Vérifiez que vos transactions sont bien catégorisées.",
                'ce mois'
            );
        }

        // ── 5. Fin de mois proche ─────────────────────────────────────────────
        $joursRestants = $now->diffInDays($now->copy()->endOfMonth());

        if ($joursRestants <= 5) {
            $totalDepenses = $categories->sum('depenses_mois');
            $recs[] = $this->rec(
                'Alertes', 'FIN DE MOIS', 'time-outline', 'ionicons',
                "Il reste {$joursRestants} jour(s) avant la fin du mois. Vous avez dépensé " .
                number_format($totalDepenses, 0, ',', ' ') . " FCFA ce mois-ci.",
                "dans {$joursRestants}j"
            );
        }

        return response()->json([
            'success' => true,
            'data'    => array_values($recs),
        ]);
    }

    private function rec(
        string $categorie,
        string $label,
        string $icon,
        string $iconLib,
        string $texte,
        string $temps
    ): array {
        return compact('categorie', 'label', 'icon', 'iconLib', 'texte', 'temps');
    }

    private function tempsRelatif($date): string
    {
        if (! $date) return '';
        $diff = Carbon::parse($date)->diffInHours(now());
        if ($diff < 1)  return 'à l\'instant';
        if ($diff < 24) return "Il y a {$diff}h";
        $jours = round($diff / 24);
        return "Il y a {$jours}j";
    }
}
