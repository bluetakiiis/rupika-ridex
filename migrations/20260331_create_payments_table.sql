-- Purpose: Store payment transactions tied to bookings, including Khalti metadata.
-- Website Section: Booking & Payment Flow (gateway callbacks and cash settlements).
-- Developer Notes: Use booking_id FK; includes pidx/provider_response for Khalti reconciliation and status lifecycle.
CREATE TABLE IF NOT EXISTS payments (
	id                    INT AUTO_INCREMENT PRIMARY KEY,
	booking_id            INT NOT NULL,
	amount                INT NOT NULL,
	method                ENUM('khalti','cash') NOT NULL,
	status                ENUM('initiated','pending','success','failed','cancelled','refunded') NOT NULL,
	transaction_time      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	pidx                  VARCHAR(100),
	provider_response     JSON,
	created_at            TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	updated_at            TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	CONSTRAINT fk_payments_booking FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
);
