<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

/**
 * Messages inside a conversation.
 */
class MessageModel extends Model
{
    protected $table = 'pwesto_messages';
    protected $primary_key = 'id';
    protected $fillable = ['id', 'conversation_id', 'sender_id', 'body', 'created_at', 'read_at'];

    /** Thread for a participant; marks the other person's messages as read. */
    public function thread($conversationId, $userId)
    {
        $conversations = $this->call->model('ConversationModel');
        if ($conversations->participants($conversationId, $userId) === null) {
            return null;
        }
        $this->db->table($this->table)
            ->where('conversation_id', '=', $conversationId)
            ->where('sender_id', '!=', $userId)
            ->where_null('read_at')
            ->update(['read_at' => utc_now()]);

        $messages = $this->db->table('pwesto_messages m')
            ->select('m.id, m.sender_id, u.name AS sender_name, m.body, m.created_at, m.read_at')
            ->inner_join('pwesto_users u', 'u.id = m.sender_id')
            ->where('m.conversation_id', '=', $conversationId)
            ->order_by('m.created_at', 'ASC')
            ->get_all();
        usort($messages, function ($a, $b) {
            return [$a['created_at'], $a['id']] <=> [$b['created_at'], $b['id']];
        });
        return $messages;
    }

    /** @return string|false the new message ID */
    public function send($conversationId, $senderId, $body)
    {
        $conversation = $this->call->model('ConversationModel')->participants($conversationId, $senderId);
        if ($conversation === null) {
            return false;
        }
        $recipientId = $conversation['customer_id'] === $senderId
            ? $conversation['owner_id']
            : $conversation['customer_id'];
        $senderName = $this->call->model('UserModel')->value('name', ['id' => $senderId]);

        $messageId = random_id();
        $this->db->transaction();
        try {
            $this->insert([
                'id' => $messageId,
                'conversation_id' => $conversationId,
                'sender_id' => $senderId,
                'body' => $body,
                'created_at' => utc_now(),
            ]);
            $this->call->model('ConversationModel')->touch($conversationId);
            if ($recipientId !== null && $senderName !== null) {
                $this->call->model('NotificationModel')->notify(
                    $recipientId,
                    'message',
                    $senderName . ' sent you a message',
                    'New message in your conversation about ' . $conversation['stall_name'] . '.'
                );
            }
            $this->db->commit();
            return $messageId;
        } catch (Throwable $error) {
            $this->db->roll_back();
            throw $error;
        }
    }

    /**
     * Last message and unread count for each conversation.
     *
     * @return array conversation ID => ['last_message', 'last_message_at', 'unread_count']
     */
    public function summaries(array $conversationIds, $readerId)
    {
        if (!$conversationIds) {
            return [];
        }
        $messages = $this->db->table($this->table)
            ->select('id, conversation_id, sender_id, body, created_at, read_at')
            ->in('conversation_id', $conversationIds)
            ->get_all();
        $summaries = [];
        foreach ($messages as $message) {
            $id = $message['conversation_id'];
            $summary = $summaries[$id] ?? ['last_message' => null, 'last_message_at' => null, 'unread_count' => 0, 'last_key' => null];
            $key = [$message['created_at'], $message['id']];
            if ($summary['last_key'] === null || $key > $summary['last_key']) {
                $summary['last_key'] = $key;
                $summary['last_message'] = $message['body'];
                $summary['last_message_at'] = $message['created_at'];
            }
            if ($message['sender_id'] !== $readerId && $message['read_at'] === null) {
                $summary['unread_count']++;
            }
            $summaries[$id] = $summary;
        }
        foreach ($summaries as &$summary) {
            unset($summary['last_key']);
        }
        unset($summary);
        return $summaries;
    }
}
