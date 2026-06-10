<?php

class LinkedInAPI
{
    private const API_URL = 'https://api.linkedin.com/v2';

    public static function getAuthUrl(): string
    {
        $params = http_build_query([
            'response_type' => 'code',
            'client_id' => get_api_setting('linkedin_client_id'),
            'redirect_uri' => LINKEDIN_REDIRECT_URI,
            'scope' => 'r_organization_social w_organization_social rw_organization_admin',
            'state' => Security::generateCSRFToken(),
        ]);
        return 'https://www.linkedin.com/oauth/v2/authorization?' . $params;
    }

    public static function exchangeCode(string $code): array
    {
        $ch = curl_init('https://www.linkedin.com/oauth/v2/accessToken');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => LINKEDIN_REDIRECT_URI,
                'client_id' => get_api_setting('linkedin_client_id'),
                'client_secret' => get_api_setting('linkedin_client_secret'),
            ]),
        ]);
        $response = curl_exec($ch);
        curl_close($ch);
        return json_decode($response, true) ?? [];
    }

    public static function getOrganizations(string $accessToken): array
    {
        $url = self::API_URL . '/organizationAcls?q=roleAssignee&role=ADMINISTRATOR';
        return self::request($url, $accessToken);
    }

    public static function publishPost(string $orgId, string $accessToken, string $text): array
    {
        $data = [
            'author' => "urn:li:organization:{$orgId}",
            'lifecycleState' => 'PUBLISHED',
            'specificContent' => [
                'com.linkedin.ugc.ShareContent' => [
                    'shareCommentary' => ['text' => $text],
                    'shareMediaCategory' => 'NONE',
                ],
            ],
            'visibility' => [
                'com.linkedin.ugc.MemberNetworkVisibility' => 'PUBLIC',
            ],
        ];

        return self::request(self::API_URL . '/ugcPosts', $accessToken, 'POST', $data);
    }

    private static function request(string $url, string $accessToken, string $method = 'GET', ?array $data = null): array
    {
        $ch = curl_init($url);
        $headers = [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json',
            'X-Restli-Protocol-Version: 2.0.0',
        ];
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
        ]);

        if ($method === 'POST' && $data) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        curl_close($ch);
        return json_decode($response, true) ?? [];
    }
}
