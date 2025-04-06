<?php
require_once '../config/database.php';
require_once '../auth.php';
require_once '../includes/header.php';
require_once '../includes/sidebar_admin.php';

// データベース接続
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 表示モードの取得（デフォルトは全件表示）
    $display_mode = isset($_GET['mode']) ? $_GET['mode'] : 'all';
    $current_day = isset($_GET['day']) ? $_GET['day'] : '月曜';

    // 曜日の配列
    $days = ['月曜', '火曜', '水曜', '木曜', '金曜', '土曜', '日曜'];

    // 生徒一覧を取得
    if ($display_mode === 'by_day') {
        $stmt = $pdo->prepare("
            SELECT s.*,
                   c.name as course_name,
                   GROUP_CONCAT(
                       CONCAT(
                           '<a href=\"parent_form.php?id=', p.id, '\">',
                           p.name,
                           '</a>'
                       )
                       ORDER BY p.furigana
                       SEPARATOR '、'
                   ) as linked_parents
            FROM students s
            LEFT JOIN courses c ON s.course = c.id
            LEFT JOIN parent_student ps ON s.id = ps.student_id
            LEFT JOIN parents p ON ps.parent_id = p.id
            WHERE s.lesson_day = ?
            GROUP BY s.id
            ORDER BY s.lesson_time, s.id DESC
        ");
        $stmt->execute([$current_day]);
    } else {
        $stmt = $pdo->query("
            SELECT s.*,
                   c.name as course_name,
                   GROUP_CONCAT(
                       CONCAT(
                           '<a href=\"parent_form.php?id=', p.id, '\">',
                           p.name,
                           '</a>'
                       )
                       ORDER BY p.furigana
                       SEPARATOR '、'
                   ) as linked_parents
            FROM students s
            LEFT JOIN courses c ON s.course = c.id
            LEFT JOIN parent_student ps ON s.id = ps.student_id
            LEFT JOIN parents p ON ps.parent_id = p.id
            GROUP BY s.id
            ORDER BY s.furigana
        ");
    }
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("データベース接続エラー: " . $e->getMessage());
}
?>

<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-12">
            <h2>生徒一覧</h2>
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="btn-group" role="group">
                    <a href="?mode=all" class="btn btn-outline-primary <?php echo $display_mode === 'all' ? 'active' : ''; ?>">全件表示</a>
                    <a href="?mode=by_day" class="btn btn-outline-primary <?php echo $display_mode === 'by_day' ? 'active' : ''; ?>">曜日別で表示</a>
                </div>
                <a href="student_form.php" class="btn btn-primary">新規登録</a>
            </div>

            <?php if ($display_mode === 'by_day'): ?>
                <div class="nav nav-tabs mb-3">
                    <?php
                    foreach ($days as $d): ?>
                        <a href="?mode=by_day&day=<?php echo $d; ?>" class="nav-link <?php echo $current_day === $d ? 'active' : ''; ?>">
                            <?php echo $d; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="input-group mb-3">
                <input type="text" class="form-control" placeholder="名前・ふりがな・学校名で検索" id="searchInput">
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead>
                                <tr class="text-nowrap">
                                    <th>ID</th>
                                    <th>名前</th>
                                    <th>ふりがな</th>
                                    <th>学校</th>
                                    <th>学年</th>
                                    <th>性別</th>
                                    <th>コース</th>
                                    <th>授業時間</th>
                                    <th>入会月</th>
                                    <th>ME情報（ID）</th>
                                    <th>ME情報（PASS）</th>
                                    <th>使用端末</th>
                                    <th>保護者</th>
                                    <th>ステータス</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody id="studentsList">
                                <?php if (empty($students)): ?>
                                    <tr>
                                        <td colspan="14" class="text-center">該当する生徒がいません</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($students as $student): ?>
                                        <tr class="text-nowrap">
                                            <td><?php echo htmlspecialchars($student['id'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($student['name'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($student['furigana'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($student['school'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($student['grade'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($student['gender'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($student['course_name'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars(($student['lesson_day'] ?? '') . ' ' . ($student['lesson_time'] ?? '')); ?></td>
                                            <td><?php echo htmlspecialchars($student['join_date'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($student['me_id'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($student['me_password'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($student['device_number'] ?? ''); ?></td>
                                            <td><?php echo $student['linked_parents'] ?: '-'; ?></td>
                                            <td><?php
                                            $status = htmlspecialchars($student['status'] ?? '');
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
                                                <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#linkParentsModal<?php echo $student['id']; ?>">
                                                    保護者を設定
                                                </button>
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
</>

<script>
    // 検索機能の実装
    let searchTimer;
    const searchInput = document.getElementById('searchInput');
    const studentsList = document.getElementById('studentsList');
    const currentMode = '<?php echo $display_mode; ?>';
    const currentDay = '<?php echo $current_day; ?>';

    searchInput.addEventListener('input', function () {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(searchStudents, 300);
    });

    function searchStudents() {
        const keyword = searchInput.value.trim();
        const searchParams = new URLSearchParams({
            keyword: keyword,
            mode: currentMode,
            day: currentDay
        });

        // ローディング表示
        studentsList.innerHTML = '<tr><td colspan="14" class="text-center">検索中...</td></tr>';

        fetch(`search_students.php?${searchParams.toString()}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('検索中にエラーが発生しました');
                }
                return response.text();
            })
            .then(html => {
                studentsList.innerHTML = html;
            })
            .catch(error => {
                studentsList.innerHTML = `<tr><td colspan="14" class="text-center text-danger">${error.message}</td></tr>`;
            });
    }

    function deleteStudent(id) {
        if (confirm('本当にこの生徒を削除しますか？')) {
            window.location.href = 'delete_student.php?id=' + id;
        }
    }
</script>

<!-- 保護者設定モーダル -->
<?php foreach ($students as $student): ?>
    <div class="modal fade" id="linkParentsModal<?php echo $student['id']; ?>" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">保護者の設定 - <?php echo htmlspecialchars($student['name'] ?? ''); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="post" action="link_parents.php">
                        <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">

                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th style="width: 50px;">選択</th>
                                        <th>名前</th>
                                        <th>ふりがな</th>
                                        <th>メールアドレス</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // 現在紐づいている保護者を取得
                                    $stmt = $pdo->prepare("SELECT parent_id FROM parent_student WHERE student_id = ?");
                                    $stmt->execute([$student['id']]);
                                    $linked_parents = array_column($stmt->fetchAll(), 'parent_id');

                                    // 保護者一覧を取得
                                    $parents = $pdo->query("
                                    SELECT id, name, furigana, email
                                    FROM parents
                                    ORDER BY furigana
                                ")->fetchAll();

                                    foreach ($parents as $parent):
                                        ?>
                                        <tr>
                                            <td class="text-center">
                                                <input type="checkbox" name="parent_ids[]" value="<?php echo $parent['id']; ?>" class="form-check-input" <?php echo in_array($parent['id'], $linked_parents) ? 'checked' : ''; ?>>
                                            </td>
                                            <td><?php echo htmlspecialchars($parent['name'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($parent['furigana'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($parent['email'] ?? ''); ?></td>
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

<?php require_once '../includes/footer.php'; ?>