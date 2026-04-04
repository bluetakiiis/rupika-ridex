<?php
/**
 * Purpose: Import tracked SQL snapshot into DB so deployed data matches committed state.
 */

require_once __DIR__ . '/../config/database.php';

$inputPath = APP_ROOT . '/data/deploy/db_snapshot.sql';
$ifMissingOk = false;

$args = array_slice($_SERVER['argv'] ?? [], 1);
foreach ($args as $arg) {
	if ($arg === '--help' || $arg === '-h') {
		echo 'Usage: php bin/import_db_snapshot.php [--path=path] [--if-missing-ok]' . PHP_EOL;
		echo 'Applies SQL snapshot statements in order.' . PHP_EOL;
		exit(0);
	}

	if ($arg === '--if-missing-ok') {
		$ifMissingOk = true;
		continue;
	}

	if (str_starts_with($arg, '--path=')) {
		$rawPath = trim((string) substr($arg, 7));
		if ($rawPath !== '') {
			$inputPath = str_starts_with($rawPath, '/') || str_starts_with($rawPath, '\\') || preg_match('/^[A-Za-z]:[\\\\\/]/', $rawPath)
				? $rawPath
				: APP_ROOT . '/' . ltrim(str_replace('\\', '/', $rawPath), '/');
		}
	}
}

$executeSqlScript = static function (PDO $pdo, string $script): int {
	$length = strlen($script);
	$buffer = '';
	$statementCount = 0;

	$inSingleQuote = false;
	$inDoubleQuote = false;
	$inBacktick = false;
	$inLineComment = false;
	$inBlockComment = false;

	for ($i = 0; $i < $length; $i++) {
		$char = $script[$i];
		$next = $i + 1 < $length ? $script[$i + 1] : '';
		$next2 = $i + 2 < $length ? $script[$i + 2] : '';

		if ($inLineComment) {
			if ($char === "\n") {
				$inLineComment = false;
			}
			continue;
		}

		if ($inBlockComment) {
			if ($char === '*' && $next === '/') {
				$inBlockComment = false;
				$i++;
			}
			continue;
		}

		if (!$inSingleQuote && !$inDoubleQuote && !$inBacktick) {
			if ($char === '-' && $next === '-' && ($next2 === '' || ctype_space($next2))) {
				$inLineComment = true;
				$i++;
				continue;
			}

			if ($char === '#') {
				$inLineComment = true;
				continue;
			}

			if ($char === '/' && $next === '*') {
				$inBlockComment = true;
				$i++;
				continue;
			}
		}

		if ($char === "'" && !$inDoubleQuote && !$inBacktick) {
			if ($inSingleQuote && $next === "'") {
				$buffer .= "''";
				$i++;
				continue;
			}

			$isEscaped = $i > 0 && $script[$i - 1] === '\\';
			if (!$isEscaped) {
				$inSingleQuote = !$inSingleQuote;
			}
			$buffer .= $char;
			continue;
		}

		if ($char === '"' && !$inSingleQuote && !$inBacktick) {
			if ($inDoubleQuote && $next === '"') {
				$buffer .= '""';
				$i++;
				continue;
			}

			$isEscaped = $i > 0 && $script[$i - 1] === '\\';
			if (!$isEscaped) {
				$inDoubleQuote = !$inDoubleQuote;
			}
			$buffer .= $char;
			continue;
		}

		if ($char === '`' && !$inSingleQuote && !$inDoubleQuote) {
			if ($inBacktick && $next === '`') {
				$buffer .= '``';
				$i++;
				continue;
			}

			$inBacktick = !$inBacktick;
			$buffer .= $char;
			continue;
		}

		if ($char === ';' && !$inSingleQuote && !$inDoubleQuote && !$inBacktick) {
			$statement = trim($buffer);
			if ($statement !== '') {
				$pdo->exec($statement);
				$statementCount++;
			}
			$buffer = '';
			continue;
		}

		$buffer .= $char;
	}

	$tailStatement = trim($buffer);
	if ($tailStatement !== '') {
		$pdo->exec($tailStatement);
		$statementCount++;
	}

	return $statementCount;
};

try {
	if (!is_file($inputPath)) {
		if ($ifMissingOk) {
			echo 'Snapshot file not found. Skipping import: ' . $inputPath . PHP_EOL;
			exit(0);
		}

		throw new RuntimeException('Snapshot file not found: ' . $inputPath);
	}

	$sql = file_get_contents($inputPath);
	if ($sql === false) {
		throw new RuntimeException('Unable to read snapshot file: ' . $inputPath);
	}

	if (trim($sql) === '') {
		echo 'Snapshot file is empty. Nothing to import.' . PHP_EOL;
		exit(0);
	}

	$pdo = db();
	$statementCount = $executeSqlScript($pdo, $sql);
	echo 'Snapshot imported from: ' . $inputPath . PHP_EOL;
	echo 'Statements executed: ' . $statementCount . PHP_EOL;
} catch (Throwable $exception) {
	fwrite(STDERR, 'Database snapshot import failed: ' . $exception->getMessage() . PHP_EOL);
	exit(1);
}
