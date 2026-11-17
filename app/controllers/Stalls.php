<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

/**
 * Stall rental applications owned by the signed-in user: apply, edit the
 * listing, replace requirement documents and pay the monthly rent.
 */
class Stalls extends MY_Controller
{
    private const DOCUMENT_FIELDS = [
        'application_document' => 'document',
        'cedula_document' => 'document',
        'photo_document' => 'photo',
    ];
    private const DOCUMENT_LABELS = [
        'application_document' => 'application form',
        'cedula_document' => 'barangay cedula',
        'photo_document' => '2×2 photo',
    ];

    public function before_action()
    {
        parent::before_action();
        $this->call->model('VendorModel');
        $this->call->library(['cloudinary_storage', 'upload_validator', 'xendit']);
    }

    /** GET /api/vendors/mine */
    public function mine()
    {
        $user = $this->require_user();
        $this->respond([
            'vendors' => $this->owned_stalls($user['id']),
            'rental_fee' => $this->monthly_rental_fee(),
            'payment_configured' => $this->xendit->configured(),
        ]);
    }

    /** POST /api/vendors (multipart: stall details and three requirement documents) */
    public function apply()
    {
        $user = $this->require_user();
        $this->verify_csrf_token();
        $this->reject_oversized_form_request();
        $body = $this->input();
        $stall = $this->validated_stall($body, ['Seafood', 'Meat', 'Vegetables', 'Fruits', 'Pantry', 'Flowers']);
        $rentalFee = $this->monthly_rental_fee();

        $documents = [];
        foreach (self::DOCUMENT_FIELDS as $field => $kind) {
            $documents[$field] = $this->validated_document($field, $kind, true);
        }
        $vendorId = random_id(12);
        $stored = $this->upload_documents($documents, $vendorId);
        try {
            $this->VendorModel->create_application($stall + $stored + [
                'id' => $vendorId,
                'owner_id' => $user['id'],
            ]);
        } catch (Throwable $error) {
            $this->delete_documents($stored);
            error_log('Pwesto vendor application failed: ' . get_class($error) . ': ' . $error->getMessage());
            $this->fail('Pwesto could not process the application. Check that the files are valid and try again.', 503);
        }
        $this->respond([
            'vendor_id' => $vendorId,
            'vendors' => $this->owned_stalls($user['id'], 'Your stall application was submitted but could not be refreshed. Reload the dashboard.'),
            'rental_fee' => $rentalFee,
            'payment_configured' => $this->xendit->configured(),
        ], 201);
    }

    /** POST /api/vendors/update (JSON: vendor_id and stall details) */
    public function update()
    {
        $user = $this->require_user();
        $this->verify_csrf_token();
        if (($user['role'] ?? '') !== 'vendor') {
            $this->fail('Only vendor accounts can edit stall listings.', 403);
        }
        $body = $this->input();
        $vendorId = $body['vendor_id'] ?? '';
        if (!$this->is_hex_id($vendorId, 24)) {
            $this->fail('Choose a valid stall listing to update.', 422);
        }
        $stall = $this->validated_stall($body, ['Produce', 'Seafood', 'Meat', 'Vegetables', 'Fruits', 'Pantry', 'Flowers']);
        $updated = $this->db_call(function () use ($vendorId, $user, $stall) {
            return $this->VendorModel->update_listing($vendorId, $user['id'], $stall);
        }, 'Your stall profile could not be updated. Please try again.');
        if (!$updated) {
            $this->fail('That stall listing is not available to your account.', 404);
        }
        $this->respond(['vendors' => $this->owned_stalls($user['id'])]);
    }

    /** POST /api/vendors/requirements (multipart: vendor_id and any of the three documents) */
    public function documents()
    {
        $user = $this->require_user();
        $this->verify_csrf_token();
        $this->reject_oversized_form_request();
        $vendorId = $this->input()['vendor_id'] ?? '';
        $this->owned_application($vendorId, $user['id']);

        $documents = [];
        foreach (self::DOCUMENT_FIELDS as $field => $kind) {
            $document = $this->validated_document($field, $kind, false);
            if ($document !== null) {
                $documents[$field] = $document;
            }
        }
        if (!$documents) {
            $this->fail('Choose at least one document to upload.', 422);
        }
        $stored = $this->upload_documents($documents, $vendorId);
        try {
            $previous = $this->VendorModel->replace_documents($vendorId, $user['id'], $stored);
        } catch (Throwable $error) {
            $this->delete_documents($stored);
            error_log('Pwesto vendor requirement update failed: ' . $error->getMessage());
            $this->fail('Your requirements could not be updated. Please try again.', 503);
        }
        if ($previous === false) {
            $this->delete_documents($stored);
            $this->fail('That stall application is not available to your account.', 404);
        }
        $this->delete_documents($previous);
        $this->respond(['vendors' => $this->owned_stalls($user['id'], 'Your documents were saved but the application could not be refreshed.')]);
    }

