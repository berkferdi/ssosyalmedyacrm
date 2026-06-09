<?php

class InstagramAPI
{
    private const GRAPH_URL = 'https://graph.facebook.com/v19.0';

    public static function getAuthUrl(): string
    {
        $params = http_build_query([
            'client_id' => FACEBOOK_APP_ID,
            'redirect_uri' => FACEBOOK_REDIRECT_URI . '?platform=instagram',
            'scope' => 'instagram_basic,instagram_content_publish,pages_show_list',
            'response_type' => 'code',
            'state' => Security::generateCSRFToken(),
        ]);
        return 'https://www.facebook.com/v19.0/dialog/oauth?' . $params;
    }

    public static function getBusinessAccounts(string $pageId, string $accessToken): array
    {
        $url = self::GRAPH_URL . "/{$pageId}?fields=instagram_business_account&access_token=" . urlencode($accessToken);
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
