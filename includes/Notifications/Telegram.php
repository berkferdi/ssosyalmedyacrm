<?php

class TelegramNotification
{
    public static function send(string $chatId, string $message): bool
    {
        if (empty(TELEGRAM_BOT_TOKEN) || empty($chatId)) {
            return false;
        }

        $url = 'https://api.telegram.org/bot' . TELEGRAM_BOT_TOKEN . '/sendMessage';
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => [
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'HTML',
            ],
            CURLOPT_TIMEOUT => 10,
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($response, true);
        return ($result['ok'] ?? false) === true;
    }

    public static function notifyUser(int $userId, string $title, string $message): void
    {
        $chatId = get_setting('telegram_chat_id', null, $userId);
        if (!$chatId) return;

        $text = "<b>{$title}</b>\n\n{$message}";
        if (self::send($chatId, $text)) {
            Database::update('notifications', ['sent_telegram' => 1], 'user_id = ? AND title = ? ORDER BY id DESC LIMIT 1', [$userId, $title]);
        }
    }
}
