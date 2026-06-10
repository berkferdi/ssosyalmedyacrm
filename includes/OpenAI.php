<?php

class OpenAI
{
    private string $apiKey;
    private string $model;

    public function __construct()
    {
        $this->apiKey = get_api_setting('openai_api_key');
        $this->model = get_api_setting('openai_model') ?: (defined('OPENAI_MODEL') ? OPENAI_MODEL : 'gpt-4o-mini');
    }

    public function testConnection(): array
    {
        if (empty($this->apiKey)) {
            return ['success' => false, 'message' => 'OpenAI API anahtarı tanımlı değil. Ayarlar sayfasından kaydedin.'];
        }

        try {
            $this->request([
                'model' => $this->model,
                'messages' => [['role' => 'user', 'content' => 'Merhaba']],
                'max_tokens' => 5,
            ]);
            return ['success' => true, 'message' => 'OpenAI bağlantısı başarılı. Model: ' . $this->model];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function generateContent(string $imageDescription, ?string $context = null): array
    {
        if (empty($this->apiKey)) {
            return $this->fallbackContent($imageDescription);
        }

        $prompt = "Sen profesyonel bir sosyal medya içerik uzmanısın. Aşağıdaki görsel/içerik için Türkçe sosyal medya paylaşım metinleri oluştur.\n\n";
        $prompt .= "Görsel/İçerik: {$imageDescription}\n";
        if ($context) {
            $prompt .= "Ek Bağlam: {$context}\n";
        }
        $prompt .= "\nJSON formatında yanıt ver:\n";
        $prompt .= '{"title":"başlık","description":"açıklama metni","hashtags":"#etiket1 #etiket2","emojis":"uygun emojiler","seo_text":"SEO açıklaması","cta":"çağrı metni"}';

        try {
            $response = $this->request([
                'model' => $this->model,
                'messages' => [
                    ['role' => 'system', 'content' => 'Sen sosyal medya içerik uzmanısın. Her zaman geçerli JSON döndür.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => 0.7,
                'max_tokens' => 1000,
            ]);

            $content = $response['choices'][0]['message']['content'] ?? '';
            $content = preg_replace('/```json\s*|\s*```/', '', $content);
            $parsed = json_decode(trim($content), true);

            if ($parsed && isset($parsed['title'])) {
                return [
                    'success' => true,
                    'title' => $parsed['title'],
                    'description' => $parsed['description'] ?? '',
                    'hashtags' => $parsed['hashtags'] ?? '',
                    'emojis' => $parsed['emojis'] ?? '',
                    'seo_text' => $parsed['seo_text'] ?? '',
                    'cta' => $parsed['cta'] ?? '',
                ];
            }
        } catch (Exception $e) {
            app_log('openai', $e->getMessage(), Auth::id());
        }

        return $this->fallbackContent($imageDescription);
    }

    public function generateFromImage(string $imagePath, ?string $context = null): array
    {
        if (empty($this->apiKey) || !file_exists($imagePath)) {
            $filename = basename($imagePath);
            return $this->fallbackContent($filename);
        }

        $imageData = base64_encode(file_get_contents($imagePath));
        $mimeType = mime_content_type($imagePath);

        try {
            $response = $this->request([
                'model' => 'gpt-4o-mini',
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => [
                            [
                                'type' => 'text',
                                'text' => 'Bu görseli analiz et ve Türkçe sosyal medya içeriği oluştur. JSON formatında: {"title":"","description":"","hashtags":"","emojis":"","seo_text":"","cta":""}',
                            ],
                            [
                                'type' => 'image_url',
                                'image_url' => ['url' => "data:{$mimeType};base64,{$imageData}"],
                            ],
                        ],
                    ],
                ],
                'max_tokens' => 1000,
            ]);

            $content = $response['choices'][0]['message']['content'] ?? '';
            $content = preg_replace('/```json\s*|\s*```/', '', $content);
            $parsed = json_decode(trim($content), true);

            if ($parsed) {
                return array_merge(['success' => true], $parsed);
            }
        } catch (Exception $e) {
            app_log('openai', $e->getMessage(), Auth::id());
        }

        return $this->fallbackContent(basename($imagePath));
    }

    private function request(array $data): array
    {
        $ch = curl_init('https://api.openai.com/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey,
            ],
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_TIMEOUT => 60,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            $body = json_decode($response, true);
            $errorMsg = $body['error']['message'] ?? substr((string) $response, 0, 200);
            throw new Exception('OpenAI API hatası (HTTP ' . $httpCode . '): ' . $errorMsg);
        }

        $decoded = json_decode($response, true);
        if (!$decoded) {
            throw new Exception('OpenAI API geçersiz yanıt döndürdü');
        }

        return $decoded;
    }

    private function fallbackContent(string $description): array
    {
        $clean = Security::sanitize($description);
        return [
            'success' => true,
            'title' => ucfirst($clean),
            'description' => "{$clean} hakkında detaylı bilgi için bizi takip edin.",
            'hashtags' => '#sosyalmedya #içerik #paylaşım',
            'emojis' => '📱 ✨',
            'seo_text' => $clean,
            'cta' => 'Daha fazla bilgi için profilimizi ziyaret edin!',
        ];
    }
}
