# Railway Admin System - CRUD & Query Demonstration

A PHP-based railway management system built to demonstrate **CRUD operations** and **SQL query knowledge** including SELECT clauses, filtering, aggregations, and various types of JOINs.

## 🎯 Project Goal

This project demonstrates:
- ✅ **Basic CRUD Operations** (Create, Read, Update, Delete)
- ✅ **SELECT Operations** with WHERE, ORDER BY, LIMIT, LIKE
- ✅ **Aggregate Functions** (COUNT, SUM, AVG)
- ✅ **GROUP BY** with HAVING
- ✅ **JOIN Operations** (INNER JOIN, LEFT JOIN, RIGHT JOIN)
- ✅ **Multi-table JOINs** (up to 6 tables)
- ✅ **Query Preview** - All SQL queries shown before execution

## 📁 Project Structure

```
Railway Website 3-1/
├── config/
│   └── db.php                 # Database connection
├── database/
│   ├── schema.sql             # Database schema (6 tables)
│   └── sample_data.sql        # Sample data for testing
├── includes/
│   ├── header.php             # Common header
│   ├── sidebar.php            # Navigation sidebar
│   └── footer.php             # Common footer
├── pages/
│   ├── dashboard.php          # Dashboard with stats (COUNT, SUM, AVG, GROUP BY)
│   ├── stations.php           # Stations CRUD
│   ├── trains.php             # Trains CRUD
│   ├── routes.php             # Routes CRUD
│   ├── trips.php              # Trips CRUD
│   ├── passengers.php         # Passengers CRUD
│   ├── bookings.php           # Bookings CRUD
│   └── reports.php            # Advanced JOIN queries
├── css/
│   └── style.css              # Elegant minimal CSS
└── index.php                  # Entry point
```

## 🗄️ Database Schema

**6 Tables:**
1. **stations** - Railway stations
2. **trains** - Train information
3. **routes** - Routes between stations
4. **trips** - Scheduled trips
5. **passengers** - Passenger information
6. **bookings** - Booking records

## 🚀 Setup Instructions

### Prerequisites
- XAMPP (Apache + MySQL + PHP)
- Web browser

### Installation Steps

1. **Start XAMPP**
   - Open XAMPP Control Panel
   - Start Apache and MySQL

2. **Create Database**
   - Open phpMyAdmin: `http://localhost/phpmyadmin`
   - Run the SQL file: `database/schema.sql`
   - This creates the `railway_db` database with all tables

3. **Load Sample Data** (Optional)
   - Run the SQL file: `database/sample_data.sql`
   - This populates tables with test data

4. **Access the Application**
   - Open browser and navigate to:
   ```
   http://localhost/Railway Website 3-1/
   ```
   - You'll be redirected to the dashboard

## 📊 Features & Query Demonstrations

### Dashboard
- **COUNT queries** for all tables
- **SUM** for total revenue
- **AVG** for average fare
- **GROUP BY** with bookings by status
- **GROUP BY** with trains by type
- **GROUP BY with HAVING** for active passengers

### CRUD Pages (All 6 Tables)
Each page demonstrates:
- **INSERT** - Generate query before adding
- **UPDATE** - Generate query before updating
- **DELETE** - Generate query before deleting
- **SELECT with WHERE** - Filter and search
- **ORDER BY** - Sort results
- **LIMIT** - Pagination
- **LIKE** - Pattern matching

### Advanced Queries Page
Demonstrates different JOIN types:

**INNER JOIN:**
- 3-table join (bookings, passengers, trips)
- 4-table join (trips, trains, routes, stations)
- 6-table join (complete booking details)

**LEFT JOIN:**
- All passengers with booking count (including those with 0 bookings)
- All trains with trip count
- All trips with booking details (including empty trips)

**RIGHT JOIN:**
- Routes with station details

**Complex JOINs:**
- Passenger travel history (6-table join)
- Revenue analysis by route (with GROUP BY, HAVING, aggregations)

## 🎨 UI Features

- **Clean, Minimal Design** - Simple and elegant CSS
- **Sidebar Navigation** - Easy access to all sections
- **Query Preview Box** - Every operation shows SQL query BEFORE execution
- **Two-Step Process:**
  1. Fill form → Click "Generate Query"
  2. Review query → Click "Execute Query"
