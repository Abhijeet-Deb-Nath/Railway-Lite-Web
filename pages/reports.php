<?php
require_once '../config/db.php';
$pageTitle = 'Advanced Queries - JOIN Demonstrations';

$selectedQuery = $_GET['query'] ?? 'inner_join_1';

// Define all JOIN queries
$queries = [
    'inner_join_1' => [
        'title' => 'INNER JOIN: Bookings with Passenger & Trip Details',
        'description' => 'Shows all bookings with matching passenger and trip information (only records with matches in all tables)',
        'sql' => "SELECT b.booking_id, b.booking_date, b.seats_booked, b.total_fare, b.status as booking_status,
                  p.passenger_name, p.phone, p.email,
                  t.trip_date, t.departure_time, t.arrival_time, t.status as trip_status
                  FROM bookings b
                  INNER JOIN passengers p ON b.passenger_id = p.passenger_id
                  INNER JOIN trips t ON b.trip_id = t.trip_id
                  ORDER BY b.booking_date DESC
                  LIMIT 20",
        'type' => 'INNER JOIN'
    ],
    'inner_join_2' => [
        'title' => 'INNER JOIN: Complete Trip Information (4 Tables)',
        'description' => 'Joins trips with trains, routes, and stations to show complete trip details',
        'sql' => "SELECT t.trip_id, t.trip_date, t.departure_time, t.arrival_time, t.available_seats, t.status,
                  tr.train_name, tr.train_type,
                  s1.station_name as from_station, s1.city as from_city,
                  s2.station_name as to_station, s2.city as to_city,
                  r.distance_km, r.base_fare
                  FROM trips t
                  INNER JOIN trains tr ON t.train_id = tr.train_id
                  INNER JOIN routes r ON t.route_id = r.route_id
                  INNER JOIN stations s1 ON r.from_station_id = s1.station_id
                  INNER JOIN stations s2 ON r.to_station_id = s2.station_id
                  WHERE t.trip_date >= CURDATE()
                  ORDER BY t.trip_date, t.departure_time
                  LIMIT 20",
        'type' => 'INNER JOIN'
    ],
    'inner_join_3' => [
        'title' => 'INNER JOIN: Full Booking Details (6 Tables)',
        'description' => 'Complete booking information joining all 6 tables',
        'sql' => "SELECT b.booking_id, b.booking_date, b.seats_booked, b.total_fare, b.status as booking_status,
                  p.passenger_name, p.phone,
                  tr.train_name, tr.train_type,
                  t.trip_date, t.departure_time,
                  s1.station_name as from_station,
                  s2.station_name as to_station,
                  r.distance_km
                  FROM bookings b
                  INNER JOIN passengers p ON b.passenger_id = p.passenger_id
                  INNER JOIN trips t ON b.trip_id = t.trip_id
                  INNER JOIN trains tr ON t.train_id = tr.train_id
                  INNER JOIN routes r ON t.route_id = r.route_id
                  INNER JOIN stations s1 ON r.from_station_id = s1.station_id
                  INNER JOIN stations s2 ON r.to_station_id = s2.station_id
                  WHERE b.status = 'Confirmed'
                  ORDER BY b.booking_date DESC
                  LIMIT 20",
        'type' => 'INNER JOIN'
    ],
    'left_join_1' => [
        'title' => 'LEFT JOIN: All Passengers with Their Bookings',
        'description' => 'Shows all passengers, including those who haven\'t made any bookings yet',
        'sql' => "SELECT p.passenger_id, p.passenger_name, p.phone, p.email, p.created_at,
                  COUNT(b.booking_id) as total_bookings,
                  SUM(b.total_fare) as total_spent
                  FROM passengers p
                  LEFT JOIN bookings b ON p.passenger_id = b.passenger_id
                  GROUP BY p.passenger_id, p.passenger_name, p.phone, p.email, p.created_at
                  ORDER BY total_bookings DESC, p.passenger_name
                  LIMIT 30",
        'type' => 'LEFT JOIN'
    ],
    'left_join_2' => [
        'title' => 'LEFT JOIN: All Trains with Trip Count',
        'description' => 'Shows all trains and count of trips scheduled for each (including trains with no trips)',
        'sql' => "SELECT tr.train_id, tr.train_name, tr.train_type, tr.total_seats,
                  COUNT(t.trip_id) as total_trips,
                  SUM(CASE WHEN t.status = 'Scheduled' THEN 1 ELSE 0 END) as scheduled_trips,
                  SUM(CASE WHEN t.status = 'Completed' THEN 1 ELSE 0 END) as completed_trips
                  FROM trains tr
                  LEFT JOIN trips t ON tr.train_id = t.train_id
                  GROUP BY tr.train_id, tr.train_name, tr.train_type, tr.total_seats
                  ORDER BY total_trips DESC",
        'type' => 'LEFT JOIN'
    ],
    'left_join_3' => [
        'title' => 'LEFT JOIN: All Trips with Booking Details',
        'description' => 'Shows all trips including those with no bookings (to identify empty trips)',
        'sql' => "SELECT t.trip_id, t.trip_date, t.departure_time, t.status as trip_status,
                  tr.train_name,
                  s1.station_name as from_station,
                  s2.station_name as to_station,
                  t.available_seats,
                  COUNT(b.booking_id) as bookings_count,
                  SUM(b.seats_booked) as seats_sold,
                  SUM(b.total_fare) as revenue
                  FROM trips t
                  INNER JOIN trains tr ON t.train_id = tr.train_id
                  INNER JOIN routes r ON t.route_id = r.route_id
                  INNER JOIN stations s1 ON r.from_station_id = s1.station_id
                  INNER JOIN stations s2 ON r.to_station_id = s2.station_id
                  LEFT JOIN bookings b ON t.trip_id = b.trip_id AND b.status = 'Confirmed'
                  GROUP BY t.trip_id, t.trip_date, t.departure_time, t.status, tr.train_name, 
                           s1.station_name, s2.station_name, t.available_seats
                  ORDER BY t.trip_date DESC
                  LIMIT 25",
        'type' => 'LEFT JOIN'
    ],
    'right_join_1' => [
        'title' => 'RIGHT JOIN: All Routes with Station Details',
        'description' => 'Uses RIGHT JOIN to show routes (demonstrates RIGHT JOIN concept)',
        'sql' => "SELECT r.route_id, r.distance_km, r.base_fare,
                  s1.station_name as from_station, s1.city as from_city, s1.station_code as from_code,
                  s2.station_name as to_station, s2.city as to_city, s2.station_code as to_code
                  FROM stations s1
                  RIGHT JOIN routes r ON s1.station_id = r.from_station_id
                  INNER JOIN stations s2 ON r.to_station_id = s2.station_id
                  ORDER BY r.distance_km DESC",
        'type' => 'RIGHT JOIN'
    ],
    'complex_join_1' => [
        'title' => 'Complex JOIN: Passenger Travel History',
        'description' => 'Shows complete travel history with passenger, booking, trip, train, and route details',
        'sql' => "SELECT p.passenger_name, p.phone,
                  b.booking_id, b.booking_date, b.seats_booked, b.total_fare, b.status as booking_status,
                  tr.train_name, tr.train_type,
                  t.trip_date, t.departure_time, t.arrival_time,
                  CONCAT(s1.station_name, ' (', s1.city, ')') as journey_from,
                  CONCAT(s2.station_name, ' (', s2.city, ')') as journey_to,
                  r.distance_km
                  FROM passengers p
                  INNER JOIN bookings b ON p.passenger_id = b.passenger_id
                  INNER JOIN trips t ON b.trip_id = t.trip_id
                  INNER JOIN trains tr ON t.train_id = tr.train_id
                  INNER JOIN routes r ON t.route_id = r.route_id
                  INNER JOIN stations s1 ON r.from_station_id = s1.station_id
                  INNER JOIN stations s2 ON r.to_station_id = s2.station_id
                  WHERE b.status IN ('Confirmed', 'Completed')
                  ORDER BY p.passenger_name, b.booking_date DESC
                  LIMIT 30",
        'type' => 'Multi-Table INNER JOIN'
    ],
    'complex_join_2' => [
        'title' => 'Complex JOIN with Aggregation: Revenue by Route',
        'description' => 'Shows revenue analysis per route with GROUP BY and multiple JOINs',
        'sql' => "SELECT CONCAT(s1.station_name, ' → ', s2.station_name) as route,
                  r.distance_km, r.base_fare,
                  COUNT(DISTINCT t.trip_id) as total_trips,
                  COUNT(b.booking_id) as total_bookings,
                  SUM(b.seats_booked) as total_seats_sold,
                  SUM(b.total_fare) as total_revenue,
                  AVG(b.total_fare) as avg_booking_fare
                  FROM routes r
                  INNER JOIN stations s1 ON r.from_station_id = s1.station_id
                  INNER JOIN stations s2 ON r.to_station_id = s2.station_id
                  LEFT JOIN trips t ON r.route_id = t.route_id
                  LEFT JOIN bookings b ON t.trip_id = b.trip_id AND b.status = 'Confirmed'
                  GROUP BY r.route_id, s1.station_name, s2.station_name, r.distance_km, r.base_fare
                  HAVING total_revenue IS NOT NULL
                  ORDER BY total_revenue DESC",
        'type' => 'Complex JOIN with GROUP BY & HAVING'
    ],
    'subquery_1' => [
        'title' => 'Subquery: Passengers Who Spent Above Average',
        'description' => 'Uses a subquery to find passengers whose total spending is above the average',
        'sql' => "SELECT p.passenger_id, p.passenger_name, p.phone,
                  SUM(b.total_fare) as total_spent,
                  COUNT(b.booking_id) as total_bookings
                  FROM passengers p
                  INNER JOIN bookings b ON p.passenger_id = b.passenger_id
                  GROUP BY p.passenger_id, p.passenger_name, p.phone
                  HAVING SUM(b.total_fare) > (SELECT AVG(total_fare) FROM bookings)
                  ORDER BY total_spent DESC",
        'type' => 'Subquery in HAVING'
    ],
    'subquery_2' => [
        'title' => 'Subquery: Trips with Above Average Bookings',
        'description' => 'Finds trips that have more bookings than the average',
        'sql' => "SELECT t.trip_id, t.trip_date, t.departure_time,
                  tr.train_name,
                  CONCAT(s1.station_name, ' → ', s2.station_name) as route,
                  COUNT(b.booking_id) as booking_count
                  FROM trips t
                  INNER JOIN trains tr ON t.train_id = tr.train_id
                  INNER JOIN routes r ON t.route_id = r.route_id
                  INNER JOIN stations s1 ON r.from_station_id = s1.station_id
                  INNER JOIN stations s2 ON r.to_station_id = s2.station_id
                  LEFT JOIN bookings b ON t.trip_id = b.trip_id
                  GROUP BY t.trip_id, t.trip_date, t.departure_time, tr.train_name, s1.station_name, s2.station_name
                  HAVING COUNT(b.booking_id) > (
                      SELECT AVG(booking_count) 
                      FROM (
                          SELECT COUNT(*) as booking_count 
                          FROM bookings 
                          GROUP BY trip_id
                      ) as avg_bookings
                  )
                  ORDER BY booking_count DESC",
        'type' => 'Nested Subquery'
    ],
    'subquery_3' => [
        'title' => 'Subquery: Stations with Most Popular Routes',
        'description' => 'Uses subquery to find stations involved in routes with high trip counts',
        'sql' => "SELECT s.station_id, s.station_name, s.city, s.station_code,
                  (SELECT COUNT(*) FROM routes r WHERE r.from_station_id = s.station_id) as routes_from,
                  (SELECT COUNT(*) FROM routes r WHERE r.to_station_id = s.station_id) as routes_to,
                  (SELECT COUNT(*) FROM routes r WHERE r.from_station_id = s.station_id OR r.to_station_id = s.station_id) as total_routes
                  FROM stations s
                  ORDER BY total_routes DESC, s.station_name",
        'type' => 'Subquery in SELECT'
    ],
    'subquery_4' => [
        'title' => 'Subquery: Trains Running Profitable Routes',
        'description' => 'Uses IN subquery to find trains operating on routes with revenue above a threshold',
        'sql' => "SELECT DISTINCT tr.train_id, tr.train_name, tr.train_type, tr.total_seats
                  FROM trains tr
                  INNER JOIN trips t ON tr.train_id = t.train_id
                  WHERE t.route_id IN (
                      SELECT r.route_id 
                      FROM routes r
                      INNER JOIN trips t2 ON r.route_id = t2.route_id
                      INNER JOIN bookings b ON t2.trip_id = b.trip_id
                      WHERE b.status = 'Confirmed'
                      GROUP BY r.route_id
                      HAVING SUM(b.total_fare) > 5000
                  )
                  ORDER BY tr.train_name",
        'type' => 'Subquery with IN'
    ]
];

