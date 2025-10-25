<?php
require_once '../config/db.php';
$pageTitle = 'Bookings';

$message = '';
$generatedQuery = '';

// Get trips and passengers for dropdowns
$trips_query = "SELECT t.trip_id, tr.train_name, s1.station_name as from_name, s2.station_name as to_name, t.trip_date 
                FROM trips t 
                INNER JOIN trains tr ON t.train_id = tr.train_id 
                INNER JOIN routes r ON t.route_id = r.route_id 
                INNER JOIN stations s1 ON r.from_station_id = s1.station_id 
                INNER JOIN stations s2 ON r.to_station_id = s2.station_id 
                WHERE t.status = 'Scheduled' OR t.status = 'Running' 
                ORDER BY t.trip_date DESC LIMIT 50";
$trips_result = $conn->query($trips_query);
$trips = [];
while($row = $trips_result->fetch_assoc()) {
    $trips[] = $row;
}

$passengers_query = "SELECT passenger_id, passenger_name, phone FROM passengers ORDER BY passenger_name LIMIT 100";
$passengers_result = $conn->query($passengers_query);
$passengers = [];
while($row = $passengers_result->fetch_assoc()) {
    $passengers[] = $row;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action == 'generate') {
        $operation = $_POST['operation'] ?? '';
        
        if ($operation == 'create') {
            $trip_id = (int)$_POST['trip_id'];
            $passenger_id = (int)$_POST['passenger_id'];
            $seats_booked = (int)$_POST['seats_booked'];
            $total_fare = (float)$_POST['total_fare'];
            $status = escapeString($conn, $_POST['status']);
            $generatedQuery = "INSERT INTO bookings (trip_id, passenger_id, seats_booked, total_fare, status) VALUES ($trip_id, $passenger_id, $seats_booked, $total_fare, '$status')";
        }
        elseif ($operation == 'update') {
            $booking_id = (int)$_POST['booking_id'];
            $seats_booked = (int)$_POST['seats_booked'];
            $total_fare = (float)$_POST['total_fare'];
            $status = escapeString($conn, $_POST['status']);
            $generatedQuery = "UPDATE bookings SET seats_booked=$seats_booked, total_fare=$total_fare, status='$status' WHERE booking_id=$booking_id";
        }
        elseif ($operation == 'delete') {
            $booking_id = (int)$_POST['booking_id'];
            $generatedQuery = "DELETE FROM bookings WHERE booking_id=$booking_id";
        }
        elseif ($operation == 'search') {
            $status = escapeString($conn, $_POST['search_status']);
            $date_from = escapeString($conn, $_POST['date_from']);
            $date_to = escapeString($conn, $_POST['date_to']);
            $min_fare = (float)($_POST['min_fare'] ?? 0);
            $max_fare = (float)($_POST['max_fare'] ?? 999999);
            $order_by = $_POST['order_by'] ?? 'booking_date';
            $order_dir = $_POST['order_dir'] ?? 'DESC';
            $limit = (int)($_POST['limit'] ?? 20);
            
            $where_clauses = ["total_fare >= $min_fare", "total_fare <= $max_fare"];
            if (!empty($status)) {
                $where_clauses[] = "status = '$status'";
            }
            if (!empty($date_from)) {
                $where_clauses[] = "booking_date >= '$date_from'";
            }
            if (!empty($date_to)) {
                $where_clauses[] = "booking_date <= '$date_to'";
            }
            
            $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
            $generatedQuery = "SELECT * FROM bookings $where_sql ORDER BY $order_by $order_dir LIMIT $limit";
        }
    }
    elseif ($action == 'execute' && !empty($_POST['query'])) {
        $generatedQuery = $_POST['query'];
        $result = executeQuery($conn, $generatedQuery);
        
        if ($result['success']) {
            $message = '<div class="alert alert-success">✓ Query executed successfully!</div>';
        } else {
            $message = '<div class="alert alert-error">✗ Error: ' . $result['error'] . '</div>';
        }
    }
}

$sql_view = "SELECT b.*, p.passenger_name, p.phone, tr.train_name, s1.station_name as from_name, s2.station_name as to_name, t.trip_date 
             FROM bookings b 
             INNER JOIN passengers p ON b.passenger_id = p.passenger_id 
             INNER JOIN trips t ON b.trip_id = t.trip_id 
             INNER JOIN trains tr ON t.train_id = tr.train_id 
             INNER JOIN routes r ON t.route_id = r.route_id 
             INNER JOIN stations s1 ON r.from_station_id = s1.station_id 
             INNER JOIN stations s2 ON r.to_station_id = s2.station_id 
             ORDER BY b.booking_date DESC 
             LIMIT 30";
$bookings_result = $conn->query($sql_view);
?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/sidebar.php'; ?>

