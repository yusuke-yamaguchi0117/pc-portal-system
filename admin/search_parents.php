<?php
require_once '../config/database.php';
require_once '../auth.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';

    if (empty($keyword)) {
        // キーワードが空の場合は全件取得
        $stmt = $pdo->query("SELECT * FROM parents ORDER BY id DESC");
    } else {
        // キーワードによる検索
        $keyword = '%' . $keyword . '%';
        $stmt = $pdo->prepare("
            SELECT * FROM parents
            WHERE name LIKE ?
            OR furigana LIKE ?
            OR email LIKE ?
            ORDER BY id DESC
        ");
        $stmt->execute([$keyword, $keyword, $keyword]);
    }
    $parents = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 検索結果が0件の場合
    if (empty($parents)) {
        echo '<tr><td colspan="10" class="text-center">該当する保護者が見つかりません</td></tr>';
        exit;
    }

    // テーブルのHTML生成
    foreach ($parents as $parent): ?>
        <tr class="text-nowrap">
            <td><?php echo htmlspecialchars($parent['id']); ?></td>
            <td><?php echo htmlspecialchars($parent['name']); ?></td>
            <td><?php echo htmlspecialchars($parent['furigana'] ?? ''); ?></td>
            <td><?php echo htmlspecialchars($parent['email']); ?></td>
            <td><?php echo htmlspecialchars($parent['tel'] ?? ''); ?></td>
            <td><?php echo htmlspecialchars($parent['address'] ?? ''); ?></td>
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
                    <div class="text-truncate" title="<?php echo htmlspecialchars($parent['note']); ?>">
                        <?php echo htmlspecialchars($parent['note']); ?>
                    </div>
                <?php endif; ?>
            </td>
            <td class="text-nowrap">
                <a href="parent_form.php?id=<?php echo $parent['id']; ?>" class="btn btn-sm btn-info">編集</a>
                <button onclick="deleteParent(<?php echo $parent['id']; ?>)" class="btn btn-sm btn-danger">削除</button>
            </td>
        </tr>
    <?php endforeach;

} catch (PDOException $e) {
    http_response_code(500);
    echo '<tr><td colspan="7" class="text-center text-danger">データベースエラー: ' . htmlspecialchars($e->getMessage()) . '</td></tr>';
}