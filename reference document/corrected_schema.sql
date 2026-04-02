-- Database schema for vehicle rental system (corrected to align with UI flow)

-- Drop tables if they already exist to allow repeatable migrations
DROP TABLE IF EXISTS gps_logs;
DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS bookings;
DROP TABLE IF EXISTS maintenance_records;
DROP TABLE IF EXISTS vehicles;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS users;

-- -------------------------------------------------------------------------
-- Users
-- Stores both customers and admin accounts.  Includes contact details and a
-- drivers_id field used when creating bookings (drivers licence/ID number).
-- Added an updated_at column for auditing and role enumeration from the UI.
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
  drivers_id      VARCHAR(50),  -- e.g. driver licence number; optional for admin
  created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- -------------------------------------------------------------------------
-- Categories
-- Simple lookup table for vehicle categories such as Car, Bike or Luxury.
CREATE TABLE categories (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(50) NOT NULL UNIQUE,
  description TEXT
);

-- -------------------------------------------------------------------------
-- Vehicles
-- Defines each vehicle in the fleet.  Fields follow the UI spec:
--  - short_name / full_name: names used in cards vs detail pages
--  - price_per_day stored as integer cents
--  - driver_age_requirement is an integer (18 or 21)
--  - number_of_seats, transmission_type, fuel_type, license_plate, last_service_date
--  - status now allows reserved, ready, on_trip and overdue in addition to available and maintenance
--  - gps_id for real‑time tracking integration
CREATE TABLE vehicles (
  id                    INT AUTO_INCREMENT PRIMARY KEY,
  category_id           INT NOT NULL,
  vehicle_type          ENUM('cars','bikes','luxury') NOT NULL DEFAULT 'cars',
  short_name            VARCHAR(100) NOT NULL,
  full_name             VARCHAR(150) NOT NULL,
  price_per_day         INT NOT NULL,
  driver_age_requirement INT NOT NULL,
  image_path            VARCHAR(255),
  number_of_seats       INT,
  transmission_type     ENUM('manual','automatic','hybrid') DEFAULT 'manual',
  fuel_type             ENUM('petrol','diesel','electric') DEFAULT 'petrol',
  license_plate         VARCHAR(50) NOT NULL UNIQUE,
  status                ENUM('available','reserved','ready','on_trip','overdue','maintenance') NOT NULL DEFAULT 'available',
  gps_id                VARCHAR(50),
  last_service_date     DATE,
  description           TEXT,
  created_at            TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at            TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_vehicles_category FOREIGN KEY (category_id) REFERENCES categories(id)
);

-- -------------------------------------------------------------------------
-- Maintenance Records
-- Captures service and repair information for vehicles when marked as
-- maintenance.  Each record is tied to a vehicle and may include details
-- about the issue, workshop and cost.  Separate status to denote completed
-- maintenance.
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

-- -------------------------------------------------------------------------
-- Bookings
-- Tracks reservations/rentals.  Reflects the UI booking flow by splitting
-- booking status from payment status.  Includes pickup/return locations and
-- datetimes, a booking_number following the #XX-0000 pattern, driver ID,
-- late fee and actual return time (entered by admin).  Payment method is
-- recorded here (pay_on_arrival or khalti) to distinguish flows.
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

-- -------------------------------------------------------------------------
-- Payments
-- Stores transaction details separate from bookings.  Includes pidx for
-- Khalti transactions, and status values tailored for the gateway.  Use the
-- booking_id as a foreign key; cascade deletes to remove orphan records.
CREATE TABLE payments (
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

-- -------------------------------------------------------------------------
-- GPS Logs
-- Stores real‑time tracking data for vehicles.  Additional columns allow
-- recording fuel level and safety scores for AI dashboards.  Composite index
-- on vehicle_id and timestamp optimises time‑series queries.
CREATE TABLE gps_logs (
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