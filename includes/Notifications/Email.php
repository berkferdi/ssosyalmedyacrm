<?php

class EmailNotification
{
    public static function send(string $to, string $subject, string $body): bool
    {
        if (empty(SMTP_HOST) || empty(SMTP_USER)) {
            return false;
        }

        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=utf-8',
            'From: ' . SMTP_FROM_NAME . ' <' . SMTP_FROM . '>',
            'Reply-To: ' . SMTP_FROM,
            'X-Mailer: SocialPilot AI',
        ];

        return mail($to, $subject, $body, implode("\r\n", $headers));
    }

    public static function sendTemplate(string $to, string $subject, string $template, array $data = []): bool
    {
        ob_start();
        extract($data);
        include TEMPLATES_PATH . '/email/' . $template . '.php';
        $body = ob_get_clean();

        return self::send($to, $subject, $body);
    }

    public static function notifyUser(int $userId, string $type, string $title, string $message): void
    {
        $user = Database::fetch('SELECT email, first_name FROM users WHERE id = ?', [$userId]);
        if (!$user) return;

        self::sendTemplate($user['email'], $title, 'notification', [
            'name' => $user['first_name'],
            'title' => $title,
            'message' => $message,
            'type' => $type,
        ]);

        Database::update('notifications', ['sent_email' => 1], 'user_id = ? AND title = ? ORDER BY id DESC LIMIT 1', [$userId, $title]);
    }
}
