<?php
require_once '../config/database.php';
require_once '../auth.php';
require_once '../includes/lesson_slots_generator.php';

// POSTリクエストのみ受け付け
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: students.php');
    exit;
}

try {
    $pdo->beginTransaction();

    $student_id = isset($_POST['id']) ? $_POST['id'] : null;
    $is_new = empty($student_id);

    if ($is_new) {
        // 新規登録
        $stmt = $pdo->prepare("
            INSERT INTO students (
                name,
                course_id,
                lesson_day,
                lesson_time,
                created_at,
                updated_at
            ) VALUES (?, ?, ?, ?, NOW(), NOW())
        ");
        $stmt->execute([
            $_POST['name'],
            $_POST['course_id'],
            $_POST['lesson_day'],
            $_POST['lesson_time']
        ]);
        $student_id = $pdo->lastInsertId();
        $message = '生徒を登録しました。';
    } else {
        // 更新
        $stmt = $pdo->prepare("
            UPDATE students
            SET name = ?,
                course_id = ?,
                lesson_day = ?,
                lesson_time = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([
            $_POST['name'],
            $_POST['course_id'],
            $_POST['lesson_day'],
            $_POST['lesson_time'],
            $student_id
        ]);
        $message = '生徒情報を更新しました。';
    }

    // 授業予定を生成
    $result = generateLessonSlots($pdo, $student_id);
    if (!$result['success']) {
        throw new Exception($result['message']);
    }
    $message .= ' ' . $result['message'];

    $pdo->commit();
    $_SESSION['success'] = $message;

} catch (Exception $e) {
    $pdo->rollBack();
    error_log('生徒登録エラー: ' . $e->getMessage());
    $_SESSION['error'] = 'エラーが発生しました。: ' . $e->getMessage();
}

header('Location: students.php');
exit;