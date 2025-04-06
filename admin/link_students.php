<?php
require_once '../config/database.php';
require_once '../auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $parent_id = filter_input(INPUT_POST, 'parent_id', FILTER_VALIDATE_INT);
        $student_ids = isset($_POST['student_ids']) ? $_POST['student_ids'] : [];

        if (!$parent_id) {
            throw new Exception('保護者IDが不正です。');
        }

        // トランザクション開始
        $pdo->beginTransaction();

        // 既存の紐づけを削除
        $stmt = $pdo->prepare("DELETE FROM parent_student WHERE parent_id = ?");
        $stmt->execute([$parent_id]);

        // 新しい紐づけを登録
        if (!empty($student_ids)) {
            $stmt = $pdo->prepare("INSERT INTO parent_student (parent_id, student_id) VALUES (?, ?)");
            foreach ($student_ids as $student_id) {
                if (filter_var($student_id, FILTER_VALIDATE_INT)) {
                    $stmt->execute([$parent_id, $student_id]);
                }
            }
        }

        // トランザクション完了
        $pdo->commit();
        $_SESSION['success_message'] = '生徒の紐づけを更新しました。';

    } catch (Exception $e) {
        // エラー時はロールバック
        $pdo->rollBack();
        $_SESSION['error_message'] = $e->getMessage();
    }
}

// 保護者一覧画面にリダイレクト
header('Location: parents.php');
exit;