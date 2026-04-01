-- Purpose: Track maintenance/repair jobs for vehicles while out of service.
-- Website Section: Admin Maintenance Management.
-- Developer Notes: Tie to vehicles; capture issue, workshop, schedule, cost, and status lifecycle.
DROP TABLE IF EXISTS maintenance_records;
CREATE TABLE maintenance_records (
	id                    INT AUTO_INCREMENT PRIMARY KEY,
	vehicle_id            INT NOT NULL,
	issue_description     TEXT NOT NULL,
	workshop_name         VARCHAR(100) NOT NULL,
	estimated_completion  DATETIME NOT NULL,
	service_cost          INT NOT NULL,
	status                ENUM('ongoing','completed') NOT NULL DEFAULT 'ongoing',
	created_at            TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	updated_at            TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	CONSTRAINT fk_maintenance_vehicle FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE
);
