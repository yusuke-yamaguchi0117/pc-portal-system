<?php
require_once '../config/database.php';
require_once '../auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $student_id = filter_input(INPUT_POST, 'student_id', FILTER_VALIDATE_INT);
        $parent_ids = isset($_POST['parent_ids']) ? $_POST['parent_ids'] : [];

        if (!$student_id) {
            throw new Exception('生徒IDが不正です。');
        }

        // トランザクション開始
        $pdo->beginTransaction();

        // 既存の紐づけを削除
        $stmt = $pdo->prepare("DELETE FROM parent_student WHERE student_id = ?");
        $stmt->execute([$student_id]);

        // 新しい紐づけを登録
        if (!empty($parent_ids)) {
            $stmt = $pdo->prepare("INSERT INTO parent_student (parent_id, student_id) VALUES (?, ?)");
            foreach ($parent_ids as $parent_id) {
                if (filter_var($parent_id, FILTER_VALIDATE_INT)) {
                    $stmt->execute([$parent_id, $student_id]);
                }
            }
        }

        // トランザクション完了
        $pdo->commit();
        $_SESSION['success_message'] = '保護者の紐づけを更新しました。';

    } catch (Exception $e) {
        // エラー時はロールバック
        $pdo->rollBack();
        $_SESSION['error_message'] = $e->getMessage();
    }
}

// 生徒一覧画面にリダイレクト
header('Location: students.php');
exit;