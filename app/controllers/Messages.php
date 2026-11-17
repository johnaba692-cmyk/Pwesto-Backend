<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

/**
 * Direct messages between customers and stall owners.
 */
class Messages extends MY_Controller
{
    public function before_action()
    {
        parent::before_action();
        $this->call->model(['ConversationModel', 'MessageModel']);
    }

    /** GET /api/messages */
    public function index()
    {
        $user = $this->require_messaging_user();
        $conversations = $this->db_call(function () use ($user) {
            return $this->ConversationModel->for_user($user['id']);
        }, 'Your messages could not be loaded.');
        $this->respond(['conversations' => $conversations]);
    }

    /** GET /api/messages/thread?conversation_id= */
    public function thread()
    {
        $user = $this->require_messaging_user();
        $conversationId = $this->query('conversation_id');
        if (!$this->is_hex_id($conversationId, 32)) {
            $this->fail('Choose a valid conversation.', 422);
        }
        $messages = $this->db_call(function () use ($conversationId, $user) {
            return $this->MessageModel->thread($conversationId, $user['id']);
        }, 'This conversation could not be loaded.');
        if ($messages === null) {
            $this->fail('This conversation is not available to your account.', 404);
        }
        $this->respond(['messages' => $messages]);
    }

    /** POST /api/messages (JSON: message, and conversation_id or vendor_id to start one) */
    public function send()
    {
        $user = $this->require_messaging_user();
        $this->verify_csrf_token();
        $body = $this->input();
        $message = $this->string_input($body, 'message');
        if ($message === '' || $this->text_length($message) > 2000) {
            $this->fail('Write a message between 1 and 2,000 characters.', 422);
        }

        $conversationId = $body['conversation_id'] ?? null;
        if ($conversationId === null) {
            $vendorId = $body['vendor_id'] ?? '';
            if (($user['role'] ?? 'customer') !== 'customer'
                || !is_string($vendorId) || !preg_match('/^[a-z0-9][a-z0-9-]{0,63}$/', $vendorId)) {
                $this->fail('Choose an available vendor to start a conversation.', 422);
            }
            $conversationId = $this->db_call(function () use ($user, $vendorId) {
                return $this->ConversationModel->start($user['id'], $vendorId);
            }, 'A conversation with this stall could not be started.');
            if ($conversationId === null) {
                $this->fail('This stall is not available for direct messages.', 404);
            }
        } elseif (!$this->is_hex_id($conversationId, 32)) {
            $this->fail('Choose a valid conversation.', 422);
        }

        $sent = $this->db_call(function () use ($conversationId, $user, $message) {
            return $this->MessageModel->send($conversationId, $user['id'], $message);
        }, 'Your message could not be sent.');
        if (!$sent) {
            $this->fail('This conversation is not available to your account.', 404);
        }
        $messages = $this->db_call(function () use ($conversationId, $user) {
            return $this->MessageModel->thread($conversationId, $user['id']);
        }, 'Your conversation could not be loaded.');
        $this->respond(['conversation_id' => $conversationId, 'messages' => $messages], 201);
    }

    private function require_messaging_user()
    {
        $user = $this->require_user();
        if (!in_array($user['role'] ?? 'customer', ['customer', 'vendor'], true)) {
            $this->fail('Messaging is available to customers and vendors.', 403);
        }
        return $user;
    }
}
