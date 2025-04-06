CREATE TABLE IF NOT EXISTS notification_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_type VARCHAR(50) NOT NULL COMMENT 'イベントタイプ（lesson_reminder, transfer_approved等）',
    template_id INT NOT NULL COMMENT 'メールテンプレートID',
    is_enabled TINYINT(1) NOT NULL DEFAULT 1 COMMENT '有効/無効',
    send_timing VARCHAR(50) NOT NULL COMMENT '送信タイミング（immediately, x_days_before等）',
    timing_value INT DEFAULT NULL COMMENT '送信タイミングの値（日数等）',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (template_id) REFERENCES email_templates(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;