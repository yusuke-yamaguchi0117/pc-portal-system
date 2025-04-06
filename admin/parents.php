<?php
require_once '../config/database.php';
require_once '../auth.php';
require_once '../includes/header.php';
require_once '../includes/sidebar_admin.php';

// データベース接続
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 保護者一覧を取得
    $stmt = $pdo->query("
        SELECT p.*,
               GROUP_CONCAT(
                   CONCAT(
                       '<a href=\"student_form.php?id=', s.id, '\">',
                       s.name,
                       '</a>'
                   )
                   ORDER BY s.furigana
                   SEPARATOR '、'
               ) as linked_students
        FROM parents p
        LEFT JOIN parent_student ps ON p.id = ps.parent_id
        LEFT JOIN students s ON ps.student_id = s.id
        GROUP BY p.id
        ORDER BY p.furigana
    ");
    $parents = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("データベース接続エラー: " . $e->getMessage());
}
?>

<div class="main-container">
    <?php include '../includes/sidebar_admin.php'; ?>
    <div class="container-fluid py-3">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">保護者一覧</h5>
                            <a href="parent_form.php" class="btn btn-primary">新規登録</a>
                        </div>
                        <!-- 検索ボックス -->
                        <div class="mt-3">
                            <input type="text" id="searchKeyword" class="form-control" placeholder="名前・ふりがな・メールアドレスで検索" autocomplete="off">
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead>
                                    <tr class="text-nowrap">
                                        <th>ID</th>
                                        <th>名前</th>
                                        <th>ふりがな</th>
                                        <th>メールアドレス</th>
                                        <th>電話番号</th>
                                        <th>住所</th>
                                        <th>紐づけられた生徒</th>
                                        <th>登録日</th>
                                        <th>最終ログイン</th>
                                        <th>備考</th>
                                        <th>操作</th>
                                    </tr>
                                </thead>
                                <tbody id="parentsList">
                                    <?php if (empty($parents)): ?>
                                        <tr>
                                            <td colspan="10" class="text-center">保護者情報が登録されていません</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($parents as $parent): ?>
                                            <tr class="text-nowrap">
                                                <td><?php echo htmlspecialchars($parent['id']); ?></td>
                                                <td><?php echo htmlspecialchars($parent['name'] ?? ''); ?></td>
                                                <td><?php echo htmlspecialchars($parent['furigana'] ?? ''); ?></td>
                                                <td><?php echo htmlspecialchars($parent['email'] ?? ''); ?></td>
                                                <td><?php echo htmlspecialchars($parent['tel'] ?? ''); ?></td>
                                                <td><?php echo htmlspecialchars($parent['address'] ?? ''); ?></td>
                                                <td><?php echo $parent['linked_students'] ?: '-'; ?></td>
                                                <td><?php
                                                echo $parent['created_at']
                                                    ? date('Y-m-d', strtotime($parent['created_at']))
                                                    : '';
                                                ?></td>
                                                <td><?php
                                                echo $parent['last_login']
                                                    ? date('Y-m-d H:i', strtotime($parent['last_login']))
                                                    : '未ログイン';
                                                ?></td>
                                                <td class="text-wrap" style="max-width: 200px;">
                                                    <?php if (!empty($parent['note'])): ?>
                                                        <div class="text-truncate" title="<?php echo htmlspecialchars($parent['note'] ?? ''); ?>">
                                                            <?php echo htmlspecialchars($parent['note'] ?? ''); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-nowrap">
                                                    <a href="parent_form.php?id=<?php echo $parent['id']; ?>" class="btn btn-sm btn-info">編集</a>
                                                    <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#linkStudentsModal<?php echo $parent['id']; ?>">
                                                        生徒を紐づけ
                                                    </button>
                                                    <button onclick="deleteParent(<?php echo $parent['id']; ?>)" class="btn btn-sm btn-danger">削除</button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

<!-- 生徒紐づけモーダル -->
<?php foreach ($parents as $parent): ?>
    <div class="modal fade" id="linkStudentsModal<?php echo $parent['id']; ?>" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">生徒の紐づけ - <?php echo htmlspecialchars($parent['name']); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="post" action="link_students.php">
                        <input type="hidden" name="parent_id" value="<?php echo $parent['id']; ?>">

                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th style="width: 50px;">選択</th>
                                        <th>名前</th>
                                        <th>ふりがな</th>
                                        <th>学年</th>
                                        <th>学校</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // 現在紐づいている生徒を取得
                                    $stmt = $pdo->prepare("SELECT student_id FROM parent_student WHERE parent_id = ?");
                                    $stmt->execute([$parent['id']]);
                                    $linked_students = array_column($stmt->fetchAll(), 'student_id');

                                    // 生徒一覧を取得
                                    $students = $pdo->query("
                                    SELECT id, name, furigana, grade, school
                                    FROM students
                                    WHERE status != '退会済'
                                    ORDER BY furigana
                                ")->fetchAll();

                                    foreach ($students as $student):
                                        ?>
                                        <tr>
                                            <td class="text-center">
                                                <input type="checkbox" name="student_ids[]" value="<?php echo $student['id']; ?>" class="form-check-input" <?php echo in_array($student['id'], $linked_students) ? 'checked' : ''; ?>>
                                            </td>
                                            <td><?php echo htmlspecialchars($student['name']); ?></td>
                                            <td><?php echo htmlspecialchars($student['furigana']); ?></td>
                                            <td><?php echo htmlspecialchars($student['grade']); ?></td>
                                            <td><?php echo htmlspecialchars($student['school']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="text-center mt-3">
                            <button type="submit" class="btn btn-primary">保存</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<script>
    // 検索機能の実装
    let searchTimer;
    const searchInput = document.getElementById('searchKeyword');
    const parentsList = document.getElementById('parentsList');

    searchInput.addEventListener('input', function () {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(searchParents, 300);
    });

    function searchParents() {
        const keyword = searchInput.value.trim();

        // ローディング表示
        parentsList.innerHTML = '<tr><td colspan="10" class="text-center">検索中...</td></tr>';

        fetch(`search_parents.php?keyword=${encodeURIComponent(keyword)}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('検索中にエラーが発生しました');
                }
                return response.text();
            })
            .then(html => {
                parentsList.innerHTML = html;
            })
            .catch(error => {
                parentsList.innerHTML = `<tr><td colspan="10" class="text-center text-danger">${error.message}</td></tr>`;
            });
    }

    function deleteParent(id) {
        if (confirm('本当にこの保護者情報を削除しますか？')) {
            window.location.href = 'delete_parent.php?id=' + id;
        }
    }
</script>

<?php require_once '../includes/footer.php'; ?>