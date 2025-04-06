<?php
header('Content-Type: application/json');

try {
    $pdo = new PDO(
        "mysql:host=localhost;dbname=pc_kakogawa_sys;charset=utf8mb4",
        "root",
        "root",
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );

    // バリデーション
    $required_fields = ['student_id', 'lesson_slot_id', 'theme', 'comment'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            throw new Exception($field . 'は必須です');
        }
    }

    $photo_path = null;
    if (!empty($_FILES['photo']['name'])) {
        // アップロードディレクトリの作成
        $upload_dir = __DIR__ . '/../../uploads/posts/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        // ファイル名の生成
        $extension = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '.' . $extension;
        $upload_path = $upload_dir . $filename;

        // ファイルの移動
        if (!move_uploaded_file($_FILES['photo']['tmp_name'], $upload_path)) {
            throw new Exception('ファイルのアップロードに失敗しました');
        }

        $photo_path = '/portal/uploads/posts/' . $filename;
    }

    // データベースに保存
    $stmt = $pdo->prepare("
        INSERT INTO lesson_posts (
            student_id,
            lesson_slot_id,
            theme,
            comment,
            photo_path,
            created_at
        ) VALUES (
            :student_id,
            :lesson_slot_id,
            :theme,
            :comment,
            :photo_path,
            NOW()
        )
    ");

    $stmt->execute([
        'student_id' => $_POST['student_id'],
        'lesson_slot_id' => $_POST['lesson_slot_id'],
        'theme' => $_POST['theme'],
        'comment' => $_POST['comment'],
        'photo_path' => $photo_path
    ]);

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>