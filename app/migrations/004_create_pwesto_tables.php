<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

/**
 * Pwesto schema (MySQL / InnoDB). Every statement uses IF NOT EXISTS, so it is
 * safe to run against a database that already contains these tables.
 *
 * Numbered 004 because migrations 000-003 (users and refresh_tokens for the
 * LavaLust Api library) were already applied to the shared class database.
 */
class Create_pwesto_tables
{
    private $_lava;

    private const OPTIONS = 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';

    public function __construct()
    {
        $this->_lava = lava_instance();
    }

    public function up()
    {
        foreach ($this->tables() as $definition) {
            $this->_lava->db->raw($definition . ' ' . self::OPTIONS);
        }
    }

    public function down()
    {
        $this->_lava->db->raw('SET FOREIGN_KEY_CHECKS = 0');
        foreach (array_reverse(array_keys($this->tables())) as $table) {
            $this->_lava->db->raw('DROP TABLE IF EXISTS `' . $table . '`');
        }
        $this->_lava->db->raw('SET FOREIGN_KEY_CHECKS = 1');
    }

    private function tables()
    {
        return [
            'pwesto_users' => "CREATE TABLE IF NOT EXISTS pwesto_users (
                id VARCHAR(255) NOT NULL PRIMARY KEY,
                google_sub VARCHAR(255) NULL UNIQUE,
                email VARCHAR(254) NOT NULL UNIQUE,
                name VARCHAR(120) NOT NULL,
                picture TEXT NOT NULL,
                profile_customized TINYINT NOT NULL DEFAULT 0,
                password_hash VARCHAR(255) NULL,
                role VARCHAR(20) NOT NULL DEFAULT 'customer',
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL
            )",
            'pwesto_vendors' => "CREATE TABLE IF NOT EXISTS pwesto_vendors (
                id VARCHAR(64) NOT NULL PRIMARY KEY,
                owner_id VARCHAR(255) NULL,
                name VARCHAR(100) NOT NULL,
                category VARCHAR(60) NOT NULL,
                market VARCHAR(100) NOT NULL,
                specialty VARCHAR(240) NOT NULL,
                rating DECIMAL(4,2) NOT NULL DEFAULT 0,
                latitude DECIMAL(10,7) NOT NULL,
                longitude DECIMAL(10,7) NOT NULL,
                icon VARCHAR(32) NOT NULL DEFAULT '⌂',
                image TEXT NOT NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'approved',
                application_document TEXT NULL,
                cedula_document TEXT NULL,
                photo_document TEXT NULL,
                rental_payment_status VARCHAR(20) NOT NULL DEFAULT 'unpaid',
                rental_checkout_id VARCHAR(255) NULL,
                rental_checkout_url TEXT NULL,
                rental_checkout_amount BIGINT NULL,
                rental_paid_at DATETIME NULL,
                rental_paid_until DATE NULL,
                created_at DATETIME NOT NULL,
                stall_code VARCHAR(24) NULL,
                UNIQUE KEY pwesto_vendors_stall_code_uq (stall_code),
                INDEX pwesto_vendors_market_idx (status, market),
                CONSTRAINT pwesto_vendors_owner_fk FOREIGN KEY (owner_id) REFERENCES pwesto_users(id) ON DELETE SET NULL
            )",
            'pwesto_stall_sequences' => "CREATE TABLE IF NOT EXISTS pwesto_stall_sequences (
                prefix VARCHAR(12) NOT NULL PRIMARY KEY,
                next_number INT NOT NULL
            )",
            'pwesto_favorites' => "CREATE TABLE IF NOT EXISTS pwesto_favorites (
                user_id VARCHAR(255) NOT NULL,
                vendor_id VARCHAR(64) NOT NULL,
                created_at DATETIME NOT NULL,
                PRIMARY KEY (user_id, vendor_id),
                CONSTRAINT pwesto_favorites_user_fk FOREIGN KEY (user_id) REFERENCES pwesto_users(id) ON DELETE CASCADE,
                CONSTRAINT pwesto_favorites_vendor_fk FOREIGN KEY (vendor_id) REFERENCES pwesto_vendors(id) ON DELETE CASCADE
            )",
            'pwesto_appointments' => "CREATE TABLE IF NOT EXISTS pwesto_appointments (
                id VARCHAR(64) NOT NULL PRIMARY KEY,
                user_id VARCHAR(255) NOT NULL,
                vendor_id VARCHAR(64) NOT NULL,
                visit_date DATE NOT NULL,
                visit_time VARCHAR(32) NOT NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'Planned',
                created_at DATETIME NOT NULL,
                INDEX pwesto_appointments_user_idx (user_id, visit_date),
                CONSTRAINT pwesto_appointments_user_fk FOREIGN KEY (user_id) REFERENCES pwesto_users(id) ON DELETE CASCADE,
                CONSTRAINT pwesto_appointments_vendor_fk FOREIGN KEY (vendor_id) REFERENCES pwesto_vendors(id) ON DELETE CASCADE
            )",
            'pwesto_auth_attempts' => "CREATE TABLE IF NOT EXISTS pwesto_auth_attempts (
                identity_hash CHAR(64) NOT NULL PRIMARY KEY,
                attempts INT NOT NULL,
                window_started BIGINT NOT NULL
            )",
            'pwesto_password_resets' => "CREATE TABLE IF NOT EXISTS pwesto_password_resets (
                token_hash CHAR(64) NOT NULL PRIMARY KEY,
                user_id VARCHAR(255) NOT NULL,
                expires_at DATETIME NOT NULL,
                created_at DATETIME NOT NULL,
                INDEX pwesto_password_resets_user_idx (user_id),
                CONSTRAINT pwesto_password_resets_user_fk FOREIGN KEY (user_id) REFERENCES pwesto_users(id) ON DELETE CASCADE
            )",
            'pwesto_conversations' => "CREATE TABLE IF NOT EXISTS pwesto_conversations (
                id VARCHAR(64) NOT NULL PRIMARY KEY,
                vendor_id VARCHAR(64) NOT NULL,
                customer_id VARCHAR(255) NOT NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                UNIQUE KEY pwesto_conversations_vendor_customer_idx (vendor_id, customer_id),
                INDEX pwesto_conversations_customer_idx (customer_id, updated_at),
                INDEX pwesto_conversations_vendor_idx (vendor_id, updated_at),
                CONSTRAINT pwesto_conversations_vendor_fk FOREIGN KEY (vendor_id) REFERENCES pwesto_vendors(id) ON DELETE CASCADE,
                CONSTRAINT pwesto_conversations_customer_fk FOREIGN KEY (customer_id) REFERENCES pwesto_users(id) ON DELETE CASCADE
            )",
            'pwesto_messages' => "CREATE TABLE IF NOT EXISTS pwesto_messages (
                id VARCHAR(64) NOT NULL PRIMARY KEY,
                conversation_id VARCHAR(64) NOT NULL,
                sender_id VARCHAR(255) NOT NULL,
                body TEXT NOT NULL,
                created_at DATETIME NOT NULL,
                read_at DATETIME NULL,
                INDEX pwesto_messages_conversation_idx (conversation_id, created_at),
                CONSTRAINT pwesto_messages_conversation_fk FOREIGN KEY (conversation_id) REFERENCES pwesto_conversations(id) ON DELETE CASCADE,
                CONSTRAINT pwesto_messages_sender_fk FOREIGN KEY (sender_id) REFERENCES pwesto_users(id) ON DELETE CASCADE
            )",
            'pwesto_notifications' => "CREATE TABLE IF NOT EXISTS pwesto_notifications (
                id VARCHAR(64) NOT NULL PRIMARY KEY,
                user_id VARCHAR(255) NOT NULL,
                type VARCHAR(60) NOT NULL,
                title VARCHAR(255) NOT NULL,
                message TEXT NOT NULL,
                created_at DATETIME NOT NULL,
                read_at DATETIME NULL,
                INDEX pwesto_notifications_user_idx (user_id, read_at, created_at),
                CONSTRAINT pwesto_notifications_user_fk FOREIGN KEY (user_id) REFERENCES pwesto_users(id) ON DELETE CASCADE
            )",
            'pwesto_products' => "CREATE TABLE IF NOT EXISTS pwesto_products (
                id VARCHAR(64) NOT NULL PRIMARY KEY,
                vendor_id VARCHAR(64) NOT NULL,
                name VARCHAR(100) NOT NULL,
                description VARCHAR(500) NOT NULL DEFAULT '',
                price_cents BIGINT NOT NULL,
                stock INT NOT NULL DEFAULT 0,
                image TEXT NULL,
                active TINYINT NOT NULL DEFAULT 1,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                INDEX pwesto_products_vendor_idx (vendor_id, active, name),
                CONSTRAINT pwesto_products_vendor_fk FOREIGN KEY (vendor_id) REFERENCES pwesto_vendors(id) ON DELETE CASCADE
            )",
            'pwesto_orders' => "CREATE TABLE IF NOT EXISTS pwesto_orders (
                id VARCHAR(64) NOT NULL PRIMARY KEY,
                customer_id VARCHAR(255) NOT NULL,
                vendor_id VARCHAR(64) NOT NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'pending',
                total_cents BIGINT NOT NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                delivery_method VARCHAR(20) NOT NULL DEFAULT 'pickup',
                delivery_address TEXT NULL,
                pickup_at DATETIME NULL,
                payment_method VARCHAR(32) NOT NULL DEFAULT 'cash_on_pickup',
                payment_status VARCHAR(20) NOT NULL DEFAULT 'due',
                payment_checkout_id VARCHAR(255) NULL,
                payment_checkout_url TEXT NULL,
                vendor_receipt_email_status VARCHAR(20) NOT NULL DEFAULT 'pending',
                vendor_receipt_email_attempted_at DATETIME NULL,
                INDEX pwesto_orders_customer_idx (customer_id, created_at),
                INDEX pwesto_orders_vendor_idx (vendor_id, status, created_at),
                CONSTRAINT pwesto_orders_customer_fk FOREIGN KEY (customer_id) REFERENCES pwesto_users(id) ON DELETE CASCADE,
                CONSTRAINT pwesto_orders_vendor_fk FOREIGN KEY (vendor_id) REFERENCES pwesto_vendors(id) ON DELETE CASCADE
            )",
            'pwesto_order_items' => "CREATE TABLE IF NOT EXISTS pwesto_order_items (
                id VARCHAR(64) NOT NULL PRIMARY KEY,
                order_id VARCHAR(64) NOT NULL,
                product_id VARCHAR(64) NULL,
                product_name VARCHAR(100) NOT NULL,
                unit_price_cents BIGINT NOT NULL,
                quantity INT NOT NULL,
                INDEX pwesto_order_items_order_idx (order_id),
                CONSTRAINT pwesto_order_items_order_fk FOREIGN KEY (order_id) REFERENCES pwesto_orders(id) ON DELETE CASCADE,
                CONSTRAINT pwesto_order_items_product_fk FOREIGN KEY (product_id) REFERENCES pwesto_products(id) ON DELETE SET NULL
            )",
        ];
    }
}
