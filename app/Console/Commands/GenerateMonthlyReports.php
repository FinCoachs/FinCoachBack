<?php

namespace App\Console\Commands;

use App\Models\RapportMensuel;
use App\Models\User;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use function Laravel\Ai\agent;

class GenerateMonthlyReports extends Command
{
    protected $signature   = 'rapports:mensuel {--user= : ID d\'un utilisateur spécifique}';
    protected $description = 'Génère le rapport mensuel des transactions pour chaque utilisateur';

    public function __construct(private readonly NotificationService $notificationService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $mois        = Carbon::now()->subMonth()->startOfMonth();
        $moisLabel   = $mois->translatedFormat('F Y');
        $debutMois   = $mois->copy()->startOfMonth();
        $finMois     = $mois->copy()->endOfMonth();

        $query = User::with([
            'comptes',
            'comptes.transactions' => fn($q) => $q->whereBetween('date', [$debutMois, $finMois])
                ->with('categorie'),
        ]);

        if ($userId = $this->option('user')) {
            $query->whereKey($userId);
        }

        $users = $query->get();
        $count = 0;

        foreach ($users as $user) {
            $existe = RapportMensuel::where('user_id', $user->id)
                ->whereDate('mois', $mois->toDateString())
                ->exists();

            if ($existe) continue;

            $revenus  = 0.0;
            $depenses = 0.0;
            $txList   = [];

            foreach ($user->comptes as $compte) {
                foreach ($compte->transactions as $tx) {
                    if ($tx->type === 'revenu')  $revenus  += $tx->montant;
                    if ($tx->type === 'depense') $depenses += $tx->montant;

                    $txList[] = [
                        'date'      => $tx->date,
                        'type'      => $tx->type,
                        'categorie' => $tx->categorie?->libelle ?? 'Non catégorisé',
                        'montant'   => $tx->montant,
                    ];
                }
            }

            $net         = $revenus - $depenses;
            $netSigne    = $net >= 0 ? '+' : '-';
            $revenusFmt  = number_format($revenus,  0, ',', ' ');
            $depensesFmt = number_format($depenses, 0, ',', ' ');
            $netFmt      = number_format(abs($net), 0, ',', ' ');

            $description = $this->genererResumeIA($user->name, $moisLabel, $txList, $revenus, $depenses, $net)
                ?? "Rapport {$moisLabel} — Revenus : {$revenusFmt} FCFA | Dépenses : {$depensesFmt} FCFA | Net : {$netSigne}{$netFmt} FCFA.";

            RapportMensuel::create([
                'user_id'     => $user->id,
                'description' => $description,
                'mois'        => $mois->toDateString(),
            ]);

            $this->notificationService->notify(
                $user,
                "Votre rapport de {$moisLabel} est prêt. Net : {$netSigne}{$netFmt} FCFA.",
                'info',
                '📊 Rapport mensuel disponible'
            );

            $count++;
            $this->info("  ✓ Rapport généré pour {$user->name}");
        }

        $this->info("✅ {$count} rapport(s) généré(s) pour {$moisLabel}.");
        return Command::SUCCESS;
    }

    private function genererResumeIA(
        string $nom,
        string $moisLabel,
        array  $transactions,
        float  $revenus,
        float  $depenses,
        float  $net
    ): ?string {
        if (empty($transactions)) return null;

        try {
            $lignes = collect($transactions)
                ->map(fn($tx) =>
                    "- {$tx['date']} | {$tx['type']} | {$tx['categorie']} | " .
                    number_format((float) $tx['montant'], 0, ',', ' ') . " FCFA"
                )
                ->join("\n");

            $netSigne = $net >= 0 ? '+' : '-';
            $netFmt   = number_format(abs($net), 0, ',', ' ');

            $instructions = <<<PROMPT
Tu es FinCoach, un expert en finances personnelles en Afrique de l'Ouest (FCFA).
Analyse les transactions financières de {$nom} pour {$moisLabel} et rédige un résumé mensuel personnalisé.

Résumé chiffré :
- Revenus totaux : {$revenus} FCFA
- Dépenses totales : {$depenses} FCFA
- Solde net : {$netSigne}{$netFmt} FCFA

Détail des transactions :
{$lignes}

Instructions :
- Identifie les 2-3 principales catégories de dépenses
- Évalue brièvement la santé financière du mois
- Donne 1-2 conseils pratiques et actionnables pour le mois suivant
- Sois encourageant et bienveillant
- Réponds en français, 150-200 mots maximum, sans titre ni liste à puces
PROMPT;

            $response = agent(instructions: $instructions)->prompt(
                "Génère le résumé mensuel pour {$moisLabel}.",
                provider: 'gemini',
                model: 'gemini-2.0-flash',
            );

            return (string) $response;
        } catch (\Throwable) {
            return null;
        }
    }
}
