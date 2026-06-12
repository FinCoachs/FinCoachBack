<?php

namespace App\Console\Commands;

use App\Models\RapportMensuel;
use App\Models\User;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Console\Command;

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
            'comptes.transactions' => fn($q) => $q->whereBetween('date', [$debutMois, $finMois]),
        ]);

        if ($userId = $this->option('user')) {
            $query->where('id', $userId);
        }

        $users = $query->get();
        $count = 0;

        foreach ($users as $user) {
            // Évite les doublons
            $existe = RapportMensuel::where('user_id', $user->id)
                ->whereDate('mois', $mois->toDateString())
                ->exists();

            if ($existe) continue;

            // Calcul global
            $revenus  = 0.0;
            $depenses = 0.0;

            foreach ($user->comptes as $compte) {
                foreach ($compte->transactions as $tx) {
                    if ($tx->type === 'revenu')  $revenus  += $tx->montant;
                    if ($tx->type === 'depense') $depenses += $tx->montant;
                }
            }

            $net              = $revenus - $depenses;
            $revenusFmt       = number_format($revenus,  0, ',', ' ');
            $depensesFmt      = number_format($depenses, 0, ',', ' ');
            $netFmt           = number_format(abs($net), 0, ',', ' ');
            $netSigne         = $net >= 0 ? '+' : '-';

            $description = "Rapport {$moisLabel} — "
                . "Revenus : {$revenusFmt} FCFA | "
                . "Dépenses : {$depensesFmt} FCFA | "
                . "Net : {$netSigne}{$netFmt} FCFA.";

            RapportMensuel::create([
                'user_id'     => $user->id,
                'description' => $description,
                'mois'        => $mois->toDateString(),
            ]);

            // Notification push
            $this->notificationService->notify(
                $user,
                "Votre rapport de {$moisLabel} est prêt. Net : {$netSigne}{$netFmt} FCFA.",
                'info',
                '📊 Rapport mensuel disponible'
            );

            $count++;
        }

        $this->info("✅ {$count} rapport(s) généré(s) pour {$moisLabel}.");
        return Command::SUCCESS;
    }
}
