<?php
require_once '../config/db.php';
$pageTitle = 'Dashboard';
?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/sidebar.php'; ?>

<div class="main-content">
    <div class="page-header">
        <h1>📊 Dashboard - Railway System Overview</h1>
    </div>

    <?php
    // Query 1: Basic COUNT queries for all tables
    $queries = [];
    
    // Total Stations
    $sql_stations = "SELECT COUNT(*) as total FROM stations";
    $result = $conn->query($sql_stations);
    $total_stations = $result->fetch_assoc()['total'];
    $queries['Stations Count'] = $sql_stations;
    
    // Total Trains
    $sql_trains = "SELECT COUNT(*) as total FROM trains";
    $result = $conn->query($sql_trains);
    $total_trains = $result->fetch_assoc()['total'];
    $queries['Trains Count'] = $sql_trains;
    
    // Total Routes
    $sql_routes = "SELECT COUNT(*) as total FROM routes";
    $result = $conn->query($sql_routes);
    $total_routes = $result->fetch_assoc()['total'];
    $queries['Routes Count'] = $sql_routes;
    
    // Total Trips
    $sql_trips = "SELECT COUNT(*) as total FROM trips";
    $result = $conn->query($sql_trips);
    $total_trips = $result->fetch_assoc()['total'];
    $queries['Trips Count'] = $sql_trips;
    
    // Total Passengers
    $sql_passengers = "SELECT COUNT(*) as total FROM passengers";
    $result = $conn->query($sql_passengers);
    $total_passengers = $result->fetch_assoc()['total'];
    $queries['Passengers Count'] = $sql_passengers;
    
    // Total Bookings
    $sql_bookings = "SELECT COUNT(*) as total FROM bookings";
    $result = $conn->query($sql_bookings);
    $total_bookings = $result->fetch_assoc()['total'];
    $queries['Bookings Count'] = $sql_bookings;
    
    // Revenue - SUM aggregate
    $sql_revenue = "SELECT SUM(total_fare) as revenue FROM bookings WHERE status = 'Confirmed'";
    $result = $conn->query($sql_revenue);
    $total_revenue = $result->fetch_assoc()['revenue'] ?? 0;
    $queries['Total Revenue'] = $sql_revenue;
    
    // Average fare
    $sql_avg_fare = "SELECT AVG(total_fare) as avg_fare FROM bookings";
    $result = $conn->query($sql_avg_fare);
    $avg_fare = $result->fetch_assoc()['avg_fare'] ?? 0;
    $queries['Average Fare'] = $sql_avg_fare;
    ?>

    <!-- Stats Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <h3>Total Stations</h3>
            <div class="stat-value"><?php echo $total_stations; ?></div>
        </div>
        <div class="stat-card">
            <h3>Total Trains</h3>
            <div class="stat-value"><?php echo $total_trains; ?></div>
        </div>
        <div class="stat-card">
            <h3>Total Routes</h3>
            <div class="stat-value"><?php echo $total_routes; ?></div>
        </div>
        <div class="stat-card">
            <h3>Total Trips</h3>
            <div class="stat-value"><?php echo $total_trips; ?></div>
        </div>
        <div class="stat-card">
            <h3>Total Passengers</h3>
            <div class="stat-value"><?php echo $total_passengers; ?></div>
        </div>
        <div class="stat-card">
            <h3>Total Bookings</h3>
            <div class="stat-value"><?php echo $total_bookings; ?></div>
        </div>
        <div class="stat-card">
            <h3>Total Revenue</h3>
            <div class="stat-value">৳<?php echo number_format($total_revenue, 2); ?></div>
        </div>
        <div class="stat-card">
            <h3>Average Fare</h3>
            <div class="stat-value">৳<?php echo number_format($avg_fare, 2); ?></div>
        </div>
    </div>

    <?php
    // Query 2: GROUP BY with COUNT - Bookings by Status
    $sql_bookings_status = "SELECT status, COUNT(*) as count FROM bookings GROUP BY status ORDER BY count DESC";
    $result_bookings_status = $conn->query($sql_bookings_status);
    ?>

    <div class="card">
        <h2>🎫 Bookings by Status</h2>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Status</th>
                        <th>Count</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $result_bookings_status->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['status']; ?></td>
                        <td><?php echo $row['count']; ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php
    // Query 3: GROUP BY with COUNT - Trains by Type
    $sql_trains_type = "SELECT train_type, COUNT(*) as count FROM trains GROUP BY train_type ORDER BY count DESC";
    $result_trains_type = $conn->query($sql_trains_type);
    ?>

    <div class="card">
        <h2>🚂 Trains by Type</h2>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Train Type</th>
                        <th>Count</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $result_trains_type->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['train_type']; ?></td>
                        <td><?php echo $row['count']; ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php
    // Query 4: GROUP BY with SUM - Revenue by Trip (with LIMIT)
    $sql_revenue_trip = "SELECT trip_id, SUM(total_fare) as revenue, COUNT(*) as bookings 
                         FROM bookings 
                         WHERE status = 'Confirmed' 
                         GROUP BY trip_id 
                         ORDER BY revenue DESC 
                         LIMIT 5";
    $result_revenue_trip = $conn->query($sql_revenue_trip);
    ?>

    <div class="card">
        <h2>💰 Top 5 Revenue Generating Trips</h2>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Trip ID</th>
                        <th>Total Bookings</th>
                        <th>Total Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $result_revenue_trip->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['trip_id']; ?></td>
                        <td><?php echo $row['bookings']; ?></td>
                        <td>৳<?php echo number_format($row['revenue'], 2); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php
    // Query 5: GROUP BY with HAVING - Passengers with more than 1 booking
    $sql_active_passengers = "SELECT passenger_id, COUNT(*) as booking_count, SUM(total_fare) as total_spent 
                              FROM bookings 
                              GROUP BY passenger_id 
                              HAVING booking_count > 1 
                              ORDER BY booking_count DESC 
                              LIMIT 10";
    $result_active_passengers = $conn->query($sql_active_passengers);
    ?>

    <div class="card">
        <h2>👥 Active Passengers</h2>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Passenger ID</th>
                        <th>Total Bookings</th>
                        <th>Total Spent</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if($result_active_passengers->num_rows > 0):
                        while($row = $result_active_passengers->fetch_assoc()): 
                    ?>
                    <tr>
                        <td><?php echo $row['passenger_id']; ?></td>
                        <td><?php echo $row['booking_count']; ?></td>
                        <td>৳<?php echo number_format($row['total_spent'], 2); ?></td>
                    </tr>
                    <?php 
                        endwhile;
                    else:
                    ?>
                    <tr>
                        <td colspan="3" style="text-align: center;">No passengers with multiple bookings yet</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php include '../includes/footer.php'; ?>
