-- PickRide database schema
-- Import this file into MySQL before using the site:
--   mysql -u root -p < database.sql

CREATE DATABASE IF NOT EXISTS pickride CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE pickride;

-- Riders (customers)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    phone VARCHAR(20) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Vehicle types (tuk, car, van, etc) with pricing rules
CREATE TABLE vehicle_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    description VARCHAR(255),
    base_fare DECIMAL(10,2) NOT NULL,
    rate_per_km DECIMAL(10,2) NOT NULL,
    icon VARCHAR(50) DEFAULT 'tuk',
    is_active TINYINT(1) DEFAULT 1
);

-- Drivers
CREATE TABLE drivers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(150),
    vehicle_type_id INT,
    vehicle_plate VARCHAR(20),
    status ENUM('available','on_trip','offline') DEFAULT 'offline',
    username VARCHAR(60) UNIQUE,
    password_hash VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vehicle_type_id) REFERENCES vehicle_types(id) ON DELETE SET NULL
);

-- Bookings / rides
CREATE TABLE bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    driver_id INT DEFAULT NULL,
    vehicle_type_id INT NOT NULL,
    pickup_location VARCHAR(255) NOT NULL,
    dropoff_location VARCHAR(255) NOT NULL,
    distance_km DECIMAL(6,2) NOT NULL,
    estimated_fare DECIMAL(10,2) NOT NULL,
    status ENUM('pending','confirmed','on_trip','completed','cancelled') DEFAULT 'pending',
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (driver_id) REFERENCES drivers(id) ON DELETE SET NULL,
    FOREIGN KEY (vehicle_type_id) REFERENCES vehicle_types(id)
);

-- Admin accounts
CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL
);

-- Seed data
INSERT INTO vehicle_types (name, description, base_fare, rate_per_km, icon) VALUES
('Tuk', 'Three-wheeler, quick and affordable', 100.00, 60.00, 'tuk'),
('Car', 'Air-conditioned sedan for up to 4', 250.00, 90.00, 'car'),
('Van', 'Spacious ride for groups up to 7', 400.00, 120.00, 'van');

-- Default admin login: admin@pickride.lk / admin123
-- Change this password after first login.
INSERT INTO admins (full_name, email, password_hash) VALUES
('Site Admin', 'admin@pickride.lk', '$2b$10$fvcfIy.tDe1g9Uu6YIMYY.5vV.ckvSMb7z4jiP1oGK9DkMA11CmNO');
