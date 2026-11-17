<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

/**
 * Planned market visits (create, list, cancel).
 */
class Appointments extends MY_Controller
{
    public function before_action()
    {
        parent::before_action();
        $this->call->model('AppointmentModel');
    }

    /** GET /api/appointments */
    public function index()
    {
        $user = $this->require_user();
        $this->respond(['appointments' => $this->planned($user['id'])]);
    }

    /** POST /api/appointments (JSON: vendor_id, visit_date, visit_time) */
    public function store()
    {
        $user = $this->require_user();
        $this->verify_csrf_token();
        $body = $this->input();
        $vendorId = $body['vendor_id'] ?? '';
        $visitDate = $body['visit_date'] ?? '';
        $visitTime = $body['visit_time'] ?? '';
        if (!is_string($vendorId) || $vendorId === '') {
            $this->fail('Choose a valid market stall.', 422);
        }
        if (!is_string($visitDate) || !$this->is_valid_visit_date($visitDate)) {
            $this->fail('Choose a valid visit date that is today or later.', 422);
        }
        if (!is_string($visitTime) || !in_array($visitTime, AppointmentModel::VISIT_TIMES, true)) {
            $this->fail('Choose an available visit time.', 422);
        }
        $planned = $this->db_call(function () use ($user, $vendorId, $visitDate, $visitTime) {
            return $this->AppointmentModel->plan($user['id'], $vendorId, $visitDate, $visitTime);
        }, 'Your visit could not be saved. Please try again.');
        if ($planned === false) {
            $this->fail('Choose a valid market stall.', 422);
        }
        $this->respond(['appointments' => $this->planned($user['id'])], 201);
    }

    /** DELETE /api/appointments (JSON: id) */
    public function destroy()
    {
        $user = $this->require_user();
        $this->verify_csrf_token();
        $id = $this->input()['id'] ?? '';
        if (!$this->is_hex_id($id, 32)) {
            $this->fail('Choose a valid visit to cancel.', 422);
        }
        $cancelled = $this->db_call(function () use ($user, $id) {
            return $this->AppointmentModel->cancel($user['id'], $id);
        }, 'Your visit could not be cancelled. Please try again.');
        if (!$cancelled) {
            $this->fail('This visit could not be cancelled. It may already be cancelled.', 404);
        }
        $this->respond(['appointments' => $this->planned($user['id'])]);
    }

    private function planned($userId)
    {
        return $this->db_call(function () use ($userId) {
            return $this->AppointmentModel->for_user($userId);
        }, 'Your visits could not be loaded.');
    }

    private function is_valid_visit_date($date)
    {
        $parsed = DateTime::createFromFormat('!Y-m-d', $date);
        $errors = DateTime::getLastErrors();
        return $parsed !== false
            && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))
            && $parsed->format('Y-m-d') === $date
            && $date >= date('Y-m-d');
    }
}
