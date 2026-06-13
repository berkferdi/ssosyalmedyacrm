<?php

function app_log(string $source, string $message, ?int $userId = null, ?array $context = null): void
{
    Database::insert('error_logs', [
        'user_id' => $userId,
        'source' => $source,
        'message' => $message,
        'context' => $context ? json_encode($context) : null,
    ]);
}

function create_notification(int $userId, string $type, string $title, string $message, ?array $data = null): int
{
    return Database::insert('notifications', [
        'user_id' => $userId,
        'type' => $type,
        'title' => $title,
        'message' => $message,
        'data' => $data ? json_encode($data) : null,
    ]);
}

function get_setting(string $key, $default = null, ?int $userId = null)
{
    $setting = Database::fetch(
        'SELECT setting_value FROM settings WHERE setting_key = ? AND (user_id = ? OR user_id IS NULL) ORDER BY user_id DESC LIMIT 1',
        [$key, $userId]
    );
    return $setting ? $setting['setting_value'] : $default;
}

function set_setting(string $key, $value, ?int $userId = null): void
{
    $existing = Database::fetch(
        'SELECT id FROM settings WHERE setting_key = ? AND user_id ' . ($userId ? '= ?' : 'IS NULL'),
        $userId ? [$key, $userId] : [$key]
    );

    if ($existing) {
        Database::update('settings', [
            'setting_value' => $value,
        ], 'id = ?', [$existing['id']]);
    } else {
        Database::insert('settings', [
            'user_id' => $userId,
            'setting_key' => $key,
            'setting_value' => $value,
        ]);
    }
}

function get_system_setting(string $key, $default = null)
{
    $setting = Database::fetch(
        'SELECT setting_value FROM settings WHERE setting_key = ? AND user_id IS NULL',
        [$key]
    );
    return ($setting && $setting['setting_value'] !== '') ? $setting['setting_value'] : $default;
}

function set_system_setting(string $key, $value): void
{
    set_setting($key, $value, null);
}

function get_api_setting(string $key): string
{
    $dbValue = get_system_setting($key, '');
    if ($dbValue !== '' && $dbValue !== null) {
        return (string) $dbValue;
    }

    $constantMap = [
        'openai_api_key' => 'OPENAI_API_KEY',
        'openai_model' => 'OPENAI_MODEL',
        'facebook_app_id' => 'FACEBOOK_APP_ID',
        'facebook_app_secret' => 'FACEBOOK_APP_SECRET',
        'linkedin_client_id' => 'LINKEDIN_CLIENT_ID',
        'linkedin_client_secret' => 'LINKEDIN_CLIENT_SECRET',
    ];

    if (isset($constantMap[$key]) && defined($constantMap[$key])) {
        $value = constant($constantMap[$key]);
        return $value ? (string) $value : '';
    }

    return '';
}

function mask_api_key(string $key): string
{
    if (strlen($key) <= 8) {
        return '••••••••';
    }
    return substr($key, 0, 7) . '...' . substr($key, -4);
}

function is_localhost_host(?string $host): bool
{
    if (!$host) {
        return true;
    }
    return in_array($host, ['localhost', '127.0.0.1', '::1'], true)
        || str_ends_with($host, '.local');
}

function is_https_request(): bool
{
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        return true;
    }
    if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') {
        return true;
    }
    if (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on') {
        return true;
    }
    return !empty($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443;
}

function force_https_url(string $url): string
{
    $host = parse_url($url, PHP_URL_HOST);
    if (str_starts_with($url, 'http://') && !is_localhost_host($host)) {
        return 'https://' . substr($url, 7);
    }
    return $url;
}

function app_url(): string
{
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }

    try {
        $configured = get_system_setting('app_url', '');
        if ($configured !== '') {
            $cached = rtrim(force_https_url($configured), '/');
            return $cached;
        }
    } catch (Throwable $e) {
        // DB henüz hazır olmayabilir
    }

    if (php_sapi_name() !== 'cli' && !empty($_SERVER['HTTP_HOST'])) {
        $scheme = is_https_request() ? 'https' : 'http';
        if (!is_localhost_host($_SERVER['HTTP_HOST'])) {
            $scheme = 'https';
        }
        $cached = $scheme . '://' . $_SERVER['HTTP_HOST'];
        return $cached;
    }

    $cached = rtrim(force_https_url(defined('APP_URL') ? APP_URL : ''), '/');
    return $cached;
}

function oauth_redirect_uri(string $provider): string
{
    return match ($provider) {
        'instagram' => app_url() . '/api/oauth/instagram.php',
        'facebook' => app_url() . '/api/oauth/facebook.php',
        'linkedin' => app_url() . '/api/oauth/linkedin.php',
        default => app_url() . '/api/oauth/' . $provider . '.php',
    };
}

function oauth_state_create(): string
{
    $state = Security::generateToken(16);
    $_SESSION['oauth_state'] = $state;
    return $state;
}

function oauth_state_validate(?string $state): bool
{
    $expected = $_SESSION['oauth_state'] ?? '';
    unset($_SESSION['oauth_state']);
    return $expected !== '' && hash_equals($expected, (string) $state);
}

