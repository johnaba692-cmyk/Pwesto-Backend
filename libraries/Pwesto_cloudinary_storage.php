<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

require_once ROOT_DIR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

class Pwesto_cloudinary_storage
{
    private $cloudinary;

    public function __construct()
    {
        $cloudName = getenv('CLOUDINARY_CLOUD_NAME');
        $apiKey = getenv('CLOUDINARY_API_KEY');
        $apiSecret = getenv('CLOUDINARY_API_SECRET');
        if (!is_string($cloudName) || !preg_match('/^[A-Za-z0-9_-]{1,128}$/', $cloudName)
            || !is_string($apiKey) || !preg_match('/^[A-Za-z0-9]{1,128}$/', $apiKey)
            || !is_string($apiSecret) || trim($apiSecret) === '') {
            throw new RuntimeException('Cloudinary is not configured.');
        }

        $this->cloudinary = new \Cloudinary\Cloudinary([
            'cloud' => [
                'cloud_name' => $cloudName,
                'api_key' => trim($apiKey),
                'api_secret' => trim($apiSecret),
            ],
        ]);
    }

    public function upload($filePath, $publicId)
    {
        $this->assertObjectName($publicId);
        $isPrivate = self::isVendorDocumentObject($publicId);
        $response = $this->cloudinary->uploadApi()->upload($filePath, [
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
        if (!is_string($storedId) || $storedId !== $publicId) {
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
            $url = $this->cloudinary->uploadApi()->privateDownloadUrl($publicId, $extension, [
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
        $response = $this->cloudinary->uploadApi()->destroy($publicId, [
            'resource_type' => $isPrivate ? 'raw' : 'image',
            'type' => $isPrivate ? 'authenticated' : 'upload',
            'invalidate' => true,
        ]);
        if (!in_array($response['result'] ?? '', ['ok', 'not found'], true)) {
            throw new RuntimeException('Cloudinary did not confirm asset deletion.');
        }
    }

    public function publicImageUrl($publicId)
    {
        if (!self::isProductObject($publicId)) {
            throw new RuntimeException('Cloudinary product image identifier is invalid.');
        }
        $cloudName = $this->cloudinary->configuration->cloud->cloudName;
        $segments = explode('/', $publicId);
        $encodedId = implode('/', array_map('rawurlencode', $segments));
        return 'https://res.cloudinary.com/' . rawurlencode($cloudName)
            . '/image/upload/' . $encodedId;
    }

    public static function isProductObject($publicId)
    {
        return is_string($publicId)
            && preg_match('/^cloudinary\/products\/[A-Za-z0-9_-]{1,64}\/[a-f0-9]{32}$/', $publicId);
    }

    public static function isVendorDocumentObject($publicId)
    {
        return is_string($publicId)
            && preg_match(
                '/^cloudinary\/vendor-requirements\/[A-Za-z0-9_-]{1,64}\/(application_document|cedula_document|photo_document)\/[a-f0-9]{32}\.(pdf|jpg|png)$/',
                $publicId
            );
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
        if ($handle === false) {
            throw new RuntimeException('Cloudinary download request could not be initialized.');
        }
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
        $curlError = curl_error($handle);
        curl_close($handle);
        if (!is_string($response)) {
            error_log('Pwesto Cloudinary download failed: ' . $curlError);
            throw new RuntimeException('Cloudinary could not be reached.');
        }
        if ($status < 200 || $status >= 300) {
            error_log('Pwesto Cloudinary download returned HTTP ' . $status . '.');
            throw new RuntimeException('Cloudinary rejected the download request.');
        }
        return $response;
    }
}
