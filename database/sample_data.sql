-- Sample Data for Railway Management System (Bangladesh Railway)
USE railway_db;

-- Insert Stations (Major Bangladesh Railway Stations)
INSERT INTO stations (station_name, city, station_code) VALUES
('Kamalapur Railway Station', 'Dhaka', 'DKA'),
('Chittagong Railway Station', 'Chittagong', 'CTG'),
('Rajshahi Railway Station', 'Rajshahi', 'RJH'),
('Sylhet Railway Station', 'Sylhet', 'SYL'),
('Khulna Railway Station', 'Khulna', 'KHL'),
('Mymensingh Railway Station', 'Mymensingh', 'MYM'),
('Rangpur Railway Station', 'Rangpur', 'RGP'),
('Comilla Railway Station', 'Comilla', 'CML'),
('Dinajpur Railway Station', 'Dinajpur', 'DNJ'),
('Jessore Railway Station', 'Jessore', 'JSR'),
('Narayanganj Railway Station', 'Narayanganj', 'NRG'),
('Bogra Railway Station', 'Bogra', 'BGR');

-- Insert Trains (Famous Bangladesh Railway Trains)
INSERT INTO trains (train_name, train_type, total_seats) VALUES
('Suborna Express', 'Express', 450),
('Turna Nishitha', 'Mail', 380),
('Padma Express', 'Express', 420),
('Mohanagar Godhuli', 'Intercity', 350),
('Silk City Express', 'Express', 400),
('Parabat Express', 'Intercity', 320),
('Rangpur Express', 'Express', 380),
('Karnaphuli Express', 'Express', 360),
('Upakul Express', 'Mail', 340),
('Drutojan Express', 'Intercity', 300);

-- Insert Routes (Major Bangladesh Railway Routes)
INSERT INTO routes (from_station_id, to_station_id, distance_km, base_fare) VALUES
(1, 2, 320.00, 450.00),  -- Dhaka to Chittagong
(1, 3, 256.00, 380.00),  -- Dhaka to Rajshahi
(1, 4, 198.00, 320.00),  -- Dhaka to Sylhet
(1, 5, 327.00, 420.00),  -- Dhaka to Khulna
(1, 6, 120.00, 180.00),  -- Dhaka to Mymensingh
(2, 4, 410.00, 520.00),  -- Chittagong to Sylhet
(3, 7, 142.00, 220.00),  -- Rajshahi to Rangpur
(1, 8, 97.00, 150.00),   -- Dhaka to Comilla
(7, 9, 112.00, 180.00),  -- Rangpur to Dinajpur
(5, 10, 68.00, 120.00),  -- Khulna to Jessore
(1, 11, 25.00, 50.00),   -- Dhaka to Narayanganj
(3, 12, 72.00, 140.00);  -- Rajshahi to Bogra

-- Insert Trips (Scheduled trips for Bangladesh Railway)
INSERT INTO trips (train_id, route_id, trip_date, departure_time, arrival_time, available_seats, status) VALUES
-- Dhaka to Chittagong (Suborna Express)
(1, 1, '2025-10-26', '23:00:00', '05:30:00', 420, 'Scheduled'),
(1, 1, '2025-10-27', '23:00:00', '05:30:00', 450, 'Scheduled'),
-- Dhaka to Rajshahi (Padma Express)
(3, 2, '2025-10-26', '06:50:00', '13:20:00', 400, 'Scheduled'),
(3, 2, '2025-10-27', '06:50:00', '13:20:00', 420, 'Scheduled'),
-- Dhaka to Sylhet (Parabat Express)
(6, 3, '2025-10-26', '14:00:00', '20:30:00', 310, 'Scheduled'),
(6, 3, '2025-10-27', '14:00:00', '20:30:00', 320, 'Scheduled'),
-- Dhaka to Khulna (Silk City Express)
(5, 4, '2025-10-26', '07:20:00', '14:50:00', 380, 'Scheduled'),
-- Dhaka to Mymensingh (Mohanagar Godhuli)
(4, 5, '2025-10-26', '16:30:00', '19:45:00', 340, 'Scheduled'),
(4, 5, '2025-10-27', '16:30:00', '19:45:00', 350, 'Scheduled'),
-- Chittagong to Sylhet (Upakul Express)
(9, 6, '2025-10-26', '08:00:00', '16:30:00', 330, 'Scheduled'),
-- Rajshahi to Rangpur (Rangpur Express)
(7, 7, '2025-10-26', '10:15:00', '14:00:00', 365, 'Scheduled'),
-- Dhaka to Comilla (Turna Nishitha)
(2, 8, '2025-10-25', '20:00:00', '22:30:00', 150, 'Running'),
(2, 8, '2025-10-26', '20:00:00', '22:30:00', 380, 'Scheduled');

