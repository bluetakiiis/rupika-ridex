-- Purpose: Create vehicle maintenance records to persist maintenance fill/edit history.
-- Website Section: Admin Fleet Management.
-- Developer Notes: Stores issue, workshop, estimate, service cost, and completion state per vehicle.
CREATE TABLE IF NOT EXISTS vehicle_maintenance_records (
	id INT AUTO_INCREMENT PRIMARY KEY,
	vehicle_id INT NOT NULL,
	issue_description TEXT NOT NULL,
	workshop_name VARCHAR(150) NOT NULL,
	estimate_completion_date DATE NOT NULL,
	service_cost DECIMAL(10,2) NOT NULL DEFAULT 0,
	status ENUM('open','completed') NOT NULL DEFAULT 'open',
	completed_at TIMESTAMP NULL DEFAULT NULL,
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	CONSTRAINT fk_maintenance_records_vehicle FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE,
	INDEX idx_maintenance_vehicle_status (vehicle_id, status),
	INDEX idx_maintenance_vehicle_created (vehicle_id, created_at)
);