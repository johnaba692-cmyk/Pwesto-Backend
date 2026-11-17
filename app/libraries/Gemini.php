<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

/**
 * Gemini generateContent client with model fallbacks.
 * Settings come from config_item('gemini') in app/config/pwesto.php.
 *
 * Load with: $this->call->library('gemini');
 */
class Gemini
{
    public function configured()
    {
        return $this->settings()['api_key'] !== '' && function_exists('curl_init');
    }

    /**
     * @param string $instructions system instruction text
     * @param array  $history      [['role' => 'user'|'model', 'text' => ...], ...]
     * @param string $message      latest user message
     * @return string|null the answer text, or null when every model failed
     */
    public function answer($instructions, array $history, $message)
    {
        $settings = $this->settings();
        $contents = [];
        foreach ($history as $item) {
            $contents[] = ['role' => $item['role'], 'parts' => [['text' => $item['text']]]];
        }
        $contents[] = ['role' => 'user', 'parts' => [['text' => $message]]];
        $payload = json_encode([
            'systemInstruction' => ['parts' => [['text' => $instructions]]],
            'contents' => $contents,
            'generationConfig' => ['maxOutputTokens' => 1200, 'temperature' => 0.6],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $lastStatus = 0;
        $lastError = '';
        foreach ($settings['models'] as $model) {
            $handle = curl_init('https://generativelanguage.googleapis.com/v1beta/models/' . rawurlencode($model) . ':generateContent');
            curl_setopt_array($handle, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $payload,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_CONNECTTIMEOUT => 8,
                CURLOPT_TIMEOUT => 25,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
                CURLOPT_HTTPHEADER => [
                    'Accept: application/json',
                    'Content-Type: application/json',
                    'x-goog-api-key: ' . $settings['api_key'],
                ],
            ]);
            $responseBody = curl_exec($handle);
            $lastError = curl_error($handle);
            $lastStatus = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
            if ($responseBody === false) {
                continue;
            }
            $result = json_decode($responseBody, true);
            if ($lastStatus >= 200 && $lastStatus < 300 && is_array($result)) {
                $answer = '';
                foreach ($result['candidates'][0]['content']['parts'] ?? [] as $part) {
                    if (isset($part['text']) && is_string($part['text'])) {
                        $answer .= $part['text'];
                    }
                }
                return trim($answer);
            }
            if (in_array($lastStatus, [400, 401, 403], true)) {
                break;
            }
        }
        error_log('Pwesto Gemini request failed after model fallbacks (HTTP ' . $lastStatus
            . ($lastError !== '' ? ', ' . $lastError : '') . ').');
        return null;
    }

    private function settings()
    {
        return array_merge(['api_key' => '', 'models' => []], (array) config_item('gemini'));
    }
}
