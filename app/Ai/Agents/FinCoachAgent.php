<?php

namespace App\Ai\Agents;

use App\Models\Message as MessageModel;
use App\Models\RapportMensuel;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\Model;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Attributes\Temperature;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Promptable;
use Stringable;

#[Provider('gemini')]
#[Model('gemini-2.0-flash')]
#[Temperature(0.7)]
class FinCoachAgent implements Agent, Conversational
{
    use Promptable;

    public function __construct(private readonly User $user) {}

    public function instructions(): Stringable|string
    {
        $contexte = $this->buildContexte();

        return <<<PROMPT
Tu es FinCoach, un assistant coach financier bienveillant et expert en finances personnelles en Afrique de l'Ouest (monnaie : FCFA).
Tu aides {$this->user->name} à mieux gérer ses finances personnelles au quotidien.

Contexte financier actuel de l'utilisateur :
{$contexte}

Instructions importantes :
- Réponds TOUJOURS en français
- Sois encourageant, pratique et précis
- Donne des conseils actionnables adaptés au contexte africain (MoMo, Orange Money, banques locales)
- Limite tes réponses à 200 mots maximum pour être concis et lisible sur mobile
- Si l'utilisateur n'a pas assez de données financières, ou n'a pas de transaction utilise les informations sur son profil
- Ne demande jamais d'informations sensibles (mots de passe, etc.)
PROMPT;
    }

    public function messages(): iterable
    {
        return MessageModel::query()
            ->where('user_id', $this->user->id)
            ->orderByDesc('date')
            ->limit(20)
            ->get()
            ->reverse()
            ->map(fn($m) => new Message(
                $m->expediteur === 'utilisateur' ? 'user' : 'assistant',
                $m->contenu,
                $m->expediteur,
                []
            ))
            ->values()
            ->all();
    }

    private function buildContexte(): string
    {
        $rapports = RapportMensuel::where('user_id', '=', $this->user->id, 'and')
            ->orderByDesc('mois')
            ->limit(3)
            ->get();

        if ($rapports->isEmpty()) {
            return "Aucun rapport mensuel disponible. L'utilisateur commence tout juste à utiliser FinCoach.";
        }

        return $rapports->map(fn($r) =>
            Carbon::parse($r->mois)->translatedFormat('F Y') . " : " . $r->description
        )->join("\n");
    }
}
