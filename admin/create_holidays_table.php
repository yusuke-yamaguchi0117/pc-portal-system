<?php
require_once '../config/database.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // SQLファイルの読み込み
    $sql = file_get_contents('../sql/create_holidays_table.sql');

    // テーブルの作成
    $pdo->exec($sql);

    echo "holidays テーブルが正常に作成されました。\n";

    // 2024年の主要な祝日を登録
    $holidays = [
        ['2024-01-01', '元日'],
        ['2024-01-08', '成人の日'],
        ['2024-02-11', '建国記念の日'],
        ['2024-02-12', '建国記念の日 振替休日'],
        ['2024-02-23', '天皇誕生日'],
        ['2024-03-20', '春分の日'],
        ['2024-04-29', '昭和の日'],
        ['2024-05-03', '憲法記念日'],
        ['2024-05-04', 'みどりの日'],
        ['2024-05-05', 'こどもの日'],
        ['2024-05-06', 'こどもの日 振替休日'],
        ['2024-07-15', '海の日'],
        ['2024-08-11', '山の日'],
        ['2024-08-12', '山の日 振替休日'],
        ['2024-09-16', '敬老の日'],
        ['2024-09-22', '秋分の日'],
        ['2024-09-23', '秋分の日 振替休日'],
        ['2024-10-14', 'スポーツの日'],
        ['2024-11-03', '文化の日'],
        ['2024-11-04', '文化の日 振替休日'],
        ['2024-11-23', '勤労感謝の日']
    ];

    // 祝日データの挿入
    $stmt = $pdo->prepare("INSERT INTO holidays (date, name) VALUES (?, ?)");
    foreach ($holidays as $holiday) {
        $stmt->execute($holiday);
    }

    echo "2024年の祝日データが正常に登録されました。\n";

} catch (PDOException $e) {
    die("データベースエラー: " . $e->getMessage() . "\n");
} catch (Exception $e) {
    die("エラー: " . $e->getMessage() . "\n");
}