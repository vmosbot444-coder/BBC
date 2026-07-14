<?php
/*
 * ============================================================
 *  Made by Bapan | Date: 5/4/2026
 *  All credits belongs to Bapan
 *  For any kind of software development job, cheat, website
 *  or panel development — contact Bapan:
 *  Telegram: https://t.me/bapanff
 *  Official Channel: https://t.me/mocosn
 * ============================================================
 */
class TelegramSchema {
    public static function getStatements() {
        return [
            "CREATE TABLE IF NOT EXISTS bot_config (
                id INT PRIMARY KEY DEFAULT 1,
                bot_token VARCHAR(255) DEFAULT '',
                bot_username VARCHAR(100) DEFAULT '',
                bot_name VARCHAR(100) DEFAULT '',
                rp_key_id VARCHAR(100) DEFAULT '',
                rp_key_secret VARCHAR(255) DEFAULT '',
                rp_webhook_secret VARCHAR(255) DEFAULT '',
                rp_mode ENUM('test','live') DEFAULT 'test',
                is_active TINYINT(1) DEFAULT 0,
                reset_limit_per_day INT DEFAULT 3,
                reset_cooldown_minutes INT DEFAULT 30,
                apk_download_url VARCHAR(500) DEFAULT '',
                setup_video_url VARCHAR(500) DEFAULT '',
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB",

            "CREATE TABLE IF NOT EXISTS bot_plans (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                description TEXT,
                duration_days INT NOT NULL,
                price_paise INT NOT NULL,
                discount_price_paise INT DEFAULT NULL,
                is_active TINYINT(1) DEFAULT 1,
                sort_order INT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB",

            "CREATE TABLE IF NOT EXISTS bot_users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                telegram_id BIGINT UNIQUE NOT NULL,
                username VARCHAR(100) DEFAULT '',
                first_name VARCHAR(100) DEFAULT '',
                last_name VARCHAR(100) DEFAULT '',
                first_seen TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                last_active TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_telegram_id (telegram_id)
            ) ENGINE=InnoDB",

            "CREATE TABLE IF NOT EXISTS bot_orders (
                id INT AUTO_INCREMENT PRIMARY KEY,
                bot_user_id INT NOT NULL,
                plan_id INT NOT NULL,
                amount_paise INT NOT NULL,
                razorpay_order_id VARCHAR(100) DEFAULT '',
                razorpay_payment_id VARCHAR(100) DEFAULT '',
                status ENUM('pending','paid','failed','refunded') DEFAULT 'pending',
                key_id INT DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                paid_at TIMESTAMP NULL,
                FOREIGN KEY (bot_user_id) REFERENCES bot_users(id) ON DELETE CASCADE,
                FOREIGN KEY (plan_id) REFERENCES bot_plans(id) ON DELETE CASCADE,
                INDEX idx_status (status),
                INDEX idx_razorpay (razorpay_order_id)
            ) ENGINE=InnoDB",


            "CREATE TABLE IF NOT EXISTS bot_broadcasts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                message TEXT NOT NULL,
                recipient_count INT DEFAULT 0,
                target_type VARCHAR(20) DEFAULT 'all',
                sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB",

            "CREATE TABLE IF NOT EXISTS bot_key_resets (
                id INT AUTO_INCREMENT PRIMARY KEY,
                key_id INT NOT NULL,
                telegram_id BIGINT NOT NULL,
                reset_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (key_id) REFERENCES `keys`(id) ON DELETE CASCADE,
                INDEX idx_key_date (key_id, reset_at),
                INDEX idx_telegram (telegram_id)
            ) ENGINE=InnoDB",

            "CREATE TABLE IF NOT EXISTS client_features (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                category VARCHAR(50) NOT NULL,
                type ENUM('toggle','slider') NOT NULL DEFAULT 'toggle',
                toggle_id INT NOT NULL,
                default_value VARCHAR(50) DEFAULT '0',
                min_value FLOAT DEFAULT 0,
                max_value FLOAT DEFAULT 100,
                step FLOAT DEFAULT 1,
                unit VARCHAR(10) DEFAULT '',
                is_active TINYINT(1) DEFAULT 1,
                sort_order INT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY idx_toggle_id (toggle_id)
            ) ENGINE=InnoDB"
        ];
    }

    public static function seedConfig($pdo) {
        $pdo->exec("INSERT IGNORE INTO bot_config (id) VALUES (1)");
    }
}
