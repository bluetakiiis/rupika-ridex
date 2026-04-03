-- Purpose: Create categories lookup for grouping vehicles (e.g., SUV, Sedan, Luxury).
-- Website Section: Vehicle Catalog (filters and badges on listings/detail pages).
-- Developer Notes: Seed with initial categories; referenced by vehicles.category_id.
CREATE TABLE IF NOT EXISTS categories (
	id          INT AUTO_INCREMENT PRIMARY KEY,
	name        VARCHAR(50) NOT NULL UNIQUE,
	description TEXT
);
