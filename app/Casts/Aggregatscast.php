<?php

declare(strict_types=1);

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Cast JSON pour les agrégats : garantit que les totaux sont toujours des float
 * après désérialisation, indépendamment du driver de base de données.
 *
 * JSON encode les entiers PHP comme `400000` (pas `400000.0`), et json_decode
 * les retourne comme int — ce qui casse les assertions strictes et les calculs
 * qui attendent des float. Ce cast normalise systématiquement les types numériques.
 *
 * @implements CastsAttributes<array<string, mixed>, array<string, mixed>>
 */
final class Aggregatscast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): array
    {
        /** @var array<string, mixed> $data */
        $data = json_decode($value ?? '[]', true, 512, JSON_THROW_ON_ERROR);

        foreach (['total_revenus', 'total_depenses', 'net'] as $field) {
            if (array_key_exists($field, $data)) {
                $data[$field] = (float) $data[$field];
            }
        }

        foreach ($data['par_categorie'] ?? [] as $id => &$cat) {
            $cat['total'] = (float) ($cat['total'] ?? 0);
        }
        unset($cat);

        return $data;
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): string
    {
        return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    }
}
