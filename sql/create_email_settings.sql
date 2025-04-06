CREATE TABLE IF NOT EXISTS email_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    smtp_host VARCHAR(255) NOT NULL,
    smtp_port INT NOT NULL,
    smtp_username VARCHAR(255) NOT NULL,
    smtp_password VARCHAR(255) NOT NULL,
    smtp_encryption ENUM('tls', 'ssl') NOT NULL DEFAULT 'tls',
    from_email VARCHAR(255) NOT NULL,
    from_name VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- デフォルト設定の挿入
INSERT INTO email_settings (
    smtp_host,
    smtp_port,
    smtp_username,
    smtp_password,
    smtp_encryption,
    from_email,
    from_name
) VALUES (
    'smtp.gmail.com',
    587,
    'your-email@gmail.com',
    'your-app-password',
    'tls',
    'your-email@gmail.com',
    'プロクラ加古川南校'
);