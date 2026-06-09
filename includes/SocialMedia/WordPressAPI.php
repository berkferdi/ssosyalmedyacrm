<?php

class WordPressAPI
{
    public static function testConnection(string $siteUrl, string $username, string $appPassword): array
    {
        $url = rtrim($siteUrl, '/') . '/wp-json/wp/v2/users/me';
        return self::request($url, $username, $appPassword);
    }

    public static function createPost(
        string $siteUrl,
        string $username,
        string $appPassword,
        string $title,
        string $content,
        string $status = 'draft',
        array $categories = [],
        array $tags = []
    ): array {
        $url = rtrim($siteUrl, '/') . '/wp-json/wp/v2/posts';

        $data = [
            'title' => $title,
            'content' => $content,
            'status' => $status,
        ];

        if ($categories) {
            $data['categories'] = $categories;
        }
        if ($tags) {
            $data['tags'] = $tags;
        }

        return self::request($url, $username, $appPassword, 'POST', $data);
    }

    public static function getCategories(string $siteUrl, string $username, string $appPassword): array
    {
        $url = rtrim($siteUrl, '/') . '/wp-json/wp/v2/categories?per_page=100';
        return self::request($url, $username, $appPassword);
    }

    public static function getTags(string $siteUrl, string $username, string $appPassword): array
    {
        $url = rtrim($siteUrl, '/') . '/wp-json/wp/v2/tags?per_page=100';
        return self::request($url, $username, $appPassword);
    }

    public static function uploadMedia(string $siteUrl, string $username, string $appPassword, string $filePath): array
    {
        $url = rtrim($siteUrl, '/') . '/wp-json/wp/v2/media';
        $filename = basename($filePath);
        $mimeType = mime_content_type($filePath);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_USERPWD => $username . ':' . $appPassword,
            CURLOPT_HTTPHEADER => [
                'Content-Disposition: attachment; filename="' . $filename . '"',
                'Content-Type: ' . $mimeType,
            ],
            CURLOPT_POSTFIELDS => file_get_contents($filePath),
            CURLOPT_TIMEOUT => 60,
        ]);

        $response = curl_exec($ch);
        curl_close($ch);
        return json_decode($response, true) ?? [];
    }

    private static function request(
        string $url,
        string $username,
        string $appPassword,
        string $method = 'GET',
        ?array $data = null
    ): array {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERPWD => $username . ':' . $appPassword,
            CURLOPT_TIMEOUT => 30,
        ]);

        if ($method === 'POST' && $data) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $result = json_decode($response, true) ?? [];
        $result['_http_code'] = $httpCode;
        return $result;
    }
}
