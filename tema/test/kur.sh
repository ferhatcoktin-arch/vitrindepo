#!/usr/bin/env bash
# Temayı yerel bir WordPress'te dener: MariaDB + WordPress + sahte WooCommerce + örnek ürünler,
# sonra http://127.0.0.1:8080 adresinde PHP'nin yerleşik sunucusunu başlatır.
#
# Kullanım: tema/test/kur.sh [çalışma klasörü]   (varsayılan: /tmp/vitrin-test)
# Gerekenler: php (mysqli, gd), mariadb-server. WordPress wordpress.org'dan, erişilemezse npm'deki
# @wp-playground/wordpress-builds paketinden alınır.
set -euo pipefail

TEST_DIR="$(cd "$(dirname "$0")" && pwd)"
REPO="$(cd "$TEST_DIR/../.." && pwd)"
WORK="${1:-/tmp/vitrin-test}"
WP="$WORK/wp"
mkdir -p "$WORK"

if [ ! -f "$WP/wp-settings.php" ]; then
	echo "WordPress indiriliyor..."
	if curl -fsSL --max-time 60 https://wordpress.org/latest.tar.gz -o "$WORK/wp.tar.gz"; then
		tar -xzf "$WORK/wp.tar.gz" -C "$WORK" && mv "$WORK/wordpress" "$WP"
	else
		(cd "$WORK" && npm pack --silent @wp-playground/wordpress-builds@0.9.19 >/dev/null)
		tar -xzf "$WORK"/wp-playground-wordpress-builds-*.tgz -C "$WORK" package/src/wordpress/wp-6.5.zip
		unzip -q "$WORK/package/src/wordpress/wp-6.5.zip" -d "$WP"
	fi
fi

if ! mariadb -uroot -e 'SELECT 1' >/dev/null 2>&1; then
	service mariadb start >/dev/null
fi
mariadb -uroot -e "DROP DATABASE IF EXISTS vitrin_test; CREATE DATABASE vitrin_test CHARACTER SET utf8mb4;
	CREATE USER IF NOT EXISTS 'vitrin'@'localhost' IDENTIFIED BY 'vitrin';
	GRANT ALL ON vitrin_test.* TO 'vitrin'@'localhost';"

cat > "$WP/wp-config.php" <<'PHP'
<?php
define( 'DB_NAME', 'vitrin_test' );
define( 'DB_USER', 'vitrin' );
define( 'DB_PASSWORD', 'vitrin' );
define( 'DB_HOST', 'localhost:/run/mysqld/mysqld.sock' );
define( 'DB_CHARSET', 'utf8mb4' );
define( 'DB_COLLATE', '' );
$table_prefix = 'wp_';
foreach ( array( 'AUTH_KEY', 'SECURE_AUTH_KEY', 'LOGGED_IN_KEY', 'NONCE_KEY', 'AUTH_SALT', 'SECURE_AUTH_SALT', 'LOGGED_IN_SALT', 'NONCE_SALT' ) as $vitrin_key ) {
	define( $vitrin_key, 'yerel-test-' . $vitrin_key );
}
define( 'WP_HOME', 'http://127.0.0.1:8080' );
define( 'WP_SITEURL', 'http://127.0.0.1:8080' );
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_DISPLAY', false ); // WordPress 6.5 + PHP 8.4 çekirdek uyarıları sayfayı bozmasın; hepsi debug.log'da
define( 'WP_DEBUG_LOG', __DIR__ . '/debug.log' );
define( 'DISABLE_WP_CRON', true );
define( 'WP_HTTP_BLOCK_EXTERNAL', true );
define( 'AUTOMATIC_UPDATER_DISABLED', true );
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}
require_once ABSPATH . 'wp-settings.php';
PHP

rm -f "$WP/debug.log"
mkdir -p "$WP/wp-content/mu-plugins" "$WP/wp-content/themes"
cp "$TEST_DIR/sahte-woocommerce.php" "$TEST_DIR/sahte-woocommerce.css" "$WP/wp-content/mu-plugins/"
ln -sfn "$REPO/tema/vitrin-hizli" "$WP/wp-content/themes/vitrin-hizli"

php -r '
define( "WP_INSTALLING", true );
$_SERVER["HTTP_HOST"] = "127.0.0.1:8080";
require $argv[1] . "/wp-load.php";
require_once ABSPATH . "wp-admin/includes/upgrade.php";
wp_install( "IQOS Vitrin", "admin", "admin@example.test", true, "", "admin" );
update_option( "permalink_structure", "/%postname%/" );
' "$WP" >/dev/null 2>&1  # yönetici e-postası gönderilemez uyarısını gizle
php "$TEST_DIR/tohum.php" "$WP"

pkill -f "php -S 127.0.0.1:8080" 2>/dev/null || true
nohup php -S 127.0.0.1:8080 -t "$WP" "$TEST_DIR/router.php" > "$WORK/server.log" 2>&1 &
sleep 1
echo "Hazır: http://127.0.0.1:8080  (tema kaynaklı PHP uyarıları: grep vitrin-hizli $WP/debug.log)"
