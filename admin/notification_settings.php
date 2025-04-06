<?php
require_once '../config/database.php';
require_once '../auth.php';
require_once '../includes/header.php';
require_once '../includes/sidebar_admin.php';

$pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$message = '';
$error = '';

// イベントタイプの定義
$event_types = [
    'lesson_reminder' => '授業リマインダー',
    'transfer_approved' => '振替承認通知',
    'transfer_rejected' => '振替否認通知',
    'lesson_report' => '授業報告通知',
    'schedule_change' => '授業スケジュール変更通知'
];

// 送信タイミングの定義
$timing_options = [
    'immediately' => '即時送信',
    'x_days_before' => '指定日数前',
    'next_day' => '翌日',
    'same_day' => '当日'
];

// 設定の保存
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['delete'])) {
            // 設定の削除
            $stmt = $pdo->prepare("DELETE FROM notification_settings WHERE id = ?");
            $stmt->execute([$_POST['setting_id']]);
            $message = "通知設定を削除しました。";
        } else {
            // 設定の新規作成または更新
            if (empty($_POST['id'])) {
                $stmt = $pdo->prepare("INSERT INTO notification_settings (event_type, template_id, is_enabled, send_timing, timing_value) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([
                    $_POST['event_type'],
                    $_POST['template_id'],
                    isset($_POST['is_enabled']) ? 1 : 0,
                    $_POST['send_timing'],
                    $_POST['timing_value']
                ]);
                $message = "通知設定を作成しました。";
            } else {
                $stmt = $pdo->prepare("UPDATE notification_settings SET event_type = ?, template_id = ?, is_enabled = ?, send_timing = ?, timing_value = ? WHERE id = ?");
                $stmt->execute([
                    $_POST['event_type'],
                    $_POST['template_id'],
                    isset($_POST['is_enabled']) ? 1 : 0,
                    $_POST['send_timing'],
                    $_POST['timing_value'],
                    $_POST['id']
                ]);
                $message = "通知設定を更新しました。";
            }
        }
    } catch (PDOException $e) {
        $error = "エラーが発生しました: " . $e->getMessage();
    }
}

// 設定一覧の取得
$settings = $pdo->query("
    SELECT ns.*, et.name as template_name
    FROM notification_settings ns
    LEFT JOIN email_templates et ON ns.template_id = et.id
    ORDER BY ns.event_type
")->fetchAll(PDO::FETCH_ASSOC);

// メールテンプレート一覧の取得
$templates = $pdo->query("SELECT id, name FROM email_templates ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h2 class="mb-4">自動通知設定</h2>

            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <!-- 設定作成フォーム -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">通知設定の作成・編集</h5>
                </div>
                <div class="card-body">
                    <form method="post">
                        <input type="hidden" name="id" id="setting_id">
                        <div class="mb-3">
                            <label for="event_type" class="form-label">イベントタイプ</label>
                            <select class="form-control" id="event_type" name="event_type" required>
                                <?php foreach ($event_types as $key => $value): ?>
                                    <option value="<?php echo htmlspecialchars($key); ?>"><?php echo htmlspecialchars($value); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="template_id" class="form-label">メールテンプレート</label>
                            <select class="form-control" id="template_id" name="template_id" required>
                                <?php foreach ($templates as $template): ?>
                                    <option value="<?php echo $template['id']; ?>"><?php echo htmlspecialchars($template['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_enabled" name="is_enabled" checked>
                                <label class="form-check-label" for="is_enabled">
                                    通知を有効にする
                                </label>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="send_timing" class="form-label">送信タイミング</label>
                            <select class="form-control" id="send_timing" name="send_timing" required onchange="toggleTimingValue()">
                                <?php foreach ($timing_options as $key => $value): ?>
                                    <option value="<?php echo htmlspecialchars($key); ?>"><?php echo htmlspecialchars($value); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3" id="timing_value_container">
                            <label for="timing_value" class="form-label">日数</label>
                            <input type="number" class="form-control" id="timing_value" name="timing_value" min="1">
                            <div class="form-text">「指定日数前」を選択した場合のみ入力してください。</div>
                        </div>
                        <button type="submit" class="btn btn-primary">保存</button>
                        <button type="reset" class="btn btn-secondary" onclick="clearForm()">クリア</button>
                    </form>
                </div>
            </div>

            <!-- 設定一覧 -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">通知設定一覧</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th>イベントタイプ</th>
                                    <th>テンプレート</th>
                                    <th>状態</th>
                                    <th>送信タイミング</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($settings as $setting): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($event_types[$setting['event_type']] ?? $setting['event_type']); ?></td>
                                        <td><?php echo htmlspecialchars($setting['template_name']); ?></td>
                                        <td>
                                            <span class="badge <?php echo $setting['is_enabled'] ? 'bg-success' : 'bg-secondary'; ?>">
                                                <?php echo $setting['is_enabled'] ? '有効' : '無効'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php
                                            echo htmlspecialchars($timing_options[$setting['send_timing']] ?? $setting['send_timing']);
                                            if ($setting['send_timing'] === 'x_days_before' && $setting['timing_value']) {
                                                echo ' (' . $setting['timing_value'] . '日前)';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-primary" onclick='editSetting(<?php echo json_encode($setting); ?>)'>
                                                <i class="fas fa-edit"></i> 編集
                                            </button>
                                            <form method="post" class="d-inline" onsubmit="return confirm('この通知設定を削除してもよろしいですか？');">
                                                <input type="hidden" name="setting_id" value="<?php echo $setting['id']; ?>">
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

<script>
    function toggleTimingValue() {
        const sendTiming = document.getElementById('send_timing').value;
        const container = document.getElementById('timing_value_container');
        const input = document.getElementById('timing_value');

        if (sendTiming === 'x_days_before') {
            container.style.display = 'block';
            input.required = true;
        } else {
            container.style.display = 'none';
            input.required = false;
            input.value = '';
        }
    }

    function editSetting(setting) {
        document.getElementById('setting_id').value = setting.id;
        document.getElementById('event_type').value = setting.event_type;
        document.getElementById('template_id').value = setting.template_id;
        document.getElementById('is_enabled').checked = setting.is_enabled == 1;
        document.getElementById('send_timing').value = setting.send_timing;
        document.getElementById('timing_value').value = setting.timing_value;
        toggleTimingValue();
    }

    function clearForm() {
        document.getElementById('setting_id').value = '';
        document.getElementById('event_type').selectedIndex = 0;
        document.getElementById('template_id').selectedIndex = 0;
        document.getElementById('is_enabled').checked = true;
        document.getElementById('send_timing').selectedIndex = 0;
        document.getElementById('timing_value').value = '';
        toggleTimingValue();
    }

    // 初期表示時の設定
    toggleTimingValue();
</script>

<?php require_once '../includes/footer.php'; ?>