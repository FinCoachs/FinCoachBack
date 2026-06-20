<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use App\Ai\Tools\ConsulterProfil;
use App\Ai\Tools\ConsulterSolde;
use App\Ai\Tools\ListerTransactions;
use App\Services\Summary\TransactionSummaryService;
use Laravel\Ai\Attributes\MaxSteps;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Promptable;

#[MaxSteps(10)]
#[MaxTokens(4096)]
class FinCoachAgent implements Agent, Conversational, HasTools
{
    use Promptable, RemembersConversations;

    public function __construct(
        private readonly TransactionSummaryService $summaryService,
    ) {}

    public function instructions(): string
    {
        $today = now()->locale('fr')->isoFormat('dddd D MMMM YYYY');
        $user  = $this->conversationUser;

        $header = <<<HEADER
        Tu es FinCoach, un assistant financier personnel bienveillant et francophone.
        Aujourd'hui : {$today}.
        Langue : réponds TOUJOURS en français, sauf si l'utilisateur t'écrit en anglais — dans ce cas réponds en anglais.
        Montants : exprime-les en FCFA avec séparateur de milliers (ex : 320 000 FCFA).
        Sécurité : tu n'accèdes qu'aux données de l'utilisateur connecté. Refuse poliment toute demande concernant d'autres comptes. De même en accédant aux données utilisateur tu ne dois que tu dois tu référer qu'a l'historique des transactions utilisateur, résumé et profil configuré sur l'application.
        Périmètre : si la question sort des finances personnelles, recentre poliment la conversation.
        HEADER;

        if ($user === null) {
            return $header;
        }

        $summary = $this->summaryService->getFreshSummary($user);

        if ($summary->isEmpty()) {
            return $header . "\n\nL'utilisateur n'a pas encore enregistré de transactions. Encourage-le à en ajouter pour bénéficier d'une analyse personnalisée.";
        }

        $agg        = $summary->aggregats;
        $debut      = $summary->period_start->format('d/m/Y');
        $fin        = $summary->period_end->format('d/m/Y');
        $revenus    = number_format((float) $agg['total_revenus'], 0, ',', ' ');
        $depenses   = number_format((float) $agg['total_depenses'], 0, ',', ' ');
        $net        = number_format((float) $agg['net'], 0, ',', ' ');
        $nbTx       = $agg['transaction_count'];
        $nbRev      = $agg['revenus_count'];
        $nbDep      = $agg['depenses_count'];

        $categories = collect($agg['par_categorie'] ?? [])->map(function (array $cat): string {
            $total = number_format((float) $cat['total'], 0, ',', ' ');
            return "  - {$cat['libelle']} ({$cat['type']}) : {$total} FCFA ({$cat['count']} opérations)";
        })->implode("\n");

        $categoriesBlock = $categories !== '' ? "\nPar catégorie :\n{$categories}" : '';

        return <<<INSTRUCTIONS
        {$header}

        === RÉSUMÉ FINANCIER (du {$debut} au {$fin}) ===
        Revenus totaux   : {$revenus} FCFA
        Dépenses totales : {$depenses} FCFA
        Solde net        : {$net} FCFA
        Transactions     : {$nbTx} ({$nbRev} revenus, {$nbDep} dépenses){$categoriesBlock}

        Ce résumé est ta référence principale. Utilise les outils disponibles uniquement pour des détails complémentaires (solde en temps réel, liste de transactions récentes, profil).
        INSTRUCTIONS;
    }

    /**
     * @return Tool[]
     */
    public function tools(): iterable
    {
        return [
            app(ConsulterSolde::class),
            app(ListerTransactions::class),
            app(ConsulterProfil::class),
        ];
    }
}
