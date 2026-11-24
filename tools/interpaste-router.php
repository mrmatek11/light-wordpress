<?php
// php -S 127.0.0.1:8080 -t src tools/interpaste-router.php
$root = rtrim( $_SERVER['DOCUMENT_ROOT'], '/' );
$path = rawurldecode( parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH ) );

if ( '/admin' === $path ) {
	header( 'Location: /admin/' . ( empty( $_SERVER['QUERY_STRING'] ) ? '' : '?' . $_SERVER['QUERY_STRING'] ), true, 301 );
	return true;
}
$path = preg_replace(
	array( '#^/admin/#', '#^/login/?$#', '#^/content/#', '#^/includes/#' ),
	array( '/wp-admin/', '/wp-login.php', '/wp-content/', '/wp-includes/' ),
	$path,
	1
);
if ( str_ends_with( $path, '/' ) ) {
	$path .= 'index.php';
}
$file = realpath( $root . $path );
if ( ! $file || ! str_starts_with( $file, realpath( $root ) . '/' ) || ! is_file( $file ) ) {
	$path = '/index.php';
	$file = $root . $path;
}
if ( str_ends_with( $file, '.php' ) ) {
	$_SERVER['SCRIPT_NAME']     = $path;
	$_SERVER['PHP_SELF']        = $path;
	$_SERVER['SCRIPT_FILENAME'] = $file;
	chdir( dirname( $file ) );
	require $file;
	return true;
}
$types = array( 'css' => 'text/css', 'js' => 'text/javascript', 'svg' => 'image/svg+xml', 'json' => 'application/json', 'woff2' => 'font/woff2', 'woff' => 'font/woff', 'png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'gif' => 'image/gif', 'webp' => 'image/webp', 'ico' => 'image/x-icon' );
header( 'Content-Type: ' . ( $types[ strtolower( pathinfo( $file, PATHINFO_EXTENSION ) ) ] ?? mime_content_type( $file ) ) );
readfile( $file );
return true;
