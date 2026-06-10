<?php

class FacebookAPI
{
    private const GRAPH_URL = 'https://graph.facebook.com/v19.0';

    public static function getAuthUrl(): string
    {
        $params = http_build_query([
            'client_id' => get_api_setting('facebook_app_id'),
            'redirect_uri' => FACEBOOK_REDIRECT_URI,
            'scope' => 'pages_manage_posts,pages_read_engagement,pages_show_list',
            'response_type' => 'code',
            'state' => Security::generateCSRFToken(),
        ]);
        return 'https://www.facebook.com/v19.0/dialog/oauth?' . $params;
    }

    public static function exchangeCode(string $code): array
    {
        $url = self::GRAPH_URL . '/oauth/access_token?' . http_build_query([
            'client_id' => get_api_setting('facebook_app_id'),
            'client_secret' => get_api_setting('facebook_app_secret'),
            'redirect_uri' => FACEBOOK_REDIRECT_URI,
            'code' => $code,
        ]);

        return self::request($url);
    }

    public static function getLongLivedToken(string $shortToken): array
    {
        $url = self::GRAPH_URL . '/oauth/access_token?' . http_build_query([
            'grant_type' => 'fb_exchange_token',
            'client_id' => get_api_setting('facebook_app_id'),
            'client_secret' => get_api_setting('facebook_app_secret'),
            'fb_exchange_token' => $shortToken,
        ]);

        return self::request($url);
    }

    public static function getPages(string $accessToken): array
    {
        $url = self::GRAPH_URL . '/me/accounts?access_token=' . urlencode($accessToken);
        $response = self::request($url);
        return $response['data'] ?? [];
    }

    public static function publishPost(string $pageId, string $accessToken, string $message, ?string $imageUrl = null): array
    {
        $endpoint = self::GRAPH_URL . "/{$pageId}/";
        $params = ['access_token' => $accessToken];

        if ($imageUrl) {
            $endpoint .= 'photos';
            $params['url'] = $imageUrl;
            $params['caption'] = $message;
        } else {
            $endpoint .= 'feed';
            $params['message'] = $message;
        }

        return self::request($endpoint, 'POST', $params);
    }

    public static function getInsights(string $pageId, string $accessToken): array
    {
        $url = self::GRAPH_URL . "/{$pageId}/insights?" . http_build_query([
            'metric' => 'page_impressions,page_engaged_users,page_post_engagements',
            'period' => 'day',
            'access_token' => $accessToken,
        ]);
        return self::request($url);
    }

    private static function request(string $url, string $method = 'GET', array $data = []): array
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        }

        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true) ?? [];
    }
}
