<?php
require_once 'config/database.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 基礎コース（ID: 1）の時間割を設定
    $schedules = [
        ['day_of_week' => 2, 'start_time' => '16:00:00', 'capacity' => 4], // 月曜
        ['day_of_week' => 2, 'start_time' => '17:15:00', 'capacity' => 4],
        ['day_of_week' => 2, 'start_time' => '18:30:00', 'capacity' => 4],
        ['day_of_week' => 4, 'start_time' => '16:00:00', 'capacity' => 4], // 水曜
        ['day_of_week' => 4, 'start_time' => '17:15:00', 'capacity' => 4],
        ['day_of_week' => 4, 'start_time' => '18:30:00', 'capacity' => 4],
        ['day_of_week' => 6, 'start_time' => '16:00:00', 'capacity' => 4], // 金曜
        ['day_of_week' => 6, 'start_time' => '17:15:00', 'capacity' => 4],
        ['day_of_week' => 6, 'start_time' => '18:30:00', 'capacity' => 4]
    ];

    // 既存の時間割を削除
    $stmt = $pdo->prepare("DELETE FROM schedules WHERE course_id = 1");
    $stmt->execute();

    // 新しい時間割を登録
    $stmt = $pdo->prepare("
        INSERT INTO schedules (course_id, day_of_week, start_time, capacity)
        VALUES (1, ?, ?, ?)
    ");

    foreach ($schedules as $schedule) {
        $stmt->execute([
            $schedule['day_of_week'],
            $schedule['start_time'],
            $schedule['capacity']
        ]);
    }

    echo "基礎コースの時間割を設定しました。\n";

    // 設定した時間割を確認
    $stmt = $pdo->query("
        SELECT
            day_of_week,
            start_time,
            capacity
        FROM schedules
        WHERE course_id = 1
        ORDER BY day_of_week, start_time
    ");

    echo "\n=== 基礎コースの時間割 ===\n";
    $day_names = [
        2 => '月曜日',
        4 => '水曜日',
        6 => '金曜日'
    ];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo $day_names[$row['day_of_week']] . " ";
        echo substr($row['start_time'], 0, 5) . " ";
        echo "定員: " . $row['capacity'] . "名\n";
    }

} catch (PDOException $e) {
    echo "データベースエラー: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "エラー: " . $e->getMessage() . "\n";
}
?>