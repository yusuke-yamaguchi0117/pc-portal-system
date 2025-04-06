<?php
require_once '../config/database.php';
require_once '../auth.php';
require_once '../includes/header.php';
require_once '../includes/sidebar_admin.php';
require_once '../includes/email_functions.php';
require_once '../vendor/autoload.php';

$pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$message = '';
$error = '';

// テストメール送信
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_test'])) {
    $result = send_test_email($_POST['template_id'], $_POST['test_email']);
    if ($result['success']) {
        $message = $result['message'];
    } else {
        $error = $result['message'];
    }
}

// テンプレートの保存
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['send_test'])) {
    try {
        if (isset($_POST['delete'])) {
            // テンプレートの削除
            $stmt = $pdo->prepare("DELETE FROM email_templates WHERE id = ?");
            $stmt->execute([$_POST['template_id']]);
            $message = "テンプレートを削除しました。";
        } else {
            // 入力値のバリデーション
            if (empty($_POST['name']) || empty($_POST['subject']) || empty($_POST['body'])) {
                throw new Exception('テンプレート名、件名、本文は必須項目です。');
            }

            // テンプレートの新規作成または更新
            $variables = json_encode([
                'student_name' => '生徒名',
                'parent_name' => '保護者名',
                'lesson_date' => '授業日',
                'lesson_time' => '授業時間',
                'teacher_name' => '講師名',
                'month' => '対象月',
                'lesson_dates' => '授業予定日一覧',
                'calendar_url' => 'カレンダーURL'
            ], JSON_UNESCAPED_UNICODE);

            if (empty($_POST['id'])) {
                $stmt = $pdo->prepare("INSERT INTO email_templates (name, subject, body, variables) VALUES (?, ?, ?, ?)");
                $stmt->execute([$_POST['name'], $_POST['subject'], $_POST['body'], $variables]);
                $message = "テンプレートを作成しました。";
            } else {
                $stmt = $pdo->prepare("UPDATE email_templates SET name = ?, subject = ?, body = ? WHERE id = ?");
                $stmt->execute([$_POST['name'], $_POST['subject'], $_POST['body'], $_POST['id']]);
                $message = "テンプレートを更新しました。";
            }
        }
    } catch (Exception $e) {
        $error = "エラーが発生しました: " . $e->getMessage();
    }
}

// テンプレート一覧の取得
$templates = $pdo->query("SELECT * FROM email_templates ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h2 class="mb-4">メールテンプレート管理</h2>

            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <!-- テンプレート作成フォーム -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">テンプレートの作成・編集</h5>
                </div>
                <div class="card-body">
                    <form method="post">
                        <input type="hidden" name="id" id="template_id">
                        <div class="mb-3">
                            <label for="name" class="form-label">テンプレート名</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="subject" class="form-label">メール件名</label>
                            <input type="text" class="form-control" id="subject" name="subject" required>
                        </div>
                        <div class="mb-3">
                            <label for="body" class="form-label">メール本文</label>
                            <textarea class="form-control" id="body" name="body" rows="10" required></textarea>
                            <div class="form-text">
                                利用可能な変数：
                                {student_name} - 生徒名,
                                {parent_name} - 保護者名,
                                {lesson_date} - 授業日,
                                {lesson_time} - 授業時間,
                                {teacher_name} - 講師名
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">保存</button>
                        <button type="reset" class="btn btn-secondary" onclick="clearForm()">クリア</button>
                    </form>
                </div>
            </div>

            <!-- テンプレート一覧 -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">テンプレート一覧</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th>テンプレート名</th>
                                    <th>メール件名</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($templates as $template): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($template['name']); ?></td>
                                        <td><?php echo htmlspecialchars($template['subject']); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-primary" onclick="editTemplate(<?php echo htmlspecialchars(json_encode($template)); ?>)">
                                                <i class="fas fa-edit"></i> 編集
                                            </button>
                                            <button class="btn btn-sm btn-info" onclick="showTestMailModal(<?php echo $template['id']; ?>, '<?php echo htmlspecialchars($template['name']); ?>')">
                                                <i class="fas fa-paper-plane"></i> テスト送信
                                            </button>
                                            <form method="post" class="d-inline" onsubmit="return confirm('このテンプレートを削除してもよろしいですか？');">
                                                <input type="hidden" name="template_id" value="<?php echo $template['id']; ?>">
                                                <button type="submit" name="delete" class="btn btn-sm btn-danger">
                                                    <i class="fas fa-trash"></i> 削除
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- テストメール送信モーダル -->
<div class="modal fade" id="testMailModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">テストメール送信</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <input type="hidden" name="template_id" id="test_template_id">
                    <p>テンプレート: <span id="test_template_name"></span></p>
                    <div class="mb-3">
                        <label for="test_email" class="form-label">送信先メールアドレス</label>
                        <input type="email" class="form-control" id="test_email" name="test_email" required>
                    </div>
                    <div class="alert alert-info">
                        <small>
                            <i class="fas fa-info-circle"></i> テストメールには以下のダミーデータが使用されます：<br>
                            - 保護者名：テスト保護者<br>
                            - 月：現在の月<br>
                            - 授業予定日：現在の月の仮の日程<br>
                            - カレンダーURL：現在の年のURL
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
                    <button type="submit" name="send_test" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> 送信
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function editTemplate(template) {
        document.getElementById('template_id').value = template.id;
        document.getElementById('name').value = template.name;
        document.getElementById('subject').value = template.subject;
        document.getElementById('body').value = template.body;
    }

    function clearForm() {
        document.getElementById('template_id').value = '';
        document.getElementById('name').value = '';
        document.getElementById('subject').value = '';
        document.getElementById('body').value = '';
    }

    function showTestMailModal(templateId, templateName) {
        document.getElementById('test_template_id').value = templateId;
        document.getElementById('test_template_name').textContent = templateName;
        new bootstrap.Modal(document.getElementById('testMailModal')).show();
    }
</script>

<?php require_once '../includes/footer.php'; ?>