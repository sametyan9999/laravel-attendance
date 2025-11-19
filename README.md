# COACHTECH 勤怠管理アプリ

一般ユーザーの勤怠打刻・勤怠履歴の確認、および管理者による勤怠情報の確認・修正申請の承認を行うことができる Web アプリケーションです。
ユーザーはログイン後に出勤・退勤・休憩の打刻や、日次／月次の勤怠情報の確認・修正申請ができます。
管理者ユーザーは、スタッフ一覧や日次・月次勤怠一覧、修正申請一覧から各レコードを確認し、勤怠の修正や承認／却下を行うことができます。

---

## アプリケーション概要

- サービス名：**COACHTECH 勤怠管理アプリ**
- サービス概要：
  - 一般ユーザー向け勤怠打刻・勤怠閲覧・修正申請機能
  - 管理者向け勤怠一覧・スタッフ一覧・修正申請承認／却下機能
- 制作目的：Laravel および Docker 環境での Web アプリ開発実践（勤怠管理ドメイン）
- 対象ユーザー：
  - 一般社員（出勤・退勤・休憩の記録、勤怠確認・修正申請を行うユーザー）
  - 管理者ユーザー（スタッフの勤怠状況を把握し、修正申請の承認／却下を行うユーザー）
- 使用環境：PC（Chrome / Firefox / Safari の最新バージョンを想定）

---

## 主な機能

### 一般ユーザー向け

- ユーザー登録 / ログイン / ログアウト（Laravel Fortify 使用）
- メールアドレス認証機能（メール認証誘導画面・認証メール再送機能）
- 勤怠打刻機能
  - 出勤 / 退勤
  - 休憩開始 / 休憩終了（複数回）
- 勤怠一覧機能
  - 自分の月次勤怠一覧
  - 日付切り替え（前月 / 翌月）
- 勤怠詳細・修正申請機能
  - 日別の勤怠詳細表示
  - 出勤時刻・退勤時刻・休憩の編集
  - 修正申請登録（管理者へ申請）
- 申請一覧機能
  - 自分が行った修正申請の「承認待ち／承認済み」一覧表示

### 管理者向け

- 管理者ログイン機能（Laravel Fortify 認証 + `role = admin` 判定）
- スタッフ一覧機能
  - 一般ユーザーの氏名・メールアドレス一覧表示
- 日次勤怠一覧機能
  - 指定日の全ユーザー勤怠一覧
  - 詳細への遷移
- 月次勤怠一覧（スタッフ別）
  - スタッフごとの月次勤怠一覧
  - CSV 出力機能（指定ユーザーの月次勤怠を CSV ダウンロード）
- 勤怠詳細編集機能（管理者）
  - 出勤・退勤・休憩時間の修正
  - 備考・ステータス更新（off_duty / working / break / completed）
  - 時刻の前後関係チェック（出勤 < 退勤、休憩が勤務時間内か など）
- 修正申請一覧・詳細・承認／却下機能
  - 承認待ち申請一覧
  - 申請詳細表示
  - 承認処理（勤怠レコード反映）
  - 却下処理（却下理由の登録）

---

## ログイン情報

### 一般ユーザー

- 会員登録画面から任意のメールアドレス・パスワードで新規登録してください。
- 登録後、送信される認証メール（MailHog 経由）からメール認証を行うと、勤怠機能が利用可能になります。

### 管理者ユーザー

Seeder により以下の管理者ユーザーが作成されます。

- メールアドレス：`admin@example.com`
- パスワード：`Admin1234`

> ※ 管理者ユーザーには `role = admin` が付与されており、管理画面（勤怠一覧・修正申請一覧など）にアクセスできます。

---

## 環境構築

1. PHPコンテナに入る
    ```bash
    docker compose exec php bash
    ```

2. 依存関係をインストール
    ```bash
    composer install
    ```

3. .env ファイルを作成
    ```bash
    cp .env.example .env
    ```

4. `.env` の設定を修正
```
# =====================
# 基本設定
# =====================
APP_NAME=COACHTECH Attendance
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

LOG_CHANNEL=stack
LOG_LEVEL=debug

# =====================
# DB 設定（Docker 用）
# =====================
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=laravel_db
DB_USERNAME=laravel_user
DB_PASSWORD=laravel_pass

# =====================
# セッション / CSRF
# =====================
SESSION_DRIVER=file
SESSION_LIFETIME=120
SESSION_DOMAIN=localhost
SANCTUM_STATEFUL_DOMAINS=localhost

# =====================
# メール設定（MailHog）
# =====================
MAIL_MAILER=smtp
MAIL_HOST=mailhog
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="no-reply@example.com"
MAIL_FROM_NAME="COACHTECH Attendance"

# =====================
# ファイルアップロード
# =====================
FILESYSTEM_DRIVER=public

```

5. アプリケーションキーを生成
    ```bash
    php artisan key:generate
    ```

6. マイグレーションを実行
    ```bash
    php artisan migrate
    ```

7. シーディングを実行
    ```bash
    php artisan db:seed
    ```
### Seeder 内容

| Seeder名 | 内容 | 実行タイミング |
|----------|------|-----------------------------|
| **AdminUserSeeder** | 管理者ユーザーを1件作成 | `DatabaseSeeder` から自動実行される |
| UsersTableSeeder | 画面確認用の一般ユーザーを作成 | 必要に応じて手動実行 |
| AttendancesTableSeeder | 画面確認用の勤怠データを作成 | 必要に応じて手動実行 |
| AttendanceBreaksTableSeeder | 画面確認用の休憩データを作成 | 必要に応じて手動実行 |
| StampCorrectionRequestsTableSeeder | 画面確認用の修正申請データを作成 | 必要に応じて手動実行 |

#### 📌 手動実行例（必要な場合のみ）

```bash
# 一般ユーザーだけ流す
docker compose exec php php artisan db:seed --class=UsersTableSeeder

# 勤怠データと休憩データも合わせて流す
docker compose exec php php artisan db:seed --class=AttendancesTableSeeder
docker compose exec php php artisan db:seed --class=AttendanceBreaksTableSeeder

# 修正申請データも確認したい場合
docker compose exec php php artisan db:seed --class=StampCorrectionRequestsTableSeeder
```

## テスト実行方法
```bash
docker compose exec php bash
php artisan test
```
## 使用技術(実行環境)
分類
技術
言語
PHP 8.1
フレームワーク
Laravel 8.x
認証
Laravel Fortify / Laravel Sanctum
DB
MySQL 8.4
フロント
Blade / jQuery 3.7
インフラ
Docker / Docker Compose
管理ツール
phpMyAdmin / MailHog
テスト
PHPUnit 9.5
バージョン管理
Git / GitHub

## ER図
![alt text](ER.png)

## アプリケーションURL
ページ
URL
ユーザー登録
http://localhost/register
一般ログイン
http://localhost/login
管理者ログイン
http://localhost/admin/login
MailHog
http://localhost:8025
phpMyAdmin
http://localhost:8080
