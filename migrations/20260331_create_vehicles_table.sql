-- Purpose: Create vehicles table capturing fleet metadata, pricing, status, and tracking fields.
-- Website Section: Vehicle Catalog & Admin Fleet Management.
-- Developer Notes: Requires categories table; includes status lifecycle, GPS hook, and service metadata.
CREATE TABLE IF NOT EXISTS vehicles (
	id                    INT AUTO_INCREMENT PRIMARY KEY,
	category_id           INT NOT NULL,
	vehicle_type          ENUM('cars','bikes','luxury') NOT NULL DEFAULT 'cars',
	short_name            VARCHAR(100) NOT NULL,
	full_name             VARCHAR(150) NOT NULL,
	price_per_day         INT NOT NULL,
	driver_age_requirement INT NOT NULL,
	image_path            VARCHAR(255),
	number_of_seats       INT,
	transmission_type     ENUM('manual','automatic','hybrid','N/A') DEFAULT 'manual',
	fuel_type             ENUM('petrol','diesel','electric') DEFAULT 'petrol',
	license_plate         VARCHAR(50) NOT NULL UNIQUE,
	status                ENUM('available','reserved','on_trip','overdue','maintenance') NOT NULL DEFAULT 'available',
	gps_id                VARCHAR(50),
	last_service_date     DATE,
	description           TEXT,
	created_at            TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	updated_at            TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	CONSTRAINT fk_vehicles_category FOREIGN KEY (category_id) REFERENCES categories(id)
);
