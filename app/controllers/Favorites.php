<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

/**
 * Saved stalls of the signed-in user.
 */
class Favorites extends MY_Controller
{
    public function before_action()
    {
        parent::before_action();
        $this->call->model('FavoriteModel');
    }

    /** GET /api/favorites */
    public function index()
    {
        $user = $this->require_user();
        $this->respond(['favorites' => $this->saved($user['id'])]);
    }

    /** POST /api/favorites (JSON: vendor_id, saved) */
    public function update()
    {
        $user = $this->require_user();
        $this->verify_csrf_token();
        $body = $this->input();
        $vendorId = $body['vendor_id'] ?? null;
        $saved = $body['saved'] ?? null;
        if (!is_string($vendorId) || $vendorId === '' || !is_bool($saved)) {
            $this->fail('A stall and saved status are required.', 422);
        }
        $updated = $this->db_call(function () use ($user, $vendorId, $saved) {
            return $this->FavoriteModel->set_saved($user['id'], $vendorId, $saved);
        }, 'Your saved stalls could not be updated.');
        if (!$updated) {
            $this->fail('That market stall is not available.', 404);
        }
        $this->respond(['favorites' => $this->saved($user['id'])]);
    }

    private function saved($userId)
    {
        return $this->db_call(function () use ($userId) {
            return $this->FavoriteModel->vendor_ids($userId);
        }, 'Your saved stalls could not be loaded.');
    }
}
