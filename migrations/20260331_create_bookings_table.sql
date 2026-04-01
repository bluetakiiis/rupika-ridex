-- Purpose: Store reservation/rental records with booking lifecycle and payment status.
-- Website Section: Booking & Payment Flow (user checkout, admin booking oversight).
-- Developer Notes: Links to user, vehicle; includes pickup/return data, booking_number, drivers_id, payment tracking fields.
DROP TABLE IF EXISTS bookings;
CREATE TABLE bookings (
	id                    INT AUTO_INCREMENT PRIMARY KEY,
	booking_number        VARCHAR(15) NOT NULL UNIQUE,
	user_id               INT NOT NULL,
	vehicle_id            INT NOT NULL,
	pickup_location       VARCHAR(255),
	return_location       VARCHAR(255),
	pickup_datetime       DATETIME NOT NULL,
	return_datetime       DATETIME NOT NULL,
	return_time           DATETIME NULL,
	status                ENUM('pending','reserved','ready','on_trip','overdue','completed','cancelled') NOT NULL DEFAULT 'pending',
	payment_status        ENUM('unpaid','pending','paid','cancelled','refunded') NOT NULL DEFAULT 'unpaid',
	payment_method        ENUM('pay_on_arrival','khalti') NOT NULL,
	total_amount          INT NOT NULL,
	paid_amount           INT NOT NULL DEFAULT 0,
	late_fee              INT NOT NULL DEFAULT 0,
	drivers_id            VARCHAR(50) NOT NULL,
	created_at            TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	updated_at            TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	CONSTRAINT fk_bookings_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
	CONSTRAINT fk_bookings_vehicle FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE RESTRICT
);
