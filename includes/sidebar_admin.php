<div class="sidebar">
    <nav class="mt-2">
        <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
            <!-- メイン管理セクション -->
            <li class="nav-item has-treeview">
                <a href="#" class="nav-link">
                    <i class="nav-icon fas fa-tachometer-alt"></i>
                    <p>
                        メイン管理
                        <i class="right fas fa-angle-left"></i>
                    </p>
                </a>
                <ul class="nav nav-treeview">
                    <li class="nav-item">
                        <a href="/portal/admin/dashboard.php" class="nav-link">
                            <i class="far fa-circle nav-icon"></i>
                            <p>ダッシュボード</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="/portal/admin/students.php" class="nav-link">
                            <i class="far fa-circle nav-icon"></i>
                            <p>生徒管理</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="/portal/admin/parents.php" class="nav-link">
                            <i class="far fa-circle nav-icon"></i>
                            <p>保護者管理</p>
                        </a>
                    </li>
                </ul>
            </li>

            <!-- 授業関連セクション -->
            <li class="nav-item has-treeview">
                <a href="#" class="nav-link">
                    <i class="nav-icon fas fa-calendar-alt"></i>
                    <p>
                        授業関連
                        <i class="right fas fa-angle-left"></i>
                    </p>
                </a>
                <ul class="nav nav-treeview">
                    <li class="nav-item">
                        <a href="/portal/admin/calendar.php" class="nav-link">
                            <i class="far fa-circle nav-icon"></i>
                            <p>授業日程管理</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="/portal/admin/transfer_requests.php" class="nav-link">
                            <i class="far fa-circle nav-icon"></i>
                            <p>振替申請管理</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="/portal/admin/schedule_settings.php" class="nav-link">
                            <i class="far fa-circle nav-icon"></i>
                            <p>授業時間帯設定</p>
                        </a>
                    </li>
                </ul>
            </li>

            <!-- 授業の様子投稿セクション -->
            <li class="nav-item has-treeview">
                <a href="#" class="nav-link">
                    <i class="nav-icon fas fa-pencil-alt"></i>
                    <p>
                        授業の様子投稿
                        <i class="right fas fa-angle-left"></i>
                    </p>
                </a>
                <ul class="nav nav-treeview">
                    <li class="nav-item">
                        <a href="/portal/admin/lesson_posts_list.php" class="nav-link">
                            <i class="far fa-circle nav-icon"></i>
                            <p>投稿一覧</p>
                        </a>
                    </li>
                </ul>
            </li>

            <!-- メール設定セクション -->
            <li class="nav-item has-treeview">
                <a href="#" class="nav-link">
                    <i class="nav-icon fas fa-envelope"></i>
                    <p>
                        メール設定
                    </p>
                </a>
                <ul class="nav nav-treeview">
                    <li class="nav-item">
                        <a href="/portal/admin/email_settings.php" class="nav-link">
                            <i class="far fa-circle nav-icon"></i>
                            <p>メール送信設定</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="/portal/admin/email_templates.php" class="nav-link">
                            <i class="far fa-circle nav-icon"></i>
                            <p>メールテンプレート管理</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="/portal/admin/notification_settings.php" class="nav-link">
                            <i class="far fa-circle nav-icon"></i>
                            <p>自動通知設定</p>
                        </a>
                    </li>
                </ul>
            </li>
        </ul>
    </nav>
</div>

