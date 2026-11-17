<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

/**
 * Sessions, email/password and Google sign-in, logout and password resets.
 */
class Auth extends MY_Controller
{
    /** Form_validation rules shared by registration and password reset. */
    private const PASSWORD_RULES = [
        'password|Password' => 'required|min_length[8]|max_length[72]',
        'confirm_password|Password confirmation' => 'required|matches[password]',
    ];
    private const PASSWORD_MESSAGES = [
        'password' => [
            'required' => 'Choose a password between 8 and 72 characters.',
            'min_length' => 'Choose a password between 8 and 72 characters.',
            'max_length' => 'Choose a password between 8 and 72 characters.',
        ],
        'confirm_password' => [
            'required' => 'Your passwords do not match.',
            'matches' => 'Your passwords do not match.',
        ],
    ];

    public function before_action()
    {
        parent::before_action();
        $this->call->model(['UserModel', 'FavoriteModel', 'AppointmentModel']);
    }

    /** GET /api/session */
    public function session()
    {
        $user = $this->current_user();
        $this->respond(array_merge(
            ['user' => $user, 'csrf_token' => $this->csrf_token()],
            $user ? $this->account_data($user['id']) : ['favorites' => [], 'appointments' => []]
        ));
    }

    /** POST /api/auth/google */
    public function google()
    {
        $this->verify_csrf_token();
        $this->api->rate_limit('auth:' . $this->client_ip(), 20, 60);
        $this->call->library('google_identity');
        $credential = $this->input()['credential'] ?? '';
        if (!$this->google_identity->configured()) {
            $this->fail('Google sign-in is not configured. Set GOOGLE_CLIENT_ID on the server.', 503);
        }
        if (!is_string($credential) || $credential === '' || strlen($credential) > 8192) {
            $this->fail('A valid Google sign-in credential is required.', 400);
        }
        try {
            $claims = $this->google_identity->verify($credential);
        } catch (Throwable $error) {
            error_log('Pwesto ' . $error->getMessage());
            $this->fail('Google sign-in could not be reached. Please try again shortly.', 502);
        }
        if ($claims === null) {
            $this->fail('Google could not verify this sign-in. Please try again.', 401);
        }
        $role = $this->is_admin_email($claims['email']) ? 'admin' : 'customer';
        $user = $this->db_call(function () use ($claims, $role) {
            return $this->UserModel->save_google_user($claims, $role);
        }, 'Your account could not be saved. Please try again.');
        $this->start_user_session($user);
    }

    /** POST /api/auth/register */
    public function register()
    {
        $this->verify_csrf_token();
        $this->api->rate_limit('auth:' . $this->client_ip(), 20, 60);
        $body = $this->input();
        $nameError = 'Enter your name (2 to 80 characters).';
        $this->validate_input($body, [
            'email|Email' => 'required|valid_email|max_length[254]',
            'name|Name' => 'required|min_length[2]|max_length[80]',
        ] + self::PASSWORD_RULES, [
            'email' => ['required' => 'Enter a valid email address.', 'valid_email' => 'Enter a valid email address.', 'max_length' => 'Enter a valid email address.'],
            'name' => ['required' => $nameError, 'min_length' => $nameError, 'max_length' => $nameError],
        ] + self::PASSWORD_MESSAGES);
        $email = $this->normalized_email($_POST['email']);
        $name = $_POST['name'];
        $password = $_POST['password'];
        $requestedRole = $body['role'] ?? 'customer';

        if (!is_string($requestedRole) || !in_array($requestedRole, ['customer', 'vendor'], true)) {
            $this->fail('Choose a customer or vendor account.', 422);
        }
        $role = $this->is_admin_email($email) ? 'admin' : $requestedRole;

        $user = $this->db_call(function () use ($email, $name, $password, $role) {
            return $this->UserModel->register($email, $name, $password, $role);
        }, 'Your account could not be created. Please try again.');
        if ($user === null) {
            $this->fail('An account with this email already exists. Sign in instead.', 409);
        }
        $this->start_user_session($user, false);
    }

    /** POST /api/auth/login */
    public function login()
    {
        $this->verify_csrf_token();
        $ip = $this->client_ip();
        $this->api->rate_limit('auth:' . $ip, 20, 60);
        $invalid = 'Enter a valid email address and password.';
        $this->validate_input($this->input(), [
            'email|Email' => 'required|valid_email|max_length[254]',
            'password|Password' => 'required|max_length[72]',
        ], [
            'email' => ['required' => $invalid, 'valid_email' => $invalid, 'max_length' => $invalid],
            'password' => ['required' => $invalid, 'max_length' => $invalid],
        ]);
        $email = $this->normalized_email($_POST['email']);
        $password = $_POST['password'];

        $this->call->model('AuthAttemptModel');
        $unavailable = 'Sign-in is temporarily unavailable. Please try again later.';
        $allowed = $this->db_call(function () use ($email, $ip) {
            return $this->AuthAttemptModel->allowed($email, $ip);
        }, $unavailable);
        if (!$allowed) {
            $this->fail('Too many sign-in attempts. Please wait 15 minutes before trying again.', 429);
        }

        $user = $this->db_call(function () use ($email, $password) {
            return $this->UserModel->authenticate($email, $password);
        }, $unavailable);
        if ($user === null) {
            $this->db_call(function () use ($email, $ip) {
                $this->AuthAttemptModel->record_failure($email, $ip);
            }, $unavailable);
            // Deliberately vague: does not reveal whether the email exists.
            $this->fail('The email or password is incorrect.', 401);
        }

        $this->db_call(function () use ($email, $ip) {
            $this->AuthAttemptModel->clear($email, $ip);
        }, $unavailable);
        if ($this->is_admin_email($email)) {
            $user = $this->db_call(function () use ($user) {
                return $this->UserModel->grant_admin($user['id']);
            }, 'Your administrator role could not be verified. Please try again.');
        }
        $this->start_user_session($user);
    }

