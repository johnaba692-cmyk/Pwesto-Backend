<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

/**
 * Cloudinary storage for product images (public) and vendor requirement
 * documents (authenticated). Settings come from config_item('cloudinary');
 * the Cloudinary SDK is provided by Composer autoloading.
 *
 * Load with: $this->call->library('cloudinary_storage');
 */
class Cloudinary_storage
{
    /** @var \Cloudinary\Cloudinary|null */
    private $client;

    public static function isProductObject($publicId)
    {
        return is_string($publicId)
            && preg_match('/^cloudinary\/products\/[A-Za-z0-9_-]{1,64}\/[a-f0-9]{32}$/', $publicId) === 1;
    }

    public static function isVendorDocumentObject($publicId)
    {
        return is_string($publicId)
            && preg_match(
                '~^(?:[A-Za-z0-9_-]{1,64}/)*vendor-requirements/[A-Za-z0-9_-]{1,64}/(application_document|cedula_document|photo_document)/[a-f0-9]{32}\.(pdf|jpg|png)$~',
                $publicId
            ) === 1;
    }

    public function upload($filePath, $publicId)
    {
        $this->assertObjectName($publicId);
        $isPrivate = self::isVendorDocumentObject($publicId);
        $response = $this->client()->uploadApi()->upload($filePath, [
            'resource_type' => $isPrivate ? 'raw' : 'image',
            'type' => $isPrivate ? 'authenticated' : 'upload',
            'public_id' => $publicId,
            'overwrite' => false,
            'unique_filename' => false,
            'use_filename' => false,
            'filename' => $isPrivate ? basename($publicId) : null,
            'headers' => $isPrivate ? ['X-Robots-Tag: noindex'] : null,
        ]);
        $storedId = $response['public_id'] ?? null;
        $expectedSuffix = substr($publicId, strlen('cloudinary/'));
        if (!is_string($storedId)
            || !preg_match('~^(?:[A-Za-z0-9_-]{1,64}/)*' . preg_quote($expectedSuffix, '~') . '$~', $storedId)) {
            throw new RuntimeException('Cloudinary returned an unexpected asset identifier.');
        }
        return $storedId;
    }

    public function download($publicId)
    {
        $this->assertObjectName($publicId);
        if (self::isProductObject($publicId)) {
            $url = $this->publicImageUrl($publicId);
        } else {
            $extension = strtolower(pathinfo($publicId, PATHINFO_EXTENSION));
            $url = $this->client()->uploadApi()->privateDownloadUrl($publicId, $extension, [
                'resource_type' => 'raw',
                'type' => 'authenticated',
                'expires_at' => time() + 60,
            ]);
        }
        return $this->requestDownload($url);
    }

    public function delete($publicId)
    {
        $this->assertObjectName($publicId);
        $isPrivate = self::isVendorDocumentObject($publicId);
        $response = $this->client()->uploadApi()->destroy($publicId, [
            'resource_type' => $isPrivate ? 'raw' : 'image',
            'type' => $isPrivate ? 'authenticated' : 'upload',
            'invalidate' => true,
        ]);
        if (!in_array($response['result'] ?? '', ['ok', 'not found'], true)) {
            throw new RuntimeException('Cloudinary did not confirm asset deletion.');
        }
    }

    /** Delete without failing the request; cleanup errors are only logged. */
    public function deleteQuietly($publicId)
    {
        if (!self::isProductObject($publicId) && !self::isVendorDocumentObject($publicId)) {
            return;
        }
        try {
            $this->delete($publicId);
        } catch (Throwable $error) {
            error_log('Pwesto Cloudinary cleanup failed: ' . $error->getMessage());
        }
    }

    private function publicImageUrl($publicId)
    {
        $cloudName = $this->client()->configuration->cloud->cloudName;
        $encodedId = implode('/', array_map('rawurlencode', explode('/', $publicId)));
        return 'https://res.cloudinary.com/' . rawurlencode($cloudName) . '/image/upload/' . $encodedId;
    }

    private function client()
    {
        if ($this->client !== null) {
            return $this->client;
        }
        $settings = (array) config_item('cloudinary');
        $cloudName = $settings['cloud_name'] ?? '';
        $apiKey = $settings['api_key'] ?? '';
        $apiSecret = $settings['api_secret'] ?? '';
        if (!preg_match('/^[A-Za-z0-9_-]{1,128}$/', $cloudName)
            || !preg_match('/^[A-Za-z0-9]{1,128}$/', $apiKey)
            || $apiSecret === '') {
            throw new RuntimeException('Cloudinary is not configured.');
        }
        $this->client = new \Cloudinary\Cloudinary([
            'cloud' => [
                'cloud_name' => $cloudName,
                'api_key' => $apiKey,
                'api_secret' => $apiSecret,
            ],
        ]);
        return $this->client;
    }

    private function assertObjectName($publicId)
    {
        if (!self::isProductObject($publicId) && !self::isVendorDocumentObject($publicId)) {
            throw new RuntimeException('Cloudinary asset identifier is invalid.');
        }
    }

    private function requestDownload($url)
    {
        if (!function_exists('curl_init')) {
            throw new RuntimeException('PHP cURL is required for Cloudinary file downloads.');
        }
        $handle = curl_init($url);
        curl_setopt_array($handle, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
            CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTPS,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 45,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);
        $response = curl_exec($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
        if (!is_string($response)) {
            error_log('Pwesto Cloudinary download failed: ' . curl_error($handle));
            throw new RuntimeException('Cloudinary could not be reached.');
        }
        if ($status < 200 || $status >= 300) {
            error_log('Pwesto Cloudinary download returned HTTP ' . $status . '.');
            throw new RuntimeException('Cloudinary rejected the download request.');
        }
        return $response;
    }
}
