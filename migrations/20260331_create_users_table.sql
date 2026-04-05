-- Purpose: Define the users table for authentication and account roles (customers and admins).---
CREATE TABLE IF NOT EXISTS users (
	id              INT AUTO_INCREMENT PRIMARY KEY,
	name            VARCHAR(100) NOT NULL,
	first_name      VARCHAR(100),
	last_name       VARCHAR(100),
	email           VARCHAR(150) NOT NULL UNIQUE,
	password_hash   VARCHAR(255) NOT NULL,
	phone           VARCHAR(20),
	address         VARCHAR(255),
	date_of_birth   DATE,
	street          VARCHAR(255),
	post_code       VARCHAR(20),
	city            VARCHAR(100),
	province        ENUM('Koshi','Madhesh','Bagmati','Gandaki','Lumbini','Karnali','Sudurpashchim') DEFAULT NULL,
	role            ENUM('user','admin') NOT NULL DEFAULT 'user',
	drivers_id      VARCHAR(50),
	created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
