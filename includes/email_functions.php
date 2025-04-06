<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// 復号化関数を追加
function decrypt($data)
{
    $method = "AES-256-CBC";
    $key = ENCRYPTION_KEY;
    $data = base64_decode($data);
    $ivlen = openssl_cipher_iv_length($method);
    $iv = substr($data, 0, $ivlen);
    $encrypted = substr($data, $ivlen);
    return openssl_decrypt($encrypted, $method, $key, 0, $iv);
}

/**
 * 指定月の授業予定日を生成する
 *
 * @param int $student_id 生徒ID
 * @param int $year 年
 * @param int $month 月
 * @return string フォーマット済みの授業予定日一覧
 */
function generate_lesson_dates($student_id, $year, $month)
{
    global $pdo;

    // 生徒の通常の授業曜日を取得
    $stmt = $pdo->prepare("SELECT lesson_day FROM students WHERE id = ?");
    $stmt->execute([$student_id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        return '';
    }

    // 指定月の全日付を取得
    $dates = [];
    $start = new DateTime("$year-$month-01");
    $end = new DateTime("$year-$month-01");
    $end->modify('last day of this month');

    // 曜日の変換マップ
    $dayMap = [
        '月曜' => 1,
        '火曜' => 2,
        '水曜' => 3,
        '木曜' => 4,
        '金曜' => 5,
        '土曜' => 6,
        '日曜' => 0
    ];

    $targetDayNum = $dayMap[$student['lesson_day']];

    // 該当する曜日の日付を収集
    $current = clone $start;
    while ($current <= $end) {
        if ($current->format('w') == $targetDayNum) {
            // 休日チェック
            $date = $current->format('Y-m-d');
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM lesson_calendar WHERE lesson_date = ? AND (lesson_type = '休み' OR lesson_type = '臨時休校')");
            $stmt->execute([$date]);
            $isHoliday = $stmt->fetchColumn() > 0;

            if (!$isHoliday) {
                $dates[] = $current->format('n月j日') . "（" . format_day_of_week($current->format('w')) . "）";
            }
        }
        $current->modify('+1 day');
    }

    // 箇条書きにフォーマット
    return "・" . implode("\n・", $dates);
}

/**
 * 数値の曜日を日本語表記に変換
 *
 * @param int $dow 曜日（0-6）
 * @return string 日本語の曜日
 */
function format_day_of_week($dow)
{
    $days = ['日', '月', '火', '水', '木', '金', '土'];
    return $days[$dow];
}

/**
 * メールテンプレートの変数を置換
 *
 * @param string $template テンプレート文字列
 * @param array $data 置換データ
 * @return string 置換後の文字列
 */
function replace_template_variables($template, $data)
{
    // 基本的な変数の置換
    $replaced = $template;
    foreach ($data as $key => $value) {
        if (!is_array($value)) {
            $replaced = str_replace("{{$key}}", $value, $replaced);
        }
    }

    return $replaced;
}

/**
 * 年間カレンダーのURLを取得
 *
 * @param int $year 年
 * @return string カレンダーのURL
 */
function get_calendar_url($year)
{
    return "https://fujicomp.co.jp/wp-content/uploads/prokura_Calendar{$year}.pdf";
}

/**
 * テストメールを送信する
 *
 * @param int $template_id テンプレートID
 * @param string $to_email 送信先メールアドレス
 * @return array 成功時は ['success' => true]、失敗時は ['success' => false, 'error' => エラーメッセージ]
 */
function send_test_email($template_id, $to_email)
{
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8",
            DB_USER,
            DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );

        // メール設定を取得
        $stmt = $pdo->query("SELECT * FROM email_settings LIMIT 1");
        $settings = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$settings) {
            throw new Exception('メール設定が見つかりません。');
        }

        // テンプレートを取得
        $stmt = $pdo->prepare("SELECT * FROM email_templates WHERE id = ?");
        $stmt->execute([$template_id]);
        $template = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$template) {
            throw new Exception('テンプレートが見つかりません。');
        }

        // テストデータを作成
        $test_data = [
            'parent_name' => 'テスト 保護者',
            'month' => date('n'),
            'lesson_dates' => "・" . date('n') . "月1日（" . format_day_of_week(date('w', strtotime(date('Y-m-01')))) . "）\n" .
                "・" . date('n') . "月8日（" . format_day_of_week(date('w', strtotime(date('Y-m-08')))) . "）\n" .
                "・" . date('n') . "月15日（" . format_day_of_week(date('w', strtotime(date('Y-m-15')))) . "）\n" .
                "・" . date('n') . "月22日（" . format_day_of_week(date('w', strtotime(date('Y-m-22')))) . "）",
            'calendar_url' => get_calendar_url(date('Y'))
        ];

        // テンプレート変数を置換
        $subject = replace_template_variables($template['subject'], $test_data);
        $body = replace_template_variables($template['body'], $test_data);

        // メールを送信
        $mail = new PHPMailer(true);
        $mail->CharSet = 'UTF-8';
        $mail->isSMTP();
        $mail->Host = $settings['smtp_host'];
        $mail->SMTPAuth = true;
        $mail->Username = $settings['smtp_user'];
        $mail->Password = decrypt($settings['smtp_pass']); // パスワードを復号化
        // SSL/TLSの設定
        $mail->SMTPSecure = 'ssl';
        $mail->Port = 465;

        // デバッグ出力を最大限に設定
        $mail->SMTPDebug = SMTP::DEBUG_LOWLEVEL; // 最も詳細なデバッグ情報
        $mail->Debugoutput = function ($str, $level) {
            error_log("PHPMailer Debug [$level]: $str");
        };

        // 認証情報のログ出力（パスワードは一部マスク）
        error_log("SMTP Settings - Host: " . $settings['smtp_host']);
        error_log("SMTP Settings - Port: 465");
        error_log("SMTP Settings - Username: " . $settings['smtp_user']);
        error_log("SMTP Settings - Password length: " . strlen($settings['smtp_pass']));

        $mail->setFrom($settings['from_email'], $settings['from_name']);
        $mail->addAddress($to_email);
        if (!empty($settings['reply_to'])) {
            $mail->addReplyTo($settings['reply_to']);
        }
        $mail->Subject = $subject;
        $mail->Body = $body;

        $mail->send();
        return ['success' => true, 'message' => 'テストメールを送信しました。'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'メール送信に失敗しました: ' . $e->getMessage()];
    }
}