-- Insert Passengers (Bangladesh names and phone numbers)
INSERT INTO passengers (passenger_name, phone, email) VALUES
('Md. Kamal Hossain', '01711223344', 'kamal.hossain@gmail.com'),
('Ayesha Siddika', '01812345678', 'ayesha.siddika@yahoo.com'),
('Rafiqul Islam', '01923456789', 'rafiq.islam@outlook.com'),
('Fatima Begum', '01534567890', 'fatima.begum@gmail.com'),
('Abdul Rahman', '01645678901', 'abdul.rahman@hotmail.com'),
('Nasrin Akter', '01756789012', 'nasrin.akter@gmail.com'),
('Shahidul Islam', '01867890123', 'shahid.islam@yahoo.com'),
('Rupa Khatun', '01978901234', 'rupa.khatun@gmail.com'),
('Mizanur Rahman', '01312345678', 'mizan.rahman@outlook.com'),
('Sultana Razia', '01423456789', 'sultana.razia@gmail.com'),
('Habibur Rahman', '01534567891', 'habib.rahman@gmail.com'),
('Jannatul Ferdous', '01645678902', 'jannat.ferdous@yahoo.com'),
('Rahim Uddin', '01756789013', 'rahim.uddin@gmail.com'),
('Shirin Akter', '01867890124', 'shirin.akter@hotmail.com'),
('Faruk Ahmed', '01978901235', 'faruk.ahmed@gmail.com');

-- Insert Bookings (Realistic Bangladesh Railway bookings)
INSERT INTO bookings (trip_id, passenger_id, seats_booked, total_fare, status) VALUES
-- Suborna Express bookings (Dhaka-Chittagong)
(1, 1, 2, 900.00, 'Confirmed'),
(1, 5, 1, 450.00, 'Confirmed'),
(1, 8, 3, 1350.00, 'Confirmed'),
(2, 12, 2, 900.00, 'Confirmed'),
-- Padma Express bookings (Dhaka-Rajshahi)
(3, 2, 1, 380.00, 'Confirmed'),
(3, 6, 2, 760.00, 'Confirmed'),
(4, 13, 1, 380.00, 'Confirmed'),
-- Parabat Express bookings (Dhaka-Sylhet)
(5, 3, 2, 640.00, 'Confirmed'),
(5, 9, 1, 320.00, 'Confirmed'),
(6, 14, 4, 1280.00, 'Confirmed'),
-- Silk City Express bookings (Dhaka-Khulna)
(7, 4, 1, 420.00, 'Confirmed'),
(7, 10, 2, 840.00, 'Confirmed'),
-- Mohanagar Godhuli bookings (Dhaka-Mymensingh)
(8, 7, 3, 540.00, 'Confirmed'),
(8, 15, 1, 180.00, 'Confirmed'),
(9, 11, 2, 360.00, 'Confirmed'),
-- Upakul Express bookings (Chittagong-Sylhet)
(10, 1, 2, 1040.00, 'Confirmed'),
-- Rangpur Express bookings
(11, 3, 1, 220.00, 'Confirmed'),
-- Running trip (Turna Nishitha - Dhaka-Comilla)
(12, 7, 1, 150.00, 'Completed'),
(12, 9, 2, 300.00, 'Completed');

-- Update available seats in trips after bookings
UPDATE trips SET available_seats = available_seats - 6 WHERE trip_id = 1;  -- Suborna Express trip 1
UPDATE trips SET available_seats = available_seats - 2 WHERE trip_id = 2;  -- Suborna Express trip 2
UPDATE trips SET available_seats = available_seats - 3 WHERE trip_id = 3;  -- Padma Express trip 1
UPDATE trips SET available_seats = available_seats - 1 WHERE trip_id = 4;  -- Padma Express trip 2
UPDATE trips SET available_seats = available_seats - 3 WHERE trip_id = 5;  -- Parabat Express trip 1
UPDATE trips SET available_seats = available_seats - 4 WHERE trip_id = 6;  -- Parabat Express trip 2
UPDATE trips SET available_seats = available_seats - 3 WHERE trip_id = 7;  -- Silk City Express
UPDATE trips SET available_seats = available_seats - 4 WHERE trip_id = 8;  -- Mohanagar Godhuli trip 1
UPDATE trips SET available_seats = available_seats - 2 WHERE trip_id = 9;  -- Mohanagar Godhuli trip 2
UPDATE trips SET available_seats = available_seats - 2 WHERE trip_id = 10; -- Upakul Express
UPDATE trips SET available_seats = available_seats - 1 WHERE trip_id = 11; -- Rangpur Express
UPDATE trips SET available_seats = available_seats - 3 WHERE trip_id = 12; -- Turna Nishitha (Running)

SELECT 'Bangladesh Railway sample data inserted successfully!' as Status;
