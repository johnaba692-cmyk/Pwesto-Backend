<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

/**
 * Failed sign-in counters per email and per IP address (stored as hashes).
 * Eight failures within 15 minutes lock further attempts for that window.
 */
class AuthAttemptModel extends Model
{
    protected $table = 'pwesto_auth_attempts';
    protected $primary_key = 'identity_hash';
    protected $fillable = ['identity_hash', 'attempts', 'window_started'];

    private const WINDOW_SECONDS = 900;
    private const MAX_ATTEMPTS = 8;

    public function allowed($email, $ip)
    {
        $records = $this->db->table($this->table)
            ->select('attempts, window_started')
            ->in('identity_hash', $this->identities($email, $ip))
            ->get_all();
        foreach ($records as $record) {
            if ((int) $record['window_started'] > time() - self::WINDOW_SECONDS
                && (int) $record['attempts'] >= self::MAX_ATTEMPTS) {
                return false;
            }
        }
        return true;
    }

    public function record_failure($email, $ip)
    {
        $now = time();
        foreach ($this->identities($email, $ip) as $identity) {
            $record = $this->find($identity);
            if (!$record) {
                try {
                    $this->insert(['identity_hash' => $identity, 'attempts' => 1, 'window_started' => $now]);
                    continue;
                } catch (PDOException $error) {
                    $record = $this->find($identity);
                    if (!$record) {
                        throw $error;
                    }
                }
            }
            if ((int) $record['window_started'] <= $now - self::WINDOW_SECONDS) {
                $this->update($identity, ['attempts' => 1, 'window_started' => $now]);
            } else {
                $this->db->table($this->table)->where('identity_hash', '=', $identity)->increment('attempts');
            }
        }
    }

    public function clear($email, $ip)
    {
        $this->db->table($this->table)->in('identity_hash', $this->identities($email, $ip))->delete();
    }

    private function identities($email, $ip)
    {
        return [
            hash('sha256', 'email:' . strtolower($email)),
            hash('sha256', 'ip:' . $ip),
        ];
    }
}
