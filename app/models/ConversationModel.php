<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

/**
 * Customer-to-stall conversations (one per customer and stall).
 */
class ConversationModel extends Model
{
    protected $table = 'pwesto_conversations';
    protected $primary_key = 'id';
    protected $fillable = ['id', 'vendor_id', 'customer_id', 'created_at', 'updated_at'];

    /** @return string|null conversation ID, or null when the stall cannot be messaged */
    public function start($customerId, $vendorId)
    {
        $ownerId = $this->db->table('pwesto_vendors')
            ->select('owner_id')
            ->where('id', '=', $vendorId)
            ->where('status', '=', 'approved')
            ->limit(1)
            ->get()['owner_id'] ?? null;
        if ($ownerId === null || $ownerId === $customerId) {
            return null;
        }
        $key = ['vendor_id' => $vendorId, 'customer_id' => $customerId];
        $existing = $this->value('id', $key);
        if ($existing !== null) {
            return $existing;
        }
        $now = utc_now();
        try {
            $this->insert(['id' => random_id()] + $key + ['created_at' => $now, 'updated_at' => $now]);
        } catch (PDOException $error) {
            // Created concurrently; the unique key guarantees a single conversation.
            if (strpos($error->getMessage(), '23000') === false) {
                throw $error;
            }
        }
        return $this->value('id', $key);
    }

    /** Conversations where the user is the customer or the stall owner, newest activity first. */
    public function for_user($userId)
    {
        $conversations = $this->db->table('pwesto_conversations c')
            ->select('c.id, c.vendor_id, c.customer_id, c.created_at, c.updated_at, v.name AS stall_name, v.market, owner.name AS owner_name, customer.name AS customer_name')
            ->inner_join('pwesto_vendors v', 'v.id = c.vendor_id')
            ->inner_join('pwesto_users customer', 'customer.id = c.customer_id')
            ->left_join('pwesto_users owner', 'owner.id = v.owner_id')
            ->grouped(function ($query) use ($userId) {
                $query->where('c.customer_id', '=', $userId)
                    ->or_where('v.owner_id', '=', $userId);
            })
            ->get_all();
        if (!$conversations) {
            return [];
        }
        $stats = $this->call->model('MessageModel')->summaries(array_column($conversations, 'id'), $userId);

        $result = [];
        foreach ($conversations as $conversation) {
            $summary = $stats[$conversation['id']] ?? ['last_message' => null, 'last_message_at' => null, 'unread_count' => 0];
            $result[] = [
                'id' => $conversation['id'],
                'vendor_id' => $conversation['vendor_id'],
                'customer_id' => $conversation['customer_id'],
                'stall_name' => $conversation['stall_name'],
                'market' => $conversation['market'],
                'other_name' => $conversation['customer_id'] === $userId
                    ? $conversation['owner_name']
                    : $conversation['customer_name'],
                'last_message' => $summary['last_message'],
                'last_message_at' => $summary['last_message_at'],
                'unread_count' => $summary['unread_count'],
                'sort_key' => $conversation['updated_at'] ?? $conversation['created_at'],
            ];
        }
        usort($result, function ($a, $b) {
            return strcmp((string) $b['sort_key'], (string) $a['sort_key']);
        });
        return array_map(function ($row) {
            unset($row['sort_key']);
            return $row;
        }, $result);
    }

    /** Customer, stall owner and stall name when the user takes part in the conversation. */
    public function participants($conversationId, $userId)
    {
        $conversation = $this->db->table('pwesto_conversations c')
            ->select('c.customer_id, v.owner_id, v.name AS stall_name')
            ->inner_join('pwesto_vendors v', 'v.id = c.vendor_id')
            ->where('c.id', '=', $conversationId)
            ->limit(1)
            ->get();
        if (!$conversation || ($conversation['customer_id'] !== $userId && $conversation['owner_id'] !== $userId)) {
            return null;
        }
        return $conversation;
    }

    public function touch($conversationId)
    {
        $this->update($conversationId, ['updated_at' => utc_now()]);
    }
}
