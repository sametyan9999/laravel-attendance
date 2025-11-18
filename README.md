# laravel-attendance
一般ユーザーの勤怠打刻と、管理者による勤怠確認・修正承認を行う Web アプリケーションです。
Docker + Laravel 8 + MySQL 環境で動作します。

---

## 環境構築

### 1. リポジトリ取得

```bash
git clone https://github.com/sametyan9999/laravel-attendance.git
cd coachtech-attendance/src

2. Docker ビルド & 起動
# ビルド & 起動
docker compose up -d --build
使用コンテナ（docker-compose.yml）:
	•	nginx : ポート 80（アプリ本体）
	•	php : Laravel 実行用 PHP コンテナ
	•	mysql : MySQL 8.0（ボリューム名 attendance-db）
	•	phpmyadmin : phpMyAdmin（ポート 8080）
	•	mailhog : メール確認用（HTTP ポート 8025 / SMTP ポート 1025）

3. Laravel セットアップ
# PHP コンテナに入らずにコマンドを実行する形
docker compose exec php composer install

# .env 作成
cp src/.env.example src/.env

.env の主な設定（Docker 用）:
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=laravel_db
DB_USERNAME=laravel_user
DB_PASSWORD=laravel_pass

MAIL_MAILER=smtp
MAIL_HOST=mailhog
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="no-reply@example.com"
MAIL_FROM_NAME="COACHTECH ATTENDANCE"

# アプリケーションキー生成
docker compose exec php php artisan key:generate

# マイグレーション & シーディング
docker compose exec php php artisan migrate --seed
# もしくは、開発時はリセット用に
# docker compose exec php php artisan migrate:fresh --seed