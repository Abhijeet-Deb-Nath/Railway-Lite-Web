-- Railway Management System - Clean 6-Table Schema
-- Database: railway_db

DROP DATABASE IF EXISTS railway_db;
CREATE DATABASE railway_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE railway_db;

-- Table 1: Stations
CREATE TABLE stations (
    station_id INT PRIMARY KEY AUTO_INCREMENT,
    station_name VARCHAR(100) NOT NULL,
    city VARCHAR(100) NOT NULL,
    station_code VARCHAR(10) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Table 2: Trains
CREATE TABLE trains (
    train_id INT PRIMARY KEY AUTO_INCREMENT,
    train_name VARCHAR(100) NOT NULL,
    train_type ENUM('Express', 'Intercity', 'Local', 'Mail') NOT NULL,
    total_seats INT NOT NULL DEFAULT 200,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Table 3: Routes
CREATE TABLE routes (
    route_id INT PRIMARY KEY AUTO_INCREMENT,
    from_station_id INT NOT NULL,
    to_station_id INT NOT NULL,
    distance_km DECIMAL(6,2) NOT NULL,
    base_fare DECIMAL(8,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (from_station_id) REFERENCES stations(station_id) ON DELETE CASCADE,
    FOREIGN KEY (to_station_id) REFERENCES stations(station_id) ON DELETE CASCADE,
    CONSTRAINT chk_different_stations CHECK (from_station_id != to_station_id)
) ENGINE=InnoDB;

-- Table 4: Trips
CREATE TABLE trips (
    trip_id INT PRIMARY KEY AUTO_INCREMENT,
    train_id INT NOT NULL,
    route_id INT NOT NULL,
    trip_date DATE NOT NULL,
    departure_time TIME NOT NULL,
    arrival_time TIME NOT NULL,
    available_seats INT NOT NULL,
    status ENUM('Scheduled', 'Running', 'Completed', 'Cancelled') DEFAULT 'Scheduled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (train_id) REFERENCES trains(train_id) ON DELETE CASCADE,
    FOREIGN KEY (route_id) REFERENCES routes(route_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Table 5: Passengers
CREATE TABLE passengers (
    passenger_id INT PRIMARY KEY AUTO_INCREMENT,
    passenger_name VARCHAR(100) NOT NULL,
    phone VARCHAR(15) UNIQUE NOT NULL,
    email VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Table 6: Bookings
CREATE TABLE bookings (
    booking_id INT PRIMARY KEY AUTO_INCREMENT,
    trip_id INT NOT NULL,
    passenger_id INT NOT NULL,
    seats_booked INT NOT NULL DEFAULT 1,
    total_fare DECIMAL(10,2) NOT NULL,
    booking_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    status ENUM('Confirmed', 'Cancelled', 'Completed') DEFAULT 'Confirmed',
    FOREIGN KEY (trip_id) REFERENCES trips(trip_id) ON DELETE CASCADE,
    FOREIGN KEY (passenger_id) REFERENCES passengers(passenger_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Indexes for better query performance
CREATE INDEX idx_route_stations ON routes(from_station_id, to_station_id);
CREATE INDEX idx_trip_date ON trips(trip_date);
CREATE INDEX idx_booking_date ON bookings(booking_date);
CREATE INDEX idx_passenger_phone ON passengers(phone);