<style>
    /* AdminLTE スタイルの調整 */
    .sidebar {
        background-color: #343a40;
        color: #c2c7d0;
        padding: 0;
        min-height: 100vh;
    }

    .nav-sidebar {
        padding: 0;
    }

    .nav-sidebar .nav-item {
        width: 100%;
    }

    .nav-sidebar .nav-item>.nav-link {
        color: #c2c7d0;
        padding: 0.8rem 1rem;
        display: flex;
        align-items: center;
        transition: all 0.3s ease;
    }

    .nav-sidebar .nav-item>.nav-link>.nav-icon {
        font-size: 1.2rem;
        width: 2rem;
        text-align: center;
        margin-right: 0.8rem;
        color: #c2c7d0;
    }

    .nav-sidebar .nav-item>.nav-link>p {
        margin: 0;
        flex-grow: 1;
        font-size: 0.9rem;
    }

    .nav-sidebar .nav-item>.nav-link>.right {
        position: absolute;
        right: 1rem;
        margin-top: 3px;
        transition: transform 0.3s ease;
    }

    .nav-sidebar .nav-treeview {
        display: none;
        background-color: rgba(255, 255, 255, 0.05);
        padding-left: 0;
        margin: 0;
    }

    .nav-sidebar .menu-open>.nav-treeview {
        display: block;
    }

    .nav-sidebar .menu-open>.nav-link>.right {
        transform: rotate(-90deg);
    }

    .nav-sidebar .nav-treeview>.nav-item>.nav-link {
        color: #c2c7d0;
        padding: 0.8rem 1rem 0.8rem 3.8rem;
        position: relative;
        font-size: 0.9rem;
    }

    .nav-sidebar .nav-treeview>.nav-item>.nav-link>.nav-icon {
        font-size: 0.85rem;
        width: 1.6rem;
        margin-right: 0.6rem;
        color: #c2c7d0;
        position: absolute;
        left: 1.5rem;
    }

    /* ホバー時のスタイル */
    .nav-sidebar .nav-item>.nav-link:hover,
    .nav-sidebar .nav-treeview>.nav-item>.nav-link:hover {
        color: #fff;
        background-color: rgba(255, 255, 255, 0.1);
    }

    .nav-sidebar .nav-item>.nav-link:hover>.nav-icon,
    .nav-sidebar .nav-treeview>.nav-item>.nav-link:hover>.nav-icon {
        color: #fff;
    }

    /* アクティブなメニューのスタイル */
    .nav-sidebar .nav-item>.nav-link.active {
        color: #fff;
        background-color: #007bff;
    }

    .nav-sidebar .nav-item>.nav-link.active>.nav-icon {
        color: #fff;
    }

    /* サブメニューのアクティブスタイル */
    .nav-sidebar .nav-treeview>.nav-item>.nav-link.active {
        color: #fff;
        background-color: rgba(255, 255, 255, 0.1);
    }

    .nav-sidebar .nav-treeview>.nav-item>.nav-link.active>.nav-icon {
        color: #fff;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // 現在のページのパスを取得
        const currentPath = window.location.pathname;

        // メニュー項目を取得
        const menuItems = document.querySelectorAll('.nav-sidebar .nav-item > .nav-link');

        // 各メニュー項目に対して処理
        menuItems.forEach(item => {
            // サブメニューがある場合
            if (item.nextElementSibling && item.nextElementSibling.classList.contains('nav-treeview')) {
                // サブメニューのリンクを取得
                const subLinks = item.nextElementSibling.querySelectorAll('.nav-link');

                // サブメニューのリンクが現在のページと一致するかチェック
                const isActive = Array.from(subLinks).some(link => link.getAttribute('href') === currentPath);

                if (isActive) {
                    // 親メニューを開く
                    item.parentElement.classList.add('menu-open');
                    item.classList.add('active');

                    // 現在のページのリンクをアクティブにする
                    subLinks.forEach(link => {
                        if (link.getAttribute('href') === currentPath) {
                            link.classList.add('active');
                        }
                    });
                }

                // クリックイベントを追加
                item.addEventListener('click', function (e) {
                    e.preventDefault();
                    const parent = this.parentElement;
                    const isOpen = parent.classList.contains('menu-open');

                    // 他のメニューを閉じる
                    document.querySelectorAll('.nav-item.menu-open').forEach(openItem => {
                        if (openItem !== parent) {
                            openItem.classList.remove('menu-open');
                        }
                    });

                    // クリックしたメニューを開閉
                    parent.classList.toggle('menu-open');
                });
            }
        });
    });
</script>