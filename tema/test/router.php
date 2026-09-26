<?php
/**
 * PHP'nin yerleşik sunucusu için WordPress yönlendiricisi (güzel kalıcı bağlantılar).
 * Kullanım: php -S 127.0.0.1:8080 -t <wordpress klasörü> router.php
 */

$root = $_SERVER['DOCUMENT_ROOT'];
$path = urldecode( parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH ) );

if ( '/' !== $path && is_file( $root . $path ) ) {
	if ( '.php' !== substr( $path, -4 ) ) {
		return false;
	}
	chdir( dirname( $root . $path ) );
	require $root . $path;
	return true;
}

$_SERVER['SCRIPT_NAME']     = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = $root . '/index.php';
chdir( $root );
require $root . '/index.php';
