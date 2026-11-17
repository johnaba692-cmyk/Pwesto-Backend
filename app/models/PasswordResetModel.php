<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

/**
 * One-time password reset tokens. Only a SHA-256 hash of each token is stored.
 */
class PasswordResetModel extends Model
{
    protected $table = 'pwesto_password_resets';
    protected $primary_key = 'token_hash';
    protected $fillable = ['token_hash', 'user_id', 'expires_at', 'created_at'];

    /** @return array status 'created' (with token), 'not_found' or 'rate_limited' */
    public function create_for_email($email)
    {
        $user = $this->call->model('UserModel')->find_by_email($email);
        if (!$user) {
            return ['status' => 'not_found'];
        }
        $recent = $this->db->table($this->table)
            ->where('user_id', '=', $user['id'])
            ->where('created_at', '>=', gmdate('Y-m-d H:i:s', time() - 60))
            ->limit(1)
            ->get();
        if ($recent) {
            return ['status' => 'rate_limited'];
        }

        $token = random_id(32);
        $tokenHash = hash('sha256', $token);
        $this->db->transaction();
        try {
            $this->delete_where(['user_id' => $user['id']]);
            $this->insert([
                'token_hash' => $tokenHash,
                'user_id' => $user['id'],
                'expires_at' => gmdate('Y-m-d H:i:s', time() + 3600),
                'created_at' => utc_now(),
            ]);
            $this->db->commit();
        } catch (Throwable $error) {
            $this->db->roll_back();
            throw $error;
        }
        return [
            'status' => 'created',
            'token' => $token,
            'token_hash' => $tokenHash,
            'email' => $user['email'],
            'name' => $user['name'],
        ];
    }

    public function revoke($tokenHash)
    {
        $this->delete($tokenHash);
    }

    /** Set a new password with a valid token; all of the user's tokens are then removed. */
    public function reset($token, $password)
    {
        $this->db->transaction();
        try {
            $reset = $this->db->table($this->table)
                ->select('user_id')
                ->where('token_hash', '=', hash('sha256', $token))
                ->where('expires_at', '>', utc_now())
                ->limit(1)
                ->get();
            if (!$reset || $this->call->model('UserModel')->set_password($reset['user_id'], $password) !== 1) {
                $this->db->roll_back();
                return false;
            }
            $this->delete_where(['user_id' => $reset['user_id']]);
            $this->db->commit();
            return true;
        } catch (Throwable $error) {
            $this->db->roll_back();
            throw $error;
        }
    }
}