    /** POST /api/auth/logout */
    public function logout()
    {
        $this->verify_csrf_token();
        $this->session->unset_userdata('pwesto_user');
        $this->session->sess_regenerate(true);
        $this->respond(['user' => null, 'csrf_token' => $this->rotate_csrf_token()]);
    }

    /** POST /api/auth/password/forgot */
    public function forgot_password()
    {
        $this->verify_csrf_token();
        $this->api->rate_limit('password-reset:' . $this->client_ip(), 5, 60);
        $email = $this->normalized_email($this->input()['email'] ?? null);
        $generic = ['message' => 'If that email belongs to a Pwesto account, a password reset link will be sent.'];
        if ($email === null) {
            $this->respond($generic);
        }

        $this->call->library('mailer');
        $baseUrl = $this->app_url();
        $mailError = $this->mailer->configurationError();
        if ($mailError !== null || $baseUrl === null) {
            error_log('Pwesto password reset unavailable: ' . ($mailError ?: 'PWESTO_APP_URL is invalid or not configured.'));
            $this->fail($mailError ?: 'Set a valid PWESTO_APP_URL in the local .env, then restart PHP.', 503);
        }

        $this->call->model('PasswordResetModel');
        $unavailable = 'Password reset is temporarily unavailable. Please try again later.';
        $reset = $this->db_call(function () use ($email) {
            return $this->PasswordResetModel->create_for_email($email);
        }, $unavailable);
        if ($reset['status'] !== 'created') {
            error_log('Pwesto password reset not sent: ' . $reset['status'] . '.');
            $this->respond($generic);
        }

        $resetUrl = $baseUrl . '/?password_reset=' . rawurlencode($reset['token']);
        $safeName = htmlspecialchars($reset['name'], ENT_QUOTES, 'UTF-8');
        $safeUrl = htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8');
        $html = '<p>Hello ' . $safeName . ',</p><p>We received a request to reset your Pwesto password.</p>'
            . '<p><a href="' . $safeUrl . '">Reset your password</a></p>'
            . '<p>This link expires in one hour. If you did not request this, you can ignore this email.</p>';
        $text = 'Hello ' . $reset['name'] . ",\n\n"
            . "Reset your Pwesto password using this one-time link (expires in one hour):\n"
            . $resetUrl . "\n\nIf you did not request this, ignore this email.";
        try {
            $this->mailer->send($reset['email'], $reset['name'], 'Reset your Pwesto password', $html, $text);
        } catch (Throwable $error) {
            $this->db_call(function () use ($reset) {
                $this->PasswordResetModel->revoke($reset['token_hash']);
            }, $unavailable);
            error_log('Pwesto password reset email failed: ' . $error->getMessage());
            $this->fail('The reset email could not be sent. Check the Gmail SMTP settings and use a Google App Password, not your regular account password.', 503);
        }
        $this->respond($generic);
    }

    /** POST /api/auth/password/reset */
    public function reset_password()
    {
        $this->verify_csrf_token();
        $body = $this->input();
        $token = $body['token'] ?? '';
        if (!$this->is_hex_id($token, 64)) {
            $this->fail('This password reset link is invalid or expired. Request a new one.', 422);
        }
        $this->validate_input($body, self::PASSWORD_RULES, self::PASSWORD_MESSAGES);
        $password = $_POST['password'];

        $this->call->model('PasswordResetModel');
        $reset = $this->db_call(function () use ($token, $password) {
            return $this->PasswordResetModel->reset($token, $password);
        }, 'Your password could not be reset. Please request a new link.');
        if (!$reset) {
            $this->fail('This password reset link is invalid or expired. Request a new one.', 422);
        }
        $this->respond(['success' => true, 'message' => 'Your password has been updated. You can now sign in.']);
    }

    /* ------------------------------------------------------------------ */

    private function start_user_session(array $user, $loadAccountData = true)
    {
        // Issue a fresh session ID after authentication (prevents session fixation).
        $this->session->after_successful_login();
        $this->session->set_userdata('pwesto_user', $user);
        $this->respond(array_merge(
            ['user' => $user, 'csrf_token' => $this->rotate_csrf_token()],
            $loadAccountData ? $this->account_data($user['id']) : ['favorites' => [], 'appointments' => []]
        ));
    }

    private function account_data($userId)
    {
        return $this->db_call(function () use ($userId) {
            return [
                'favorites' => $this->FavoriteModel->vendor_ids($userId),
                'appointments' => $this->AppointmentModel->for_user($userId),
            ];
        }, 'Your account data could not be loaded.');
    }

    private function client_ip()
    {
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }

    private function app_url()
    {
        $url = (string) config_item('pwesto_app_url');
        $parts = $url === '' ? false : parse_url($url);
        if (!is_array($parts) || empty($parts['scheme']) || empty($parts['host'])
            || isset($parts['user']) || isset($parts['pass']) || isset($parts['query']) || isset($parts['fragment'])) {
            return null;
        }
        $isLocalHttp = $parts['scheme'] === 'http'
            && in_array(strtolower($parts['host']), ['localhost', '127.0.0.1', '::1'], true);
        if ($parts['scheme'] !== 'https' && !$isLocalHttp) {
            return null;
        }
        return rtrim($url, '/');
    }
}
