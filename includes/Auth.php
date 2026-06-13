<?php

class Auth
{
    private static ?array $user = null;

    public static function init(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_set_cookie_params([
                'lifetime' => SESSION_LIFETIME,
                'path' => '/',
                'secure' => isset($_SERVER['HTTPS']),
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            session_start();
        }

        if (!empty($_SESSION['user_id'])) {
            self::$user = Database::fetch(
                'SELECT * FROM users WHERE id = ? AND status = ?',
                [$_SESSION['user_id'], 'active']
            );

            if (!self::$user) {
                self::logout();
            }
        }
    }

    public static function login(string $email, string $password, bool $remember = false): array
    {
        $user = Database::fetch('SELECT * FROM users WHERE email = ?', [$email]);

        if (!$user || !Security::verifyPassword($password, $user['password'])) {
            return ['success' => false, 'message' => 'E-posta veya şifre hatalı.'];
        }

        if ($user['status'] !== 'active') {
            return ['success' => false, 'message' => 'Hesabınız aktif değil.'];
        }

        if ($user['two_factor_enabled']) {
            $_SESSION['2fa_pending'] = $user['id'];
            return ['success' => true, 'requires_2fa' => true];
        }

        self::createSession($user);

        return ['success' => true, 'user' => self::userData($user)];
    }

    public static function verify2FA(string $code): array
    {
        if (empty($_SESSION['2fa_pending'])) {
            return ['success' => false, 'message' => '2FA oturumu bulunamadı.'];
        }

        $user = Database::fetch('SELECT * FROM users WHERE id = ?', [$_SESSION['2fa_pending']]);

        if (!$user || !$user['two_factor_secret']) {
            return ['success' => false, 'message' => '2FA yapılandırması hatalı.'];
        }

        if (!self::verifyTOTP($user['two_factor_secret'], $code)) {
            return ['success' => false, 'message' => 'Doğrulama kodu hatalı.'];
        }

        unset($_SESSION['2fa_pending']);
        self::createSession($user);

        return ['success' => true, 'user' => self::userData($user)];
    }

    private static function createSession(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['login_time'] = time();

        self::$user = $user;

        Database::update('users', [
            'last_login' => date('Y-m-d H:i:s'),
            'last_ip' => Security::getClientIP(),
        ], 'id = ?', [$user['id']]);

        Database::insert('session_logs', [
            'user_id' => $user['id'],
            'ip_address' => Security::getClientIP(),
            'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
            'action' => 'login',
        ]);
    }

    public static function logout(): void
    {
        if (!empty($_SESSION['user_id'])) {
            Database::insert('session_logs', [
                'user_id' => $_SESSION['user_id'],
                'ip_address' => Security::getClientIP(),
                'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
                'action' => 'logout',
            ]);
        }

        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }
        session_destroy();
        self::$user = null;
    }

    public static function check(): bool
    {
        return self::$user !== null;
    }

    public static function user(): ?array
    {
        return self::$user;
    }

    public static function id(): ?int
    {
        return self::$user ? (int) self::$user['id'] : null;
    }

    public static function role(): ?string
    {
        return self::$user['role'] ?? null;
    }

    public static function hasRole(string|array $roles): bool
    {
        if (!self::check()) {
            return false;
        }
        $roles = is_array($roles) ? $roles : [$roles];
        return in_array(self::role(), $roles, true);
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            if (Security::isAjax()) {
                Security::jsonResponse(['success' => false, 'message' => 'Oturum gerekli.'], 401);
            }
            Security::redirect(APP_URL . '/admin/login.php');
        }
    }

    public static function requireRole(string|array $roles): void
    {
        self::requireLogin();
        if (!self::hasRole($roles)) {
            if (Security::isAjax()) {
                Security::jsonResponse(['success' => false, 'message' => 'Yetkiniz yok.'], 403);
            }
            Security::redirect(APP_URL . '/admin/index.php?error=forbidden');
        }
    }

    public static function requestPasswordReset(string $email): array
    {
        $user = Database::fetch('SELECT id, email FROM users WHERE email = ? AND status = ?', [$email, 'active']);

        if (!$user) {
            return ['success' => true, 'message' => 'Şifre sıfırlama bağlantısı gönderildi.'];
        }

        $token = Security::generateToken();
        Database::update('users', [
            'reset_token' => hash('sha256', $token),
            'reset_token_expires' => date('Y-m-d H:i:s', strtotime('+1 hour')),
        ], 'id = ?', [$user['id']]);

        $resetUrl = APP_URL . '/admin/reset-password.php?token=' . urlencode($token);

        return [
            'success' => true,
            'message' => 'Şifre sıfırlama bağlantısı gönderildi.',
            'reset_url' => $resetUrl,
        ];
    }

    public static function resetPassword(string $token, string $password): array
    {
        $user = Database::fetch(
            'SELECT * FROM users WHERE reset_token = ? AND reset_token_expires > NOW()',
            [hash('sha256', $token)]
        );

        if (!$user) {
            return ['success' => false, 'message' => 'Geçersiz veya süresi dolmuş token.'];
        }

        if (strlen($password) < PASSWORD_MIN_LENGTH) {
            return ['success' => false, 'message' => 'Şifre en az ' . PASSWORD_MIN_LENGTH . ' karakter olmalı.'];
        }

        Database::update('users', [
            'password' => Security::hashPassword($password),
            'reset_token' => null,
            'reset_token_expires' => null,
        ], 'id = ?', [$user['id']]);

        return ['success' => true, 'message' => 'Şifreniz başarıyla güncellendi.'];
    }

    private static function userData(array $user): array
    {
        return [
            'id' => $user['id'],
            'email' => $user['email'],
            'first_name' => $user['first_name'],
            'last_name' => $user['last_name'],
            'role' => $user['role'],
            'avatar' => $user['avatar'],
        ];
    }

    private static function verifyTOTP(string $secret, string $code): bool
    {
        $timeSlice = floor(time() / 30);
        for ($i = -1; $i <= 1; $i++) {
            $calculated = self::generateTOTP($secret, $timeSlice + $i);
            if (hash_equals($calculated, $code)) {
                return true;
            }
        }
        return false;
    }

    private static function generateTOTP(string $secret, int $timeSlice): string
    {
        $key = base64_decode(str_pad(
            strtr($secret, '-_', '+/'),
            strlen($secret) % 4 === 0 ? strlen($secret) : strlen($secret) + 4 - strlen($secret) % 4,
            '=',
            STR_PAD_RIGHT
        ));
        $time = pack('N*', 0, $timeSlice);
        $hash = hash_hmac('sha1', $time, $key, true);
        $offset = ord($hash[19]) & 0x0F;
        $otp = (
            ((ord($hash[$offset]) & 0x7F) << 24) |
            ((ord($hash[$offset + 1]) & 0xFF) << 16) |
            ((ord($hash[$offset + 2]) & 0xFF) << 8) |
            (ord($hash[$offset + 3]) & 0xFF)
        ) % 1000000;
        return str_pad((string) $otp, 6, '0', STR_PAD_LEFT);
    }
}
