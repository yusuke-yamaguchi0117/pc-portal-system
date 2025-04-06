# 振替リクエスト機能

このドキュメントは、保護者ポータルサイトの振替リクエスト機能に関する説明と操作方法について記載しています。

## 機能概要

振替リクエスト機能は、保護者が子供のレッスンを別の日時に振り替える際に使用します。システムは以下の処理を行います：

1. 生徒の元のレッスン日時を表示
2. 振替候補となる日時のリストを表示（同じコースで 1 週間前後）
3. 保護者が希望する日時を選択して振替リクエストを送信
4. 管理者がリクエストを確認して承認・却下

## データベース構造

振替リクエスト機能で使用する主なテーブルは以下の通りです：

- `transfer_requests`: 振替リクエスト情報
  - 主なカラム: `student_id`, `lesson_slot_id`, `parent_id`, `lesson_date`, `transfer_date`, `transfer_start_time`, `transfer_end_time`, `status`
- `students`: 生徒情報
- `lesson_slots`: レッスン枠情報
- `schedules`: コーススケジュール情報

## 既知の問題と解決方法

### テーブル構造の不一致

テーブル構造が期待と異なる場合、以下の解決策があります：

1. **緊急対応（自動）**: API は現在のテーブル構造を検出して動的にクエリを調整します
2. **テーブル構造の標準化**: `rename_transfer_requests_columns.php` スクリプトを実行して、カラム名を統一します

#### カラム名の標準化

`rename_transfer_requests_columns.php` スクリプトは以下のカラム名を標準化します：

- `original_date` → `lesson_date`
- `requested_date` → `transfer_date`
- `requested_time` → `transfer_start_time`

また、必要に応じて `transfer_end_time` カラムを追加します。

### 実行方法

```
php rename_transfer_requests_columns.php
```

## API エンドポイント

### 振替候補を取得する

**エンドポイント**: `/parent/api/get_transfer_candidates.php`

**パラメータ**:

- `student_id`: 生徒 ID
- `lesson_date`: レッスン日（YYYY-MM-DD 形式）

**レスポンス例**:

```json
{
  "success": true,
  "data": [
    {
      "date": "2023-06-17(土) 14:00～15:00",
      "transfer_date": "2023-06-17",
      "transfer_start_time": "14:00:00",
      "transfer_end_time": "15:00:00",
      "remaining_slots": 2
    },
    ...
  ]
}
```

### 振替リクエストを送信する

**エンドポイント**: `/parent/api/submit_transfer_request.php`

**パラメータ**:

- `student_id`: 生徒 ID
- `lesson_slot_id`: レッスン枠 ID
- `lesson_date`: 元のレッスン日
- `transfer_date`: 希望振替日
- `transfer_start_time`: 希望開始時間
- `transfer_end_time`: 希望終了時間

**レスポンス例**:

```json
{
  "success": true,
  "message": "振替リクエストが送信されました",
  "request_id": 123
}
```

## トラブルシューティング

1. **403 Forbidden エラー**: ユーザー認証問題の可能性があります。セッションが有効か確認してください。
2. **データベースエラー**: ログを確認し、エラーメッセージに基づいて適切な対応を行ってください。
3. **転送候補が表示されない**: 生徒のコース設定と現在のスケジュールを確認してください。

## 開発者向け情報

コードの改善や修正点がある場合は、以下の手順で対応してください：

1. 本番環境に適用する前に、テスト環境でテストを行う
2. 変更内容を記録し、readme.md を更新する
3. データベースの変更が必要な場合は、マイグレーションスクリプトを用意する
