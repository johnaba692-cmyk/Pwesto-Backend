<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

/**
 * Verifies Google Identity Services ID tokens against the client ID in
 * config_item('google_client_id').
 *
 * Load with: $this->call->library('google_identity');
 */
class Google_identity
{
    public function configured()
    {
        return $this->clientId() !== '';
    }

    /**
     * @return array|null verified claims (sub, email, name, picture) or null if the token is not valid
     * @throws RuntimeException when Google cannot be reached
     */
    public function verify($credential)
    {
        $url = 'https://oauth2.googleapis.com/tokeninfo?id_token=' . rawurlencode($credential);
        if (!function_exists('curl_init')) {
            throw new RuntimeException('PHP cURL is required for Google sign-in.');
        }
        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CONNECTTIMEOUT => 4,
            CURLOPT_TIMEOUT => 8,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
        ]);
        $responseBody = curl_exec($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        if ($responseBody === false) {
            throw new RuntimeException('Google sign-in verification failed: ' . curl_error($curl));
        }
        if ($status !== 200) {
            return null;
        }

        $claims = json_decode($responseBody, true);
        if (!is_array($claims)) {
            return null;
        }
        $validIssuer = in_array($claims['iss'] ?? '', ['accounts.google.com', 'https://accounts.google.com'], true);
        $validEmail = in_array($claims['email_verified'] ?? false, [true, 'true', 1, '1'], true);
        $validExpiry = isset($claims['exp']) && ctype_digit((string) $claims['exp']) && (int) $claims['exp'] > time();
        $validIdentity = !empty($claims['sub']) && !empty($claims['email']) && !empty($claims['name']);
        if (($claims['aud'] ?? '') !== $this->clientId()
            || !$validIssuer || !$validEmail || !$validExpiry || !$validIdentity) {
            return null;
        }

        $picture = $claims['picture'] ?? '';
        return [
            'sub' => substr((string) $claims['sub'], 0, 255),
            'email' => strtolower(substr((string) $claims['email'], 0, 254)),
            'name' => substr((string) $claims['name'], 0, 120),
            'picture' => is_string($picture) && filter_var($picture, FILTER_VALIDATE_URL)
                && parse_url($picture, PHP_URL_SCHEME) === 'https'
                ? substr($picture, 0, 2048)
                : '',
        ];
    }

    private function clientId()
    {
        return (string) config_item('google_client_id');
    }
}
