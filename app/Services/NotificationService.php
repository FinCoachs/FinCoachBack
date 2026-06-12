<?php

namespace App\Services;

use App\Models\Alerte;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    private const EXPO_PUSH_URL = 'https://exp.host/--/api/v2/push/send';

    /**
     * Envoie une notification push via l'API Expo.
     */
    public function sendPush(User $user, string $title, string $body, array $data = []): void
    {
        if (! $user->expo_push_token) return;

        try {
            Http::withHeaders(['Accept' => 'application/json'])
                ->post(self::EXPO_PUSH_URL, [
                    'to'    => $user->expo_push_token,
                    'sound' => 'default',
                    'title' => $title,
                    'body'  => $body,
                    'data'  => $data,
                ]);
        } catch (\Throwable $e) {
            Log::warning('Expo push failed: ' . $e->getMessage());
        }
    }

    /**
     * Crée une alerte en base + envoie une push notification.
     */
    public function notify(User $user, string $message, string $type = 'alerte', ?string $pushTitle = null): Alerte
    {
        $alerte = Alerte::create([
            'user_id' => $user->id,
            'message' => $message,
            'type'    => $type,
            'lue'     => false,
            'date'    => now(),
        ]);

        $this->sendPush(
            $user,
            $pushTitle ?? ($type === 'alerte' ? '⚠️ Alerte budget' : '💡 FinCoach'),
            $message,
            ['type' => $type, 'alerte_id' => $alerte->id],
        );

        return $alerte;
    }
}