    /** POST /api/vendors/rental-payment (JSON: vendor_id) */
    public function rental_payment()
    {
        $user = $this->require_user();
        $this->verify_csrf_token();
        if (!in_array($user['role'] ?? '', ['vendor', 'customer'], true)) {
            $this->fail('This account cannot submit a stall rental payment.', 403);
        }
        $vendorId = $this->input()['vendor_id'] ?? '';
        $vendor = $this->owned_application($vendorId, $user['id']);
        if (empty($vendor['has_application']) || empty($vendor['has_cedula']) || empty($vendor['has_photo'])) {
            $this->fail('Submit all required documents before starting rental payment.', 422);
        }
        if ($vendor['rental_payment_status'] === 'paid') {
            $this->respond(['payment_status' => 'paid']);
        }
        if (!empty($vendor['rental_checkout_url'])) {
            $this->respond(['checkout_url' => $vendor['rental_checkout_url'], 'payment_status' => $vendor['rental_payment_status']]);
        }
        if (!$this->xendit->configured()) {
            $this->fail('Online payment is not configured for the selected Xendit mode. Ask the Pwesto administrator to set matching server credentials.', 503);
        }

        $amountCents = $this->monthly_rental_fee() * 100;
        $invoice = $this->xendit->createInvoice(
            'pwesto-rent-' . $vendorId . '-' . random_id(6),
            $amountCents,
            'Pwesto stall rental — one month',
            ['pwesto_vendor_id' => $vendorId, 'pwesto_payment_type' => 'rental']
        );
        if ($invoice === null) {
            $this->fail('Xendit could not create a checkout. Please try again or contact the administrator.', 502);
        }
        $saved = $this->db_call(function () use ($vendorId, $user, $invoice, $amountCents) {
            return $this->VendorModel->save_rental_checkout($vendorId, $user['id'], $invoice['id'], $invoice['url'], $amountCents);
        }, 'The rental checkout could not be saved.');
        if (!$saved) {
            $this->fail('The rental checkout could not be attached to this application.', 409);
        }
        $this->respond(['checkout_url' => $invoice['url'], 'payment_status' => 'pending']);
    }

    /** POST /api/vendors/rental-payment/check (JSON: vendor_id) */
    public function check_rental_payment()
    {
        $user = $this->require_user();
        $this->verify_csrf_token();
        $vendorId = $this->input()['vendor_id'] ?? '';
        $vendor = $this->owned_application($vendorId, $user['id']);
        if ($vendor['rental_payment_status'] === 'paid') {
            $this->respond(['payment_status' => 'paid']);
        }
        if (!$this->xendit->configured()) {
            $this->fail('Xendit checkout is not configured on the server.', 503);
        }
        $checkoutId = $vendor['rental_checkout_id'] ?? '';
        $amount = $vendor['rental_checkout_amount'] ?? null;
        if (!$this->xendit->isCheckoutId($checkoutId) || !is_numeric($amount) || (int) $amount < 100) {
            $this->fail('The Xendit invoice is not attached to this rental application.', 409);
        }
        $amount = (int) $amount;
        $invoice = $this->xendit->getInvoice($checkoutId);
        if ($invoice === null) {
            $this->fail('Xendit could not verify this payment. Try again shortly.', 502);
        }
        $externalIdPattern = '/^pwesto-rent-' . preg_quote($vendorId, '/') . '-[a-f0-9]{12}$/';
        if (($invoice['id'] ?? '') !== $checkoutId
            || !is_string($invoice['external_id'] ?? null)
            || !preg_match($externalIdPattern, $invoice['external_id'])
            || $this->xendit->amountCents($invoice['amount'] ?? null) !== $amount
            || ($invoice['currency'] ?? '') !== 'PHP') {
            $this->fail('The Xendit invoice does not match this rental application. Contact the Pwesto administrator.', 409);
        }
        if (($invoice['status'] ?? '') !== 'PAID') {
            $this->respond(['payment_status' => 'pending']);
        }
        $recorded = $this->db_call(function () use ($vendorId, $checkoutId, $amount) {
            return $this->VendorModel->mark_rental_paid($vendorId, $checkoutId, $amount)
                || $this->VendorModel->rental_payment_recorded($vendorId, $checkoutId, $amount);
        }, 'The confirmed stall rental payment could not be recorded.');
        if (!$recorded) {
            $this->fail('The payment no longer matches this rental application. Contact the Pwesto administrator.', 409);
        }
        $this->respond(['payment_status' => 'paid']);
    }

    /* ------------------------------------------------------------------ */

