CREATE TABLE IF NOT EXISTS email_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL COMMENT 'テンプレート名',
    subject VARCHAR(255) NOT NULL COMMENT 'メール件名',
    body TEXT NOT NULL COMMENT 'メール本文',
    variables TEXT COMMENT '使用可能な変数（JSON形式）',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- メールテンプレートの初期データを挿入
INSERT INTO email_templates (name, subject, body, variables) VALUES
('月次授業予定通知',
'{parent_name}様 - {month}月の授業予定のご案内',
'{parent_name}様

いつもお世話になっております。
プロクラ加古川南校です。

{month}月の授業予定日を以下の通りご案内いたします。

📅【{month}月の授業予定日】
{lesson_dates}

欠席・振替のご希望がございましたら、
事前にメールまたはお電話にてご連絡ください。

🔗【年間カレンダーはこちら】
{calendar_url}

どうぞよろしくお願いいたします。',
'{
    "parent_name": "保護者名",
    "month": "対象月",
    "lesson_dates": "授業予定日一覧（自動生成）",
    "calendar_url": "年間カレンダーのURL",
    "functions": {
        "generate_lesson_dates": "指定月の授業予定日を生成",
        "format_date": "日付のフォーマット"
    }
}');