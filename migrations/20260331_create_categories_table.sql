-- Purpose: Create categories lookup for grouping vehicles (e.g., SUV, Sedan, Luxury).---
CREATE TABLE IF NOT EXISTS categories (
	id          INT AUTO_INCREMENT PRIMARY KEY,
	name        VARCHAR(50) NOT NULL UNIQUE,
	description TEXT
);