- **Responsive Tables** - Display query results clearly
- **Color-coded Stats** - Visual dashboard statistics

## 🔍 Key SQL Concepts Demonstrated

### Basic CRUD
```sql
-- CREATE
INSERT INTO stations (station_name, city, station_code) VALUES (...)

-- READ
SELECT * FROM stations WHERE city LIKE '%Delhi%' ORDER BY station_name LIMIT 10

-- UPDATE
UPDATE trains SET train_name='...', train_type='...' WHERE train_id=1

-- DELETE
DELETE FROM passengers WHERE passenger_id=5
```

### SELECT Clauses
```sql
-- WHERE with multiple conditions
SELECT * FROM trips WHERE status='Scheduled' AND trip_date >= '2025-10-26'

-- ORDER BY
SELECT * FROM trains ORDER BY train_name ASC

-- LIMIT
SELECT * FROM bookings ORDER BY booking_date DESC LIMIT 20

-- LIKE for pattern matching
SELECT * FROM passengers WHERE passenger_name LIKE '%Kumar%'
```

### Aggregate Functions
```sql
-- COUNT
SELECT COUNT(*) as total FROM stations

-- SUM
SELECT SUM(total_fare) as revenue FROM bookings WHERE status='Confirmed'

-- AVG
SELECT AVG(total_fare) as avg_fare FROM bookings

-- GROUP BY
SELECT status, COUNT(*) as count FROM bookings GROUP BY status

-- GROUP BY with HAVING
SELECT passenger_id, COUNT(*) as bookings 
FROM bookings 
GROUP BY passenger_id 
HAVING bookings > 1
```

### JOIN Operations
```sql
-- INNER JOIN (2 tables)
SELECT r.*, s1.station_name as from_name, s2.station_name as to_name
FROM routes r
INNER JOIN stations s1 ON r.from_station_id = s1.station_id
INNER JOIN stations s2 ON r.to_station_id = s2.station_id

-- LEFT JOIN
SELECT p.*, COUNT(b.booking_id) as total_bookings
FROM passengers p
LEFT JOIN bookings b ON p.passenger_id = b.passenger_id
GROUP BY p.passenger_id

-- 6-Table JOIN
SELECT b.*, p.passenger_name, tr.train_name, s1.station_name, s2.station_name
FROM bookings b
INNER JOIN passengers p ON b.passenger_id = p.passenger_id
INNER JOIN trips t ON b.trip_id = t.trip_id
INNER JOIN trains tr ON t.train_id = tr.train_id
INNER JOIN routes r ON t.route_id = r.route_id
INNER JOIN stations s1 ON r.from_station_id = s1.station_id
INNER JOIN stations s2 ON r.to_station_id = s2.station_id
```

## 🎓 For Academic Evaluation

This project demonstrates:

✅ **Basic CRUD** - All operations with query preview  
✅ **SELECT with clauses** - WHERE, ORDER BY, LIMIT, LIKE  
✅ **Aggregate functions** - COUNT, SUM, AVG  
✅ **GROUP BY & HAVING** - Data grouping and filtering  
✅ **JOIN operations** - INNER, LEFT, RIGHT  
✅ **Multi-table JOINs** - Up to 6 tables joined  
✅ **Query visibility** - Every SQL query shown in UI  

## 📝 Notes

- No authentication required (for demonstration purposes)
- Minimal UI to focus on query demonstration
- All queries shown in readonly textboxes before execution
- Sample data provided for immediate testing

## 🛠️ Technology Stack

- **Backend:** PHP 7.4+
- **Database:** MySQL/MariaDB
- **Frontend:** HTML5, CSS3, JavaScript (Vanilla)
- **Server:** Apache (XAMPP)

## 📞 Support

For issues or questions about this project, please check:
1. XAMPP is running (Apache + MySQL)
2. Database `railway_db` exists
3. Sample data is loaded
4. File paths match your XAMPP htdocs location

---

**Author:** Railway Admin System  
**Purpose:** Academic demonstration of CRUD operations and SQL query knowledge  
**Date:** October 2025
