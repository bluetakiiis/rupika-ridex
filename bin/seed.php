<?php
/**
 * Purpose: CLI seeder to insert initial data (admin user, categories, sample vehicles).
 * Website Section: DevOps/Setup.
 * Developer Notes: Use models/DB to create default admin credentials, base categories, and demo records for development.
 */

require_once __DIR__ . '/../config/database.php';

$pdo = db();
$pdo->beginTransaction();

try {
	$categories = [
		['Cars', 'Passenger cars and sedans'],
		['Bikes', 'Two-wheel vehicles'],
		['Luxury', 'Premium and high-end vehicles'],
	];

	$categoryStmt = $pdo->prepare('SELECT id FROM categories WHERE name = :name LIMIT 1');
	$categoryInsertStmt = $pdo->prepare('INSERT INTO categories (name, description) VALUES (:name, :description)');

	foreach ($categories as [$name, $description]) {
		$categoryStmt->execute(['name' => $name]);
		if (!$categoryStmt->fetchColumn()) {
			$categoryInsertStmt->execute([
				'name' => $name,
				'description' => $description,
			]);
		}
	}

	$pdo->commit();
	echo "Seed completed" . PHP_EOL;
} catch (Throwable $exception) {
	$pdo->rollBack();
	fwrite(STDERR, 'Seed failed: ' . $exception->getMessage() . PHP_EOL);
	exit(1);
}
