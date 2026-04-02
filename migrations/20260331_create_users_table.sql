-- Purpose: Define the users table for authentication and account roles (customers and admins).
-- Website Section: Authentication & Account Management (user login/registration, admin access).
-- Developer Notes: Run this migration first; enforces unique email and stores profile/contact plus drivers_id for booking flow.
DROP TABLE IF EXISTS users;
CREATE TABLE users (
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
	district        ENUM('Koshi','Madhesh','Bagmati','Gandaki','Lumbini','Karnali','Sudurpashchim'),
	role            ENUM('user','admin') NOT NULL DEFAULT 'user',
	drivers_id      VARCHAR(50),
	created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
