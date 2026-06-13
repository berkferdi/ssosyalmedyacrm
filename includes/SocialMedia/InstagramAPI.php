<?php

class InstagramAPI
{
    private const GRAPH_URL = 'https://graph.facebook.com/v21.0';
    private const OAUTH_URL = 'https://www.facebook.com/v21.0/dialog/oauth';

    public static function getAuthUrl(): string
    {
        $appId = get_api_setting('facebook_app_id');
        if ($appId === '') {
            throw new RuntimeException('Facebook App ID tanımlı değil. Instagram için Meta uygulama ID\'si gereklidir.');
        }

        $redirectUri = oauth_redirect_uri('instagram');
        $params = http_build_query([
            'client_id' => $appId,
            'redirect_uri' => $redirectUri,
            'scope' => 'instagram_basic,instagram_content_publish,pages_show_list,pages_read_engagement,public_profile',
            'response_type' => 'code',
            'state' => oauth_state_create(),
        ]);

        return self::OAUTH_URL . '?' . $params;
    }

    public static function exchangeCode(string $code): array
    {
        $redirectUri = oauth_redirect_uri('instagram');
        $url = self::GRAPH_URL . '/oauth/access_token?' . http_build_query([
            'client_id' => get_api_setting('facebook_app_id'),
            'client_secret' => get_api_setting('facebook_app_secret'),
            'redirect_uri' => $redirectUri,
            'code' => $code,
        ]);

        return self::request($url);
    }

    public static function getBusinessAccounts(string $pageId, string $accessToken): array
    {
        $url = self::GRAPH_URL . "/{$pageId}?" . http_build_query([
            'fields' => 'instagram_business_account{id,username}',
            'access_token' => $accessToken,
        ]);
        return self::request($url);
    }

    public static function publishPost(string $igUserId, string $accessToken, string $imageUrl, string $caption): array
    {
        $container = self::request(self::GRAPH_URL . "/{$igUserId}/media", 'POST', [
            'image_url' => $imageUrl,
            'caption' => $caption,
            'access_token' => $accessToken,
        ]);

        if (empty($container['id'])) {
            return ['error' => $container['error']['message'] ?? 'Container oluşturulamadı'];
        }

        return self::request(self::GRAPH_URL . "/{$igUserId}/media_publish", 'POST', [
            'creation_id' => $container['id'],
            'access_token' => $accessToken,
        ]);
    }

    public static function getInsights(string $igUserId, string $accessToken): array
    {
        $url = self::GRAPH_URL . "/{$igUserId}/insights?" . http_build_query([
            'metric' => 'impressions,reach,profile_views',
            'period' => 'day',
            'access_token' => $accessToken,
        ]);
        return self::request($url);
    }

    private static function request(string $url, string $method = 'GET', array $data = []): array
    {
        $ch = curl_init();
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        } else {
            curl_setopt($ch, CURLOPT_URL, $url);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true) ?? [];
    }
}
