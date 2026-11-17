<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

/**
 * In-app notifications shown in the account dashboard.
 */
class NotificationModel extends Model
{
    protected $table = 'pwesto_notifications';
    protected $primary_key = 'id';
    protected $fillable = ['id', 'user_id', 'type', 'title', 'message', 'created_at', 'read_at'];

    public function notify($userId, $type, $title, $message)
    {
        if ($userId === null || $userId === false || $userId === '') {
            return;
        }
        $this->insert([
            'id' => random_id(),
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'created_at' => utc_now(),
        ]);
    }

    public function notify_admins($type, $title, $message)
    {
        $users = lava_instance()->call->model('UserModel');
        foreach ($users->admin_ids() as $adminId) {
            $this->notify($adminId, $type, $title, $message);
        }
    }

    /** Latest 50 notifications plus the unread count. */
    public function for_user($userId)
    {
        $notifications = $this->db->table($this->table)
            ->select('id, type, title, message, created_at, read_at')
            ->where('user_id', '=', $userId)
            ->order_by('created_at', 'DESC')
            ->limit(50)
            ->get_all();
        $unread = $this->db->table($this->table)
            ->where('user_id', '=', $userId)
            ->where_null('read_at')
            ->count();
        return ['notifications' => $notifications, 'unread_count' => (int) $unread];
    }

    /** @return bool true when the notification belongs to the user */
    public function mark_read($userId, $notificationId)
    {
        $updated = $this->db->table($this->table)
            ->where('id', '=', $notificationId)
            ->where('user_id', '=', $userId)
            ->where_null('read_at')
            ->update(['read_at' => utc_now()]);
        return $updated === 1 || $this->exists(['id' => $notificationId, 'user_id' => $userId]);
    }

    public function mark_all_read($userId)
    {
        $this->db->table($this->table)
            ->where('user_id', '=', $userId)
            ->where_null('read_at')
            ->update(['read_at' => utc_now()]);
    }
}