<div class="main-content">
    <div class="page-header">
        <h1>🎫 Bookings Management</h1>
    </div>

    <?php echo $message; ?>

    <div class="card">
        <h2>➕ Add New Booking</h2>
        <form method="POST" action="">
            <input type="hidden" name="operation" value="create">
            <div class="form-row">
                <div class="form-group">
                    <label>Trip *</label>
                    <select name="trip_id" required>
                        <option value="">Select Trip</option>
                        <?php foreach($trips as $trip): ?>
                        <option value="<?php echo $trip['trip_id']; ?>">
                            <?php echo $trip['train_name'] . ' | ' . $trip['from_name'] . ' → ' . $trip['to_name'] . ' | ' . date('d M Y', strtotime($trip['trip_date'])); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Passenger *</label>
                    <select name="passenger_id" required>
                        <option value="">Select Passenger</option>
                        <?php foreach($passengers as $passenger): ?>
                        <option value="<?php echo $passenger['passenger_id']; ?>">
                            <?php echo $passenger['passenger_name'] . ' (' . $passenger['phone'] . ')'; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Seats Booked *</label>
                    <input type="number" name="seats_booked" value="1" min="1" max="10" required placeholder="e.g., 2">
                    <small style="color: #666; font-size: 12px;">💡 Max 10 seats per booking</small>
                </div>
                <div class="form-group">
                    <label>Total Fare (৳) *</label>
                    <input type="number" name="total_fare" step="0.01" min="0.01" required placeholder="e.g., 450.00">
                </div>
                <div class="form-group">
                    <label>Status *</label>
                    <select name="status" required>
                        <option value="Confirmed">Confirmed</option>
                        <option value="Cancelled">Cancelled</option>
                        <option value="Completed">Completed</option>
                    </select>
                </div>
            </div>
            
            <?php if ($generatedQuery && $_POST['operation'] == 'create'): ?>
            <div class="query-section">
                <h3>📝 Generated SQL Query:</h3>
                <textarea class="query-box" name="query" readonly><?php echo $generatedQuery; ?></textarea>
            </div>
            <div class="btn-group">
                <button type="submit" name="action" value="execute" class="btn btn-success">✓ Execute Query</button>
                <button type="submit" name="action" value="generate" class="btn btn-secondary">🔄 Regenerate</button>
            </div>
            <?php else: ?>
            <div class="btn-group">
                <button type="submit" name="action" value="generate" class="btn btn-primary">Generate INSERT Query</button>
            </div>
            <?php endif; ?>
        </form>
    </div>

    <div class="card">
        <h2>🔍 Search Bookings (Complex WHERE Clauses)</h2>
        <form method="POST" action="">
            <input type="hidden" name="operation" value="search">
            <div class="form-row">
                <div class="form-group">
                    <label>Status</label>
                    <select name="search_status">
                        <option value="">All Status</option>
                        <option value="Confirmed">Confirmed</option>
                        <option value="Cancelled">Cancelled</option>
                        <option value="Completed">Completed</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Booking Date From</label>
                    <input type="date" name="date_from">
                </div>
                <div class="form-group">
                    <label>Booking Date To</label>
                    <input type="date" name="date_to">
                </div>
                <div class="form-group">
                    <label>Min Fare (₹)</label>
                    <input type="number" name="min_fare" value="0" step="0.01">
                </div>
                <div class="form-group">
                    <label>Max Fare (₹)</label>
                    <input type="number" name="max_fare" value="10000" step="0.01">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Order By</label>
                    <select name="order_by">
                        <option value="booking_date">Booking Date</option>
                        <option value="total_fare">Total Fare</option>
                        <option value="seats_booked">Seats Booked</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Order Direction</label>
                    <select name="order_dir">
                        <option value="ASC">Ascending</option>
                        <option value="DESC">Descending</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Limit</label>
                    <input type="number" name="limit" value="20" min="1" max="100">
                </div>
            </div>
            
            <?php if ($generatedQuery && $_POST['operation'] == 'search'): ?>
            <div class="query-section">
                <h3>📝 Generated SQL Query:</h3>
                <textarea class="query-box" name="query" readonly><?php echo $generatedQuery; ?></textarea>
            </div>
            <div class="btn-group">
                <button type="submit" name="action" value="execute" class="btn btn-success">✓ Execute Search</button>
                <button type="submit" name="action" value="generate" class="btn btn-secondary">🔄 Regenerate</button>
            </div>
            <?php else: ?>
            <div class="btn-group">
                <button type="submit" name="action" value="generate" class="btn btn-primary">Generate SELECT Query</button>
            </div>
            <?php endif; ?>
        </form>
    </div>

    <div class="card">
        <h2>📋 All Bookings (6-Table INNER JOIN)</h2>
        <div class="query-section">
            <h3>📝 Query Used (Joining bookings, passengers, trips, trains, routes, and stations):</h3>
            <textarea class="query-box" readonly><?php echo $sql_view; ?></textarea>
        </div>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Booking ID</th>
                        <th>Passenger</th>
                        <th>Phone</th>
                        <th>Train</th>
                        <th>Route</th>
                        <th>Trip Date</th>
                        <th>Seats</th>
                        <th>Fare (₹)</th>
                        <th>Status</th>
                        <th>Booked On</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($bookings_result->num_rows > 0): ?>
                        <?php while($row = $bookings_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['booking_id']; ?></td>
                            <td><?php echo $row['passenger_name']; ?></td>
                            <td><?php echo $row['phone']; ?></td>
                            <td><?php echo $row['train_name']; ?></td>
                            <td><?php echo $row['from_name'] . ' → ' . $row['to_name']; ?></td>
                            <td><?php echo date('d M Y', strtotime($row['trip_date'])); ?></td>
                            <td><?php echo $row['seats_booked']; ?></td>
                            <td><?php echo number_format($row['total_fare'], 2); ?></td>
                            <td><?php echo $row['status']; ?></td>
                            <td><?php echo date('d M Y H:i', strtotime($row['booking_date'])); ?></td>
                            <td class="action-buttons">
                                <button onclick="editBooking(<?php echo htmlspecialchars(json_encode($row)); ?>)" class="btn btn-primary btn-small">Edit</button>
                                <button onclick="deleteBooking(<?php echo $row['booking_id']; ?>)" class="btn btn-danger btn-small">Delete</button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="11" style="text-align: center;">No bookings found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<script>
function editBooking(booking) {
    alert('Edit functionality: Generate UPDATE query for booking ID ' + booking.booking_id);
}

function deleteBooking(id) {
    if(confirm('Generate DELETE query for booking ID ' + id + '?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="operation" value="delete">
            <input type="hidden" name="booking_id" value="${id}">
            <input type="hidden" name="action" value="generate">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php include '../includes/footer.php'; ?>
