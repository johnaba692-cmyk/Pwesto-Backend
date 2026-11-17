<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

/**
 * Minimal Xendit Invoice API client used for GCash checkout.
 * Settings come from config_item('xendit') in app/config/pwesto.php.
 *
 * Load with: $this->call->library('xendit');
 */
class Xendit
{
    private const CHECKOUT_ID_PATTERN = '/^[A-Za-z0-9_-]{1,100}$/';

    /** True when the secret key matches the selected mode and a webhook token is set. */
    public function configured()
    {
        $settings = $this->settings();
        if (!in_array($settings['mode'], ['test', 'live'], true)) {
            return false;
        }
        $prefixes = $settings['mode'] === 'live'
            ? ['xnd_production-', 'xnd_production_']
            : ['xnd_development-', 'xnd_development_'];
        $keyMatchesMode = false;
        foreach ($prefixes as $prefix) {
            if (strpos($settings['secret_key'], $prefix) === 0) {
                $keyMatchesMode = true;
                break;
            }
        }
        return $keyMatchesMode && $settings['webhook_token'] !== '';
    }

    /**
     * Create a GCash invoice and verify that Xendit echoed the expected values.
     *
     * @return array|null ['id' => ..., 'url' => ...] or null when the invoice is invalid
     */
    public function createInvoice($externalId, $amountCents, $description, array $metadata, $payerEmail = null)
    {
        $payload = [
            'external_id' => $externalId,
            'amount' => $amountCents / 100,
            'currency' => 'PHP',
            'invoice_duration' => 86400,
            'description' => $description,
            'payment_methods' => ['GCASH'],
            'metadata' => $metadata,
        ];
        if ($payerEmail !== null && $payerEmail !== '') {
            $payload['payer_email'] = $payerEmail;
        }
        $response = $this->request('POST', '/v2/invoices', $payload);
        $invoice = $response['body'];
        $checkoutId = $invoice['id'] ?? '';
        $checkoutUrl = $invoice['invoice_url'] ?? '';
        if ($response['status'] < 200 || $response['status'] >= 300
            || !$this->isCheckoutId($checkoutId)
            || !$this->isCheckoutUrl($checkoutUrl)
            || ($invoice['external_id'] ?? '') !== $externalId
            || $this->amountCents($invoice['amount'] ?? null) !== $amountCents
            || ($invoice['currency'] ?? '') !== 'PHP') {
            error_log('Pwesto Xendit invoice creation failed with HTTP ' . $response['status']);
            return null;
        }
        return ['id' => $checkoutId, 'url' => $checkoutUrl];
    }

    /** @return array|null decoded invoice, or null when Xendit could not be reached */
    public function getInvoice($checkoutId)
    {
        if (!$this->isCheckoutId($checkoutId)) {
            return null;
        }
        $response = $this->request('GET', '/v2/invoices/' . rawurlencode($checkoutId));
        if ($response['status'] < 200 || $response['status'] >= 300) {
            error_log('Pwesto Xendit invoice lookup failed with HTTP ' . $response['status']);
            return null;
        }
        return $response['body'];
    }

    public function webhookTokenMatches($providedToken)
    {
        $expected = $this->settings()['webhook_token'];
        return $expected !== '' && is_string($providedToken)
            && hash_equals($expected, trim($providedToken));
    }

    public function webhookConfigured()
    {
        return $this->settings()['webhook_token'] !== '';
    }

    public function isCheckoutId($value)
    {
        return is_string($value) && preg_match(self::CHECKOUT_ID_PATTERN, $value) === 1;
    }

    public function amountCents($amount)
    {
        if (!is_numeric($amount) || !is_finite((float) $amount) || (float) $amount < 0) {
            return null;
        }
        $amountCents = round((float) $amount * 100);
        return $amountCents <= PHP_INT_MAX ? (int) $amountCents : null;
    }

    private function isCheckoutUrl($url)
    {
        if (!is_string($url) || !filter_var($url, FILTER_VALIDATE_URL)
            || parse_url($url, PHP_URL_SCHEME) !== 'https') {
            return false;
        }
        return in_array(parse_url($url, PHP_URL_HOST), ['checkout.xendit.co', 'checkout-staging.xendit.co'], true);
    }

    private function request($method, $path, ?array $body = null)
    {
        $settings = $this->settings();
        $headers = [
            'Accept: application/json',
            'Authorization: Basic ' . base64_encode($settings['secret_key'] . ':'),
        ];
        $options = [
            'method' => $method,
            'timeout' => 15,
            'ignore_errors' => true,
            'header' => implode("\r\n", $headers) . "\r\n",
        ];
        if ($body !== null) {
            $options['header'] .= "Content-Type: application/json\r\n";
            $options['content'] = json_encode($body, JSON_UNESCAPED_SLASHES);
        }
        $context = stream_context_create([
            'http' => $options,
            'ssl' => ['verify_peer' => true, 'verify_peer_name' => true],
        ]);
        $responseBody = @file_get_contents($settings['base_url'] . $path, false, $context);
        $statusLine = $http_response_header[0] ?? '';
        $status = preg_match('/\s(\d{3})\s/', $statusLine, $matches) ? (int) $matches[1] : 0;
        $decoded = is_string($responseBody) ? json_decode($responseBody, true) : null;
        return ['status' => $status, 'body' => is_array($decoded) ? $decoded : []];
    }

    private function settings()
    {
        return array_merge([
            'mode' => 'test',
            'secret_key' => '',
            'webhook_token' => '',
            'base_url' => 'https://api.xendit.co',
        ], (array) config_item('xendit'));
    }
}
