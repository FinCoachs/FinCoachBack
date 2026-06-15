<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\Model;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Attributes\Temperature;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

#[Provider('gemini')]
#[Model('gemini-2.5-flash')]
#[Temperature(0.5)]
class RecommandationsAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): Stringable|string
    {
        return <<<'PROMPT'
Tu es FinCoach, un expert en finances personnelles en Afrique de l'Ouest (monnaie : FCFA).
Génère des recommandations financières personnalisées, pratiques et actionnables à partir des données fournies.

Règles :
- Réponds TOUJOURS en français
- Génère entre 3 et 5 recommandations
- Chaque recommandation doit être concrète et actionnable
- Adapte les conseils au contexte africain (MoMo, marchés locaux, etc.)
- Pour le champ "icon", utilise uniquement des noms valides de MaterialCommunityIcons ou Ionicons
PROMPT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'recommandations' => $schema->array()
                ->items(
                    $schema->object(fn($s) => [
                        'categorie' => $s->string()
                            ->enum(['Alertes', 'Épargne', 'Dépenses', 'Rapports'])
                            ->required(),
                        'label'  => $s->string()->required(),
                        'icon'   => $s->string()->required(),
                        'iconLib' => $s->string()
                            ->enum(['community', 'ionicons'])
                            ->required(),
                        'texte'  => $s->string()->required(),
                        'temps'  => $s->string()->required(),
                    ])
                )
                ->required(),
        ];
    }
}