$currentQuery = $queries[$selectedQuery];
$result = $conn->query($currentQuery['sql']);
?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/sidebar.php'; ?>

<div class="main-content">
    <div class="page-header">
        <h1>📈 Advanced SQL Queries</h1>
        <p>Demonstrating INNER JOIN, LEFT JOIN, RIGHT JOIN, Subqueries, GROUP BY, HAVING & Aggregate Functions</p>
    </div>

    <!-- Query Selector -->
    <div class="card">
        <h2>🔍 Select Query to Execute</h2>
        <div class="form-group">
            <label>Choose a JOIN Query:</label>
            <select onchange="window.location.href='reports.php?query=' + this.value" style="width: 100%; padding: 12px; font-size: 14px;">
                <?php foreach($queries as $key => $q): ?>
                <option value="<?php echo $key; ?>" <?php echo $selectedQuery == $key ? 'selected' : ''; ?>>
                    <?php echo $q['type'] . ': ' . $q['title']; ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <!-- Current Query Display -->
    <div class="card">
        <h2><?php echo $currentQuery['type']; ?>: <?php echo $currentQuery['title']; ?></h2>
        <p style="color: #666; margin-bottom: 20px; font-size: 15px;">
            <strong>Description:</strong> <?php echo $currentQuery['description']; ?>
        </p>
        
        <div class="query-section">
            <h3>📝 SQL Query:</h3>
            <textarea class="query-box" readonly style="min-height: 150px;"><?php echo $currentQuery['sql']; ?></textarea>
        </div>

        <div class="alert alert-info">
            <strong>JOIN Type:</strong> <?php echo $currentQuery['type']; ?>
        </div>
    </div>

    <!-- Query Results -->
    <div class="card">
        <h2>📊 Query Results</h2>
        <?php if($result && $result->num_rows > 0): ?>
            <p style="color: #10b981; font-weight: 500; margin-bottom: 15px;">
                ✓ Query executed successfully. Found <?php echo $result->num_rows; ?> record(s).
            </p>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <?php
                            // Get column names
                            $fields = $result->fetch_fields();
                            foreach($fields as $field):
                            ?>
                            <th><?php echo ucwords(str_replace('_', ' ', $field->name)); ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Reset pointer and display data
                        $result->data_seek(0);
                        while($row = $result->fetch_assoc()):
                        ?>
                        <tr>
                            <?php foreach($row as $value): ?>
                            <td><?php echo $value ?? 'NULL'; ?></td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php elseif($result): ?>
            <div class="alert alert-info">
                ℹ No records found for this query.
            </div>
        <?php else: ?>
            <div class="alert alert-error">
                ✗ Error executing query: <?php echo $conn->error; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- JOIN Types Reference -->
    <div class="card">
        <h2>📚 JOIN Types Reference</h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-top: 20px;">
            <div style="background: #f0f9ff; padding: 15px; border-radius: 5px; border-left: 4px solid #3b82f6;">
                <h3 style="color: #1e40af; margin-bottom: 10px;">INNER JOIN</h3>
                <p style="font-size: 14px; color: #555;">Returns only matching records from both tables. If no match, record is excluded.</p>
            </div>
            <div style="background: #f0fdf4; padding: 15px; border-radius: 5px; border-left: 4px solid #10b981;">
                <h3 style="color: #065f46; margin-bottom: 10px;">LEFT JOIN</h3>
                <p style="font-size: 14px; color: #555;">Returns all records from left table, and matching records from right. NULL if no match.</p>
            </div>
            <div style="background: #fef3c7; padding: 15px; border-radius: 5px; border-left: 4px solid #f59e0b;">
                <h3 style="color: #92400e; margin-bottom: 10px;">RIGHT JOIN</h3>
                <p style="font-size: 14px; color: #555;">Returns all records from right table, and matching records from left. NULL if no match.</p>
            </div>
        </div>
    </div>

</div>

<?php include '../includes/footer.php'; ?>
