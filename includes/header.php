<?php
ob_start();
?><!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>プログラ加古川南校</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <!-- Custom Layout CSS -->
    <link href="/portal/css/layout.css" rel="stylesheet">
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="/portal/admin/dashboard.php">プログラ加古川南校</a>
            <div class="d-flex">
                <a href="/portal/logout.php" class="btn btn-outline-light">ログアウト</a>
            </div>
        </div>
    </nav>

    <div class="wrapper">
        <!-- サイドバー -->
        <div class="sidebar">
            <div class="position-sticky">
                <?php
                $script_path = $_SERVER['SCRIPT_NAME'];
                if (strpos($script_path, '/admin/') !== false) {
                    include(__DIR__ . '/sidebar_admin.php');
                } else if (strpos($script_path, '/parent/') !== false) {
                    include(__DIR__ . '/sidebar_parent.php');
                }
                ?>
            </div>
        </div>

        <!-- メインコンテンツ -->
        <div class="content-wrapper">
            <main class="container-fluid py-3">
                <div class="container-fluid">