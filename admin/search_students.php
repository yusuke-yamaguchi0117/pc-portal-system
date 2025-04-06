<?php
require_once '../config/database.php';
require_once '../auth.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
    $mode = isset($_GET['mode']) ? $_GET['mode'] : 'all';
    $day = isset($_GET['day']) ? $_GET['day'] : '月曜';

    // SQLクエリの基本部分
    $sql = "SELECT * FROM students";
    $params = [];

    // 検索条件の構築
    $conditions = [];

    // 曜日別表示モードの場合
    if ($mode === 'by_day') {
        $conditions[] = "lesson_day = ?";
        $params[] = $day;
    }

    // キーワード検索条件
    if (!empty($keyword)) {
        $keyword = '%' . $keyword . '%';
        $conditions[] = "(name LIKE ? OR furigana LIKE ? OR school LIKE ?)";
        $params = array_merge($params, [$keyword, $keyword, $keyword]);
    }

    // 条件の結合
    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(" AND ", $conditions);
    }

    // 並び順
    if ($mode === 'by_day') {
        $sql .= " ORDER BY lesson_time, id DESC";
    } else {
        $sql .= " ORDER BY id DESC";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 検索結果が0件の場合
    if (empty($students)) {
        echo '<tr><td colspan="14" class="text-center">該当する生徒がいません</td></tr>';
        exit;
    }

    // テーブルのHTML生成
    foreach ($students as $student): ?>
        <tr class="text-nowrap">
            <td><?php echo htmlspecialchars($student['id']); ?></td>
            <td><?php echo htmlspecialchars($student['name']); ?></td>
            <td><?php echo htmlspecialchars($student['furigana']); ?></td>
            <td><?php echo htmlspecialchars($student['school']); ?></td>
            <td><?php echo htmlspecialchars($student['grade']); ?></td>
            <td><?php echo htmlspecialchars($student['gender']); ?></td>
            <td><?php echo htmlspecialchars($student['course']); ?></td>
            <td><?php echo htmlspecialchars($student['lesson_day'] . ' ' . $student['lesson_time']); ?></td>
            <td><?php echo htmlspecialchars($student['join_date']); ?></td>
            <td><?php echo htmlspecialchars($student['me_id']); ?></td>
            <td><?php echo htmlspecialchars($student['me_password']); ?></td>
            <td><?php echo htmlspecialchars($student['device_number']); ?></td>
            <td><?php
            $status = htmlspecialchars($student['status']);
            $statusClass = '';
            switch ($status) {
                case '退会済':
                    $statusClass = 'text-danger';
                    break;
                case '休会中':
                    $statusClass = 'text-warning';
                    break;
                default:
                    $statusClass = 'text-success';
            }
            echo "<span class=\"{$statusClass}\">{$status}</span>";
            ?></td>
            <td class="text-nowrap">
                <a href="student_form.php?id=<?php echo $student['id']; ?>" class="btn btn-sm btn-info">編集</a>
                <button onclick="deleteStudent(<?php echo $student['id']; ?>)" class="btn btn-sm btn-danger">削除</button>
            </td>
        </tr>
    <?php endforeach;

} catch (PDOException $e) {
    http_response_code(500);
    echo '<tr><td colspan="14" class="text-center text-danger">データベースエラー: ' . htmlspecialchars($e->getMessage()) . '</td></tr>';
}