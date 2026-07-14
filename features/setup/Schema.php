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
class Schema {
    public static function getStatements() {
        return [
            "CREATE TABLE IF NOT EXISTS admins (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(50) UNIQUE NOT NULL,
                password VARCHAR(255) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB",

            "CREATE TABLE IF NOT EXISTS sellers (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(50) UNIQUE NOT NULL,
                password VARCHAR(255) NOT NULL,
                tokens INT DEFAULT 0,
                total_earned INT DEFAULT 0,
                total_spent INT DEFAULT 0,
                is_active TINYINT(1) DEFAULT 1,
                created_by INT,
                last_login TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (created_by) REFERENCES admins(id) ON DELETE SET NULL
            ) ENGINE=InnoDB",

            "CREATE TABLE IF NOT EXISTS `keys` (
                id INT AUTO_INCREMENT PRIMARY KEY,
                license_key VARCHAR(30) UNIQUE NOT NULL,
                status ENUM('unused','active','expired','banned') DEFAULT 'unused',
                duration_days INT NOT NULL,
                max_devices INT DEFAULT 1,
                device_count INT DEFAULT 0,
                created_by_type ENUM('admin','seller') NOT NULL,
                created_by_id INT NOT NULL,
                activated_at TIMESTAMP NULL,
                expires_at TIMESTAMP NULL,
                note VARCHAR(255) DEFAULT '',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_status (status),
                INDEX idx_created_by (created_by_type, created_by_id),
                INDEX idx_expires (expires_at)
            ) ENGINE=InnoDB",

            "CREATE TABLE IF NOT EXISTS devices (
                id INT AUTO_INCREMENT PRIMARY KEY,
                key_id INT NOT NULL,
                hwid VARCHAR(255) NOT NULL,
                device_info TEXT,
                registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (key_id) REFERENCES `keys`(id) ON DELETE CASCADE,
                UNIQUE KEY unique_device (key_id, hwid)
            ) ENGINE=InnoDB",

            "CREATE TABLE IF NOT EXISTS hwid_bans (
                id INT AUTO_INCREMENT PRIMARY KEY,
                hwid VARCHAR(255) UNIQUE NOT NULL,
                reason VARCHAR(255) DEFAULT '',
                banned_by INT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB",

            "CREATE TABLE IF NOT EXISTS token_transactions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                seller_id INT NOT NULL,
                type ENUM('refill','spend','deduct','payment') NOT NULL,
                tokens INT DEFAULT 0,
                amount DECIMAL(10,2) DEFAULT 0,
                note VARCHAR(255) DEFAULT '',
                admin_id INT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (seller_id) REFERENCES sellers(id) ON DELETE CASCADE,
                INDEX idx_seller (seller_id),
                INDEX idx_type (type),
                INDEX idx_created (created_at)
            ) ENGINE=InnoDB",

            "CREATE TABLE IF NOT EXISTS files (
                id INT AUTO_INCREMENT PRIMARY KEY,
                arch VARCHAR(20) NOT NULL,
                filename VARCHAR(255) NOT NULL,
                original_name VARCHAR(255) NOT NULL,
                file_size BIGINT DEFAULT 0,
                version VARCHAR(20) NOT NULL,
                is_active TINYINT(1) DEFAULT 0,
                uploaded_by INT,
                uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_arch_active (arch, is_active)
            ) ENGINE=InnoDB",

            "CREATE TABLE IF NOT EXISTS settings (
                setting_key VARCHAR(50) PRIMARY KEY,
                setting_value TEXT
            ) ENGINE=InnoDB",

            "CREATE TABLE IF NOT EXISTS activity_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_type VARCHAR(20) DEFAULT 'system',
                user_id INT,
                action VARCHAR(100) NOT NULL,
                details JSON,
                ip_address VARCHAR(45),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_action (action),
                INDEX idx_created (created_at)
            ) ENGINE=InnoDB",

            "CREATE TABLE IF NOT EXISTS rate_limits (
                id INT AUTO_INCREMENT PRIMARY KEY,
                ip_address VARCHAR(45) NOT NULL,
                endpoint VARCHAR(100) DEFAULT '',
                is_blocked TINYINT(1) DEFAULT 0,
                blocked_until TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_ip (ip_address),
                INDEX idx_blocked (is_blocked, blocked_until)
            ) ENGINE=InnoDB",

            "CREATE TABLE IF NOT EXISTS client_sessions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                session_token VARCHAR(128) NOT NULL,
                download_token VARCHAR(128) NOT NULL,
                key_id INT NOT NULL,
                hwid VARCHAR(255) NOT NULL,
                arch VARCHAR(20) NOT NULL,
                status ENUM('created','downloaded','expired') DEFAULT 'created',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                expires_at TIMESTAMP NOT NULL,
                UNIQUE(session_token),
                UNIQUE(download_token),
                INDEX idx_key (key_id),
                INDEX idx_status_expires (status, expires_at)
            ) ENGINE=InnoDB"
        ];
    }

    public static function getDefaultSettings() {
        return [
            ['maintenance_mode', 'false'],
            ['maintenance_message', 'Server is under maintenance'],
            ['default_max_devices', (string)DEFAULT_MAX_DEVICES],
            ['app_version', APP_VERSION],
            ['announcement', ''],
            ['duration_options', json_encode(DEFAULT_DURATIONS)],
            ['expected_apk_hash', ''],
            ['expected_lib_hash', '']
        ];
    }
}
