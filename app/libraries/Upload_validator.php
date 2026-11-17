<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

/**
 * Raised when an uploaded file fails validation. The code is the HTTP status.
 */
class Upload_rejected extends RuntimeException
{
}

/**
 * Server-side validation for uploaded files: checks the real MIME type with
 * finfo (not the client-supplied type), image dimensions, an explicit
 * allow-list, and a maximum size.
 *
 * Load with: $this->call->library('upload_validator');
 */
class Upload_validator
{
    public const IMAGE_TYPES = [
        'image/jpeg' => ['jpg', IMAGETYPE_JPEG],
        'image/png' => ['png', IMAGETYPE_PNG],
        'image/webp' => ['webp', IMAGETYPE_WEBP],
    ];

    public const DOCUMENT_TYPES = [
        'application/pdf' => ['pdf', null],
        'image/jpeg' => ['jpg', IMAGETYPE_JPEG],
        'image/png' => ['png', IMAGETYPE_PNG],
    ];

    public const PHOTO_TYPES = [
        'image/jpeg' => ['jpg', IMAGETYPE_JPEG],
        'image/png' => ['png', IMAGETYPE_PNG],
    ];

    /**
     * Validate $_FILES[$field].
     *
     * Rules: required (bool), max_bytes (int), types (MIME allow-list),
     * square_photo (bool), and messages: missing, too_large, invalid, type, image.
     *
     * @return array|null ['tmp_name', 'extension', 'mime_type'] or null when optional and absent
     * @throws Upload_rejected
     */
    public function validate($field, array $rules)
    {
        $required = $rules['required'] ?? false;
        $messages = $rules['messages'];
        $file = $_FILES[$field] ?? null;
        if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            if ($required) {
                throw new Upload_rejected($messages['missing'], 422);
            }
            return null;
        }
        if (in_array($file['error'] ?? null, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
            throw new Upload_rejected($messages['too_large'], 413);
        }
        if (($file['error'] ?? null) !== UPLOAD_ERR_OK || !is_string($file['tmp_name'] ?? null)
            || !is_uploaded_file($file['tmp_name'])) {
            throw new Upload_rejected($messages['invalid'], 422);
        }
        $size = filesize($file['tmp_name']);
        if ($size === false || $size < 1 || $size > $rules['max_bytes']) {
            throw new Upload_rejected($messages['too_large'], 413);
        }
        if (!function_exists('finfo_open') || !function_exists('getimagesize')) {
            throw new Upload_rejected('File validation is unavailable on this server.', 503);
        }
        $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = $fileInfo === false ? false : finfo_file($fileInfo, $file['tmp_name']);
        $types = $rules['types'];
        if (!is_string($mimeType) || !isset($types[$mimeType])) {
            throw new Upload_rejected($messages['type'], 422);
        }

        [$extension, $imageType] = $types[$mimeType];
        if ($imageType !== null) {
            $image = @getimagesize($file['tmp_name']);
            if ($image === false || $image[2] !== $imageType || $image[0] < 1 || $image[1] < 1
                || $image[0] > 5000 || $image[1] > 5000 || $image[0] * $image[1] > 20000000) {
                throw new Upload_rejected($messages['image'], 422);
            }
            if (!empty($rules['square_photo']) && ($image[0] < 300 || $image[1] < 300
                || min($image[0], $image[1]) / max($image[0], $image[1]) < 0.85)) {
                throw new Upload_rejected('Use a square 2×2 photo image at least 300 × 300 pixels.', 422);
            }
        }
        return [
            'tmp_name' => $file['tmp_name'],
            'extension' => $extension,
            'mime_type' => $mimeType,
        ];
    }
}
