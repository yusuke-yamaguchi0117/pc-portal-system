<?php

/**
 * 生徒の通常授業予定を生成してlesson_slotsテーブルに保存する
 *
 * @param PDO $pdo PDOインスタンス
 * @param int $student_id 生徒ID
 * @return array ['success' => bool, 'message' => string]
 */
function generateLessonSlots($pdo, $student_id)
{
    try {
        // 生徒情報を取得
        $stmt = $pdo->prepare("
            SELECT lesson_day, lesson_time, course_id
            FROM students
            WHERE id = ?
        ");
        $stmt->execute([$student_id]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$student) {
            return ['success' => false, 'message' => '生徒情報が見つかりません。'];
        }

        // 曜日の変換マップ
        $weekday_map = [
            '月曜' => 1,
            '火曜' => 2,
            '水曜' => 3,
            '木曜' => 4,
            '金曜' => 5,
            '土曜' => 6,
            '日曜' => 0
        ];

        $target_weekday = $weekday_map[$student['lesson_day']];

        // トランザクション開始
        $pdo->beginTransaction();

        // 既存の予定を削除（今日以降のみ）
        $stmt = $pdo->prepare("
            DELETE FROM lesson_slots
            WHERE student_id = ?
            AND date >= CURDATE()
        ");
        $stmt->execute([$student_id]);

        // 今日から90日分の日付を生成
        $start_date = new DateTime();
        $end_date = (new DateTime())->modify('+90 days');
        $interval = new DateInterval('P1D');
        $date_range = new DatePeriod($start_date, $interval, $end_date);

        // 授業予定を生成
        $insert_stmt = $pdo->prepare("
            INSERT INTO lesson_slots (
                student_id,
                date,
                start_time,
                end_time
            )
            SELECT
                ?,
                ?,
                ?,
                ADDTIME(?, '1:00:00')
            WHERE NOT EXISTS (
                SELECT 1
                FROM lesson_calendar
                WHERE lesson_date = ?
                AND lesson_type = '休み'
            )
        ");

        $slots_count = 0;
        foreach ($date_range as $date) {
            if ($date->format('w') == $target_weekday) {
                $date_str = $date->format('Y-m-d');
                $insert_stmt->execute([
                    $student_id,
                    $date_str,
                    $student['lesson_time'],
                    $student['lesson_time'],
                    $date_str
                ]);
                if ($insert_stmt->rowCount() > 0) {
                    $slots_count++;
                }
            }
        }

        $pdo->commit();
        return [
            'success' => true,
            'message' => "{$slots_count}件の授業予定を生成しました。"
        ];

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('授業予定生成エラー: ' . $e->getMessage());
        return [
            'success' => false,
            'message' => 'データベースエラーが発生しました。'
        ];
    }
}