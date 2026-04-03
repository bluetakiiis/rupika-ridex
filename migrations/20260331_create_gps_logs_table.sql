-- Purpose: Capture time-series GPS telemetry for vehicles for live map and history.
-- Website Section: GPS Tracking (user live map, admin tracking/history).
-- Developer Notes: Composite index on vehicle_id/timestamp; stores speed, heading, fuel, safety_score.
CREATE TABLE IF NOT EXISTS gps_logs (
	id                    BIGINT AUTO_INCREMENT PRIMARY KEY,
	vehicle_id            INT NOT NULL,
	timestamp             DATETIME NOT NULL,
	latitude              DECIMAL(9,6) NOT NULL,
	longitude             DECIMAL(9,6) NOT NULL,
	speed                 DECIMAL(5,2),
	heading               DECIMAL(5,2),
	fuel_level            DECIMAL(5,2),
	safety_score          DECIMAL(5,2),
	created_at            TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	CONSTRAINT fk_gpslogs_vehicle FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE,
	INDEX idx_vehicle_timestamp (vehicle_id, timestamp)
);
