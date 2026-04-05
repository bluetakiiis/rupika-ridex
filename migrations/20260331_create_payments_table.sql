-- Purpose: Store payment transactions tied to bookings, including Khalti metadata.---
CREATE TABLE IF NOT EXISTS payments (
	id                    INT AUTO_INCREMENT PRIMARY KEY,
	booking_id            INT NOT NULL,
	amount                INT NOT NULL,
	method                ENUM('khalti','cash') NOT NULL,
	status                ENUM('initiated','pending','success','failed','cancelled','refunded') NOT NULL,
	transaction_time      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	pidx                  VARCHAR(100),
	provider_response     LONGTEXT CHECK (JSON_VALID(provider_response)),
	created_at            TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	updated_at            TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	CONSTRAINT fk_payments_booking FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
);
