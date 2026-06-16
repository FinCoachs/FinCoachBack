<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\TransactionSummary;
use App\Models\User;
use App\Services\Summary\SummaryAggregator;
use App\Services\Summary\TransactionSummaryService;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Génère la baseline mensuelle de résumé de transactions pour UN utilisateur.
 *
 * Idempotent : si une baseline a déjà été générée lors du mois calendaire en cours
 * (UTC+0, configurable via APP_TIMEZONE), le job se termine sans action.
 * Cela permet de dispatcher le job plusieurs fois sans effet de bord (retry, re-schedule).
 *
 * Dispatché en queue par la planification mensuelle qui itère les utilisateurs en chunks.
 */
final class GenerateBaselineSummaryJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    /** Nombre de tentatives en cas d'échec (réseau, DB temporairement indisponible). */
    public int $tries = 3;

    /** Délai entre les tentatives (secondes). */
    public int $backoff = 60;

    public function __construct(
        private readonly string $userId,
    ) {}

    public function handle(TransactionSummaryService $service): void
    {
        $user = User::find($this->userId);

        // L'utilisateur peut avoir été supprimé entre le dispatch et l'exécution
        if ($user === null) {
            return;
        }

        // Idempotence : vérifie si une baseline existe déjà pour le mois en cours
        $startOfMonth = Carbon::now()->startOfMonth()->toDateString();

        $dejaFaite = TransactionSummary::where('user_id', $user->id)
            ->where('type', 'baseline')
            ->where('generated_at', '>=', $startOfMonth)
            ->exists();

        if ($dejaFaite) {
            return;
        }

        $service->generateBaseline($user);
    }
}