function format_file_size(int $bytes): string
{
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = 0;
    while ($bytes >= 1024 && $i < count($units) - 1) {
        $bytes /= 1024;
        $i++;
    }
    return round($bytes, 2) . ' ' . $units[$i];
}

function time_ago(string $datetime): string
{
    $diff = time() - strtotime($datetime);
    if ($diff < 60) return 'Az önce';
    if ($diff < 3600) return floor($diff / 60) . ' dakika önce';
    if ($diff < 86400) return floor($diff / 3600) . ' saat önce';
    if ($diff < 2592000) return floor($diff / 86400) . ' gün önce';
    return date('d.m.Y', strtotime($datetime));
}

function platform_icon(string $platform): string
{
    return match ($platform) {
        'instagram' => 'bi-instagram',
        'facebook' => 'bi-facebook',
        'linkedin' => 'bi-linkedin',
        'wordpress' => 'bi-wordpress',
        default => 'bi-globe',
    };
}

function platform_color(string $platform): string
{
    return match ($platform) {
        'instagram' => '#E4405F',
        'facebook' => '#1877F2',
        'linkedin' => '#0A66C2',
        'wordpress' => '#21759B',
        default => '#6c757d',
    };
}

function status_badge(string $status): string
{
    $map = [
        'pending' => 'warning',
        'published' => 'success',
        'failed' => 'danger',
        'retry' => 'info',
        'active' => 'success',
        'inactive' => 'secondary',
        'expired' => 'warning',
        'disconnected' => 'danger',
        'error' => 'danger',
    ];
    $class = $map[$status] ?? 'secondary';
    $labels = [
        'pending' => 'Bekliyor',
        'published' => 'Paylaşıldı',
        'failed' => 'Hata',
        'retry' => 'Yeniden Dene',
        'active' => 'Aktif',
        'inactive' => 'Pasif',
        'expired' => 'Süresi Doldu',
        'disconnected' => 'Bağlantı Koptu',
        'error' => 'Hata',
    ];
    $label = $labels[$status] ?? ucfirst($status);
    return '<span class="badge bg-' . $class . '">' . Security::escape($label) . '</span>';
}

function role_label(string $role): string
{
    return match ($role) {
        'super_admin' => 'Süper Admin',
        'agency_manager' => 'Ajans Yöneticisi',
        'editor' => 'Editör',
        'customer' => 'Müşteri',
        default => $role,
    };
}

function generate_unique_filename(string $originalName): string
{
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    return date('Y/m/d/') . uniqid('media_', true) . '.' . $ext;
}

function get_dashboard_stats(int $userId): array
{
    $isAdmin = Auth::hasRole(['super_admin', 'agency_manager']);
    $userFilter = $isAdmin ? '' : ' AND user_id = ?';
    $params = $isAdmin ? [] : [$userId];

    return [
        'total_accounts' => Database::count('social_accounts', 'status = ?' . str_replace('user_id', 'social_accounts.user_id', $userFilter), array_merge(['active'], $params)),
        'today_posts' => Database::count(
            'scheduled_posts',
            'status = ? AND DATE(published_at) = CURDATE()' . str_replace('user_id', 'scheduled_posts.user_id', $userFilter),
            array_merge(['published'], $params)
        ),
        'pending_posts' => Database::count(
            'scheduled_posts',
            'status = ?' . str_replace('user_id', 'scheduled_posts.user_id', $userFilter),
            array_merge(['pending'], $params)
        ),
        'scheduled_posts' => Database::count(
            'scheduled_posts',
            'status = ? AND scheduled_at > NOW()' . str_replace('user_id', 'scheduled_posts.user_id', $userFilter),
            array_merge(['pending'], $params)
        ),
        'total_reach' => (int) (Database::fetch(
            'SELECT COALESCE(SUM(reach), 0) as total FROM analytics a
             JOIN social_accounts sa ON a.social_account_id = sa.id
             WHERE a.metric_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)' . ($isAdmin ? '' : ' AND sa.user_id = ?'),
            $params
        )['total'] ?? 0),
        'total_likes' => (int) (Database::fetch(
            'SELECT COALESCE(SUM(likes), 0) as total FROM analytics a
             JOIN social_accounts sa ON a.social_account_id = sa.id
             WHERE a.metric_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)' . ($isAdmin ? '' : ' AND sa.user_id = ?'),
            $params
        )['total'] ?? 0),
        'follower_growth' => (int) (Database::fetch(
            'SELECT COALESCE(MAX(followers) - MIN(followers), 0) as growth FROM analytics a
             JOIN social_accounts sa ON a.social_account_id = sa.id
             WHERE a.metric_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)' . ($isAdmin ? '' : ' AND sa.user_id = ?'),
            $params
        )['growth'] ?? 0),
    ];
}

function render_template(string $template, array $data = []): void
{
    extract($data);
    include TEMPLATES_PATH . '/' . $template;
}

function page_title(string $title): string
{
    return Security::escape($title) . ' - ' . APP_NAME;
}