    private function owned_stalls($ownerId, $message = 'Your stall applications could not be loaded.')
    {
        return $this->db_call(function () use ($ownerId) {
            return $this->VendorModel->for_owner($ownerId);
        }, $message);
    }

    /** The owner's application, or a 422/404 response. */
    private function owned_application($vendorId, $ownerId)
    {
        if (!$this->is_hex_id($vendorId, 24)) {
            $this->fail('Choose a valid stall application.', 422);
        }
        $vendor = $this->db_call(function () use ($vendorId, $ownerId) {
            return $this->VendorModel->find_for_owner($vendorId, $ownerId);
        }, 'Your stall application could not be loaded.');
        if ($vendor === null) {
            $this->fail('That stall application is not available to your account.', 404);
        }
        return $vendor;
    }

    private function validated_stall(array $body, array $categories)
    {
        $name = $this->string_input($body, 'name');
        $category = $body['category'] ?? '';
        $market = $body['market'] ?? '';
        $specialty = $this->string_input($body, 'specialty');
        if ($this->text_length($name) < 2 || $this->text_length($name) > 100) {
            $this->fail('Enter a stall name between 2 and 100 characters.', 422);
        }
        if (!is_string($category) || !in_array($category, $categories, true)) {
            $this->fail('Choose a valid stall category.', 422);
        }
        if (!is_string($market) || !in_array($market, $this->VendorModel->markets(), true)) {
            $this->fail('Choose a supported public market.', 422);
        }
        if ($this->text_length($specialty) < 5 || $this->text_length($specialty) > 240) {
            $this->fail('Describe your specialties in 5 to 240 characters.', 422);
        }
        return ['name' => $name, 'category' => $category, 'market' => $market, 'specialty' => $specialty];
    }

    private function validated_document($field, $kind, $required)
    {
        try {
            return $this->upload_validator->validate($field, [
                'required' => $required,
                'max_bytes' => 2097152,
                'types' => $kind === 'photo' ? Upload_validator::PHOTO_TYPES : Upload_validator::DOCUMENT_TYPES,
                'square_photo' => $kind === 'photo',
                'messages' => [
                    'missing' => 'Upload the application form, barangay cedula, and 2×2 photo to continue.',
                    'too_large' => 'Each requirement document must be no larger than 2 MB.',
                    'invalid' => $required
                        ? 'Upload all three required files before submitting the application.'
                        : 'The selected document could not be uploaded. Choose a valid file and try again.',
                    'type' => $kind === 'photo'
                        ? 'The 2×2 photo must be a JPEG or PNG image.'
                        : 'Application form and cedula files must be PDF, JPEG, or PNG.',
                    'image' => 'The uploaded image is invalid or exceeds 5,000 × 5,000 pixels.',
                ],
            ]);
        } catch (Upload_rejected $rejected) {
            $this->fail($rejected->getMessage(), $rejected->getCode());
        }
    }

    /** Upload documents as authenticated (private) Cloudinary assets. */
    private function upload_documents(array $documents, $vendorId)
    {
        $stored = [];
        foreach ($documents as $column => $document) {
            $objectName = 'cloudinary/vendor-requirements/' . $vendorId . '/' . $column . '/'
                . random_id() . '.' . $document['extension'];
            try {
                $stored[$column] = $this->cloudinary_storage->upload($document['tmp_name'], $objectName);
            } catch (Throwable $error) {
                $this->delete_documents($stored);
                error_log('Pwesto vendor document upload failed for ' . self::DOCUMENT_LABELS[$column]
                    . ' (' . $document['mime_type'] . '): ' . get_class($error) . ': ' . $error->getMessage());
                $this->fail('The ' . self::DOCUMENT_LABELS[$column] . ' could not be saved. Check the server log for the upload error and try again.', 503);
            }
        }
        return $stored;
    }

    private function delete_documents(array $objectNames)
    {
        foreach ($objectNames as $objectName) {
            $this->cloudinary_storage->deleteQuietly($objectName);
        }
    }

    private function reject_oversized_form_request()
    {
        $contentLength = $_SERVER['CONTENT_LENGTH'] ?? '';
        $maxBytes = $this->ini_size_bytes(ini_get('post_max_size'));
        if (ctype_digit((string) $contentLength) && $maxBytes > 0 && (int) $contentLength > $maxBytes) {
            $this->fail('The combined upload is too large. Keep each requirement file under 2 MB and try again.', 413);
        }
    }

    private function ini_size_bytes($value)
    {
        $value = trim((string) $value);
        $number = (float) $value;
        if ($value === '' || $number <= 0) {
            return 0;
        }
        $multipliers = ['g' => 1073741824, 'm' => 1048576, 'k' => 1024];
        return (int) ($number * ($multipliers[strtolower(substr($value, -1))] ?? 1));
    }
}
