<?php
/**
 * Purpose: Bootstrap app configuration (environment loading, base URL, timezone, error settings).
 * Website Section: Global Configuration.
 * Developer Notes: Load .env values, set timezone/locale, configure display errors vs production, and expose config constants.
 */

$rootPath = dirname(__DIR__);
$envPath = $rootPath . DIRECTORY_SEPARATOR . '.env';

if (is_file($envPath)) {
	$lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
	foreach ($lines as $line) {
		$line = trim($line);
		if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
			continue;
		}

		[$name, $value] = array_map('trim', explode('=', $line, 2));
		$value = trim($value, "\"'");
		if ($name !== '') {
			$_ENV[$name] = $value;
			$_SERVER[$name] = $value;
			putenv($name . '=' . $value);
		}
	}
}

if (!function_exists('env')) {
	function env(string $key, mixed $default = null): mixed
	{
		$value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

		return $value === false || $value === null || $value === '' ? $default : $value;
	}
}

if (!defined('APP_ROOT')) {
	define('APP_ROOT', $rootPath);
}

if (!defined('BASE_URL')) {
	define('BASE_URL', rtrim((string) env('BASE_URL', '/rentals-app/public'), '/'));
}

if (!defined('DB_HOST')) {
	define('DB_HOST', (string) env('DB_HOST', '127.0.0.1'));
}

if (!defined('DB_PORT')) {
	define('DB_PORT', (int) env('DB_PORT', 3306));
}

if (!defined('DB_NAME')) {
	define('DB_NAME', (string) env('DB_NAME', 'ridex_db'));
}

if (!defined('DB_USER')) {
	define('DB_USER', (string) env('DB_USER', 'root'));
}

if (!defined('DB_PASS')) {
	define('DB_PASS', (string) env('DB_PASS', ''));
}

if (!defined('APP_TIMEZONE')) {
	define('APP_TIMEZONE', (string) env('APP_TIMEZONE', 'Asia/Kathmandu'));
}

date_default_timezone_set(APP_TIMEZONE);
