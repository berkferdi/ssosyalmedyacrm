<?php

class WhatsAppNotification
{
    public static function send(string $phone, string $message): bool
    {
        if (empty(WHATSAPP_API_URL) || empty(WHATSAPP_API_TOKEN)) {
            return false;
        }

        $ch = curl_init(WHATSAPP_API_URL);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . WHATSAPP_API_TOKEN,
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS => json_encode([
                'phone' => $phone,
                'message' => $message,
            ]),
            CURLOPT_TIMEOUT => 10,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $httpCode >= 200 && $httpCode < 300;
    }

    public static function notifyUser(int $userId, string $title, string $message): void
    {
        $phone = get_setting('whatsapp_phone', null, $userId);
        if (!$phone) return;

        $text = "*{$title}*\n\n{$message}";
        if (self::send($phone, $text)) {
            Database::update('notifications', ['sent_whatsapp' => 1], 'user_id = ? AND title = ? ORDER BY id DESC LIMIT 1', [$userId, $title]);
        }
    }
}
