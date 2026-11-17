<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

/**
 * In-app notifications of the signed-in user.
 */
class Notifications extends MY_Controller
{
    public function before_action()
    {
        parent::before_action();
        $this->call->model('NotificationModel');
    }

    /** GET /api/notifications */
    public function index()
    {
        $user = $this->require_user();
        $this->respond($this->latest($user['id'], 'Your notifications could not be loaded.'));
    }

    /** POST /api/notifications (JSON: action mark_read with notification_id, or mark_all_read) */
    public function update()
    {
        $user = $this->require_user();
        $this->verify_csrf_token();
        $body = $this->input();
        $action = $body['action'] ?? '';
        if ($action === 'mark_all_read') {
            $this->db_call(function () use ($user) {
                $this->NotificationModel->mark_all_read($user['id']);
            }, 'Your notifications could not be updated.');
        } elseif ($action === 'mark_read' && $this->is_hex_id($body['notification_id'] ?? null, 32)) {
            $marked = $this->db_call(function () use ($user, $body) {
                return $this->NotificationModel->mark_read($user['id'], $body['notification_id']);
            }, 'Your notification could not be updated.');
            if (!$marked) {
                $this->fail('That notification is not available or has already been read.', 404);
            }
        } else {
            $this->fail('Choose a valid notification action.', 422);
        }
        $this->respond($this->latest($user['id'], 'Your notifications were updated but could not be refreshed.'));
    }

    private function latest($userId, $message)
    {
        return $this->db_call(function () use ($userId) {
            return $this->NotificationModel->for_user($userId);
        }, $message);
    }
}
