<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

/**
 * Accounts (customers, vendors and administrators).
 */
class UserModel extends Model
{
    protected $table = 'pwesto_users';
    protected $primary_key = 'id';
    protected $fillable = [
        'id', 'google_sub', 'email', 'name', 'picture', 'profile_customized',
        'password_hash', 'role', 'created_at', 'updated_at',
    ];

    /** Public profile fields stored in the session. */
    public function public_profile($id)
    {
        $user = $this->db->table($this->table)
            ->select('id, email, name, picture, role')
            ->where('id', '=', $id)
            ->limit(1)
            ->get();
        if (!$user) {
            throw new RuntimeException('Pwesto account was not available after saving.');
        }
        return $user;
    }

    public function find_by_email($email)
    {
        return $this->db->table($this->table)
            ->where('email', '=', $email)
            ->limit(1)
            ->get() ?: null;
    }

    /** Create or update the account linked to a verified Google identity. */
    public function save_google_user(array $google, $role)
    {
        $this->db->transaction();
        try {
            $existing = $this->db->table($this->table)->where('google_sub', '=', $google['sub'])->limit(1)->get()
                ?: $this->find_by_email($google['email']);

            if (!$existing) {
                $id = $google['sub'];
                $now = utc_now();
                $this->insert([
                    'id' => $id,
                    'google_sub' => $google['sub'],
                    'email' => $google['email'],
                    'name' => $google['name'],
                    'picture' => $google['picture'],
                    'password_hash' => null,
                    'role' => $role,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            } else {
                $id = $existing['id'];
                $customized = (int) $existing['profile_customized'] === 1;
                $this->update($id, [
                    'google_sub' => $google['sub'],
                    'name' => $customized ? $existing['name'] : $google['name'],
                    'picture' => $customized ? $existing['picture'] : $google['picture'],
                    'role' => $role === 'admin' ? 'admin' : $existing['role'],
                    'updated_at' => utc_now(),
                ]);
            }
            $this->db->commit();
        } catch (Throwable $error) {
            $this->db->roll_back();
            throw $error;
        }
        return $this->public_profile($id);
    }

    /** @return array|null the new public profile, or null when the email is already registered */
    public function register($email, $name, $password, $role)
    {
        if ($this->exists(['email' => $email])) {
            return null;
        }
        $id = random_id();
        $now = utc_now();
        try {
            $this->insert([
                'id' => $id,
                'email' => $email,
                'name' => $name,
                'picture' => '',
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'role' => $role,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } catch (PDOException $error) {
            if (strpos($error->getMessage(), '23000') !== false) {
                return null;
            }
            throw $error;
        }
        return ['id' => $id, 'email' => $email, 'name' => $name, 'picture' => '', 'role' => $role];
    }

    /** @return array|null public profile when the password matches */
    public function authenticate($email, $password)
    {
        $user = $this->find_by_email($email);
        if (!$user || empty($user['password_hash']) || !password_verify($password, $user['password_hash'])) {
            return null;
        }
        if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
            $this->update($user['id'], [
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'updated_at' => utc_now(),
            ]);
        }
        return [
            'id' => $user['id'],
            'email' => $user['email'],
            'name' => $user['name'],
            'picture' => $user['picture'],
            'role' => $user['role'],
        ];
    }

    public function grant_admin($id)
    {
        $this->update($id, ['role' => 'admin', 'updated_at' => utc_now()]);
        return $this->public_profile($id);
    }

    public function update_profile($id, $name, $picture)
    {
        $this->update($id, [
            'name' => $name,
            'picture' => $picture,
            'profile_customized' => 1,
            'updated_at' => utc_now(),
        ]);
        return $this->public_profile($id);
    }

    public function set_password($id, $password)
    {
        return $this->update($id, [
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'updated_at' => utc_now(),
        ]);
    }

    /** Promote a stall owner to the vendor role unless they are an administrator. */
    public function promote_to_vendor($id)
    {
        return $this->db->table($this->table)
            ->where('id', '=', $id)
            ->where('role', '!=', 'admin')
            ->update(['role' => 'vendor', 'updated_at' => utc_now()]);
    }

    public function admin_ids()
    {
        return $this->pluck('id', ['role' => 'admin']);
    }

    public function count_by_role($role = null)
    {
        $this->db->table($this->table);
        if ($role !== null) {
            $this->db->where('role', '=', $role);
        }
        return (int) $this->db->count();
    }
}
