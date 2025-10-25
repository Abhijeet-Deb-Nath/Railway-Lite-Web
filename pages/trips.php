<?php
require_once '../config/db.php';
$pageTitle = 'Trips';

$message = '';
$generatedQuery = '';

// Get trains and routes for dropdowns
$trains_query = "SELECT train_id, train_name FROM trains ORDER BY train_name";
$trains_result = $conn->query($trains_query);
$trains = [];
while($row = $trains_result->fetch_assoc()) {
    $trains[] = $row;
}

$routes_query = "SELECT r.route_id, s1.station_name as from_name, s2.station_name as to_name 
                 FROM routes r 
                 INNER JOIN stations s1 ON r.from_station_id = s1.station_id 
                 INNER JOIN stations s2 ON r.to_station_id = s2.station_id";
$routes_result = $conn->query($routes_query);
$routes = [];
while($row = $routes_result->fetch_assoc()) {
    $routes[] = $row;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action == 'generate') {
        $operation = $_POST['operation'] ?? '';
        
        if ($operation == 'create') {
            $train_id = (int)$_POST['train_id'];
            $route_id = (int)$_POST['route_id'];
            $trip_date = escapeString($conn, $_POST['trip_date']);
            $departure_time = escapeString($conn, $_POST['departure_time']);
            $arrival_time = escapeString($conn, $_POST['arrival_time']);
            $available_seats = (int)$_POST['available_seats'];
            $status = escapeString($conn, $_POST['status']);
            $generatedQuery = "INSERT INTO trips (train_id, route_id, trip_date, departure_time, arrival_time, available_seats, status) VALUES ($train_id, $route_id, '$trip_date', '$departure_time', '$arrival_time', $available_seats, '$status')";
        }
        elseif ($operation == 'update') {
            $trip_id = (int)$_POST['trip_id'];
            $train_id = (int)$_POST['train_id'];
            $route_id = (int)$_POST['route_id'];
            $trip_date = escapeString($conn, $_POST['trip_date']);
            $departure_time = escapeString($conn, $_POST['departure_time']);
            $arrival_time = escapeString($conn, $_POST['arrival_time']);
            $available_seats = (int)$_POST['available_seats'];
            $status = escapeString($conn, $_POST['status']);
            $generatedQuery = "UPDATE trips SET train_id=$train_id, route_id=$route_id, trip_date='$trip_date', departure_time='$departure_time', arrival_time='$arrival_time', available_seats=$available_seats, status='$status' WHERE trip_id=$trip_id";
        }
        elseif ($operation == 'delete') {
            $trip_id = (int)$_POST['trip_id'];
            $generatedQuery = "DELETE FROM trips WHERE trip_id=$trip_id";
        }
        elseif ($operation == 'search') {
            $status = escapeString($conn, $_POST['search_status']);
            $date_from = escapeString($conn, $_POST['date_from']);
            $date_to = escapeString($conn, $_POST['date_to']);
            $order_by = $_POST['order_by'] ?? 'trip_date';
            $order_dir = $_POST['order_dir'] ?? 'ASC';
            $limit = (int)($_POST['limit'] ?? 10);
            
            $where_clauses = [];
            if (!empty($status)) {
                $where_clauses[] = "status = '$status'";
            }
            if (!empty($date_from)) {
                $where_clauses[] = "trip_date >= '$date_from'";
            }
            if (!empty($date_to)) {
                $where_clauses[] = "trip_date <= '$date_to'";
            }
            
            $where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';
            $generatedQuery = "SELECT * FROM trips $where_sql ORDER BY $order_by $order_dir LIMIT $limit";
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

$sql_view = "SELECT t.*, tr.train_name, s1.station_name as from_name, s2.station_name as to_name 
             FROM trips t 
             INNER JOIN trains tr ON t.train_id = tr.train_id 
             INNER JOIN routes r ON t.route_id = r.route_id 
             INNER JOIN stations s1 ON r.from_station_id = s1.station_id 
             INNER JOIN stations s2 ON r.to_station_id = s2.station_id 
             ORDER BY t.trip_date DESC, t.departure_time DESC 
             LIMIT 20";
$trips_result = $conn->query($sql_view);
?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/sidebar.php'; ?>

<div class="main-content">
    <div class="page-header">
        <h1>📅 Trips Management</h1>
    </div>

    <?php echo $message; ?>

    <div class="card">
        <h2>➕ Add New Trip</h2>
        <form method="POST" action="">
            <input type="hidden" name="operation" value="create">
            <div class="form-row">
                <div class="form-group">
                    <label>Train *</label>
                    <select name="train_id" required>
                        <option value="">Select Train</option>
                        <?php foreach($trains as $train): ?>
                        <option value="<?php echo $train['train_id']; ?>"><?php echo $train['train_name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Route *</label>
                    <select name="route_id" required>
                        <option value="">Select Route</option>
                        <?php foreach($routes as $route): ?>
                        <option value="<?php echo $route['route_id']; ?>">
                            <?php echo $route['from_name'] . ' → ' . $route['to_name']; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Trip Date *</label>
                    <input type="date" name="trip_date" required min="<?php echo date('Y-m-d'); ?>">
                    <small style="color: #666; font-size: 12px;">💡 Cannot schedule past dates</small>
                </div>
                <div class="form-group">
                    <label>Departure Time *</label>
                    <input type="time" name="departure_time" required placeholder="HH:MM">
                </div>
                <div class="form-group">
                    <label>Arrival Time *</label>
                    <input type="time" name="arrival_time" required placeholder="HH:MM">
                </div>
                <div class="form-group">
                    <label>Available Seats *</label>
                    <input type="number" name="available_seats" value="200" min="1" required placeholder="e.g., 450">
                </div>
                <div class="form-group">
                    <label>Status *</label>
                    <select name="status" required>
                        <option value="Scheduled">Scheduled</option>
                        <option value="Running">Running</option>
                        <option value="Completed">Completed</option>
                        <option value="Cancelled">Cancelled</option>
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
        <h2>🔍 Search Trips (WHERE with Multiple Conditions)</h2>
        <form method="POST" action="">
            <input type="hidden" name="operation" value="search">
            <div class="form-row">
                <div class="form-group">
                    <label>Status</label>
                    <select name="search_status">
                        <option value="">All Status</option>
                        <option value="Scheduled">Scheduled</option>
                        <option value="Running">Running</option>
                        <option value="Completed">Completed</option>
                        <option value="Cancelled">Cancelled</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Date From</label>
                    <input type="date" name="date_from">
                </div>
                <div class="form-group">
                    <label>Date To</label>
                    <input type="date" name="date_to">
                </div>
                <div class="form-group">
                    <label>Order By</label>
                    <select name="order_by">
                        <option value="trip_date">Trip Date</option>
                        <option value="departure_time">Departure Time</option>
                        <option value="available_seats">Available Seats</option>
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
                    <input type="number" name="limit" value="10" min="1" max="100">
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
        <h2>📋 All Trips (4-Table INNER JOIN)</h2>
        <div class="query-section">
            <h3>📝 Query Used (Joining trips, trains, routes, and stations):</h3>
            <textarea class="query-box" readonly><?php echo $sql_view; ?></textarea>
        </div>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Train</th>
                        <th>Route</th>
                        <th>Date</th>
                        <th>Departure</th>
                        <th>Arrival</th>
                        <th>Seats</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($trips_result->num_rows > 0): ?>
                        <?php while($row = $trips_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['trip_id']; ?></td>
                            <td><?php echo $row['train_name']; ?></td>
                            <td><?php echo $row['from_name'] . ' → ' . $row['to_name']; ?></td>
                            <td><?php echo date('d M Y', strtotime($row['trip_date'])); ?></td>
                            <td><?php echo date('H:i', strtotime($row['departure_time'])); ?></td>
                            <td><?php echo date('H:i', strtotime($row['arrival_time'])); ?></td>
                            <td><?php echo $row['available_seats']; ?></td>
                            <td><?php echo $row['status']; ?></td>
                            <td class="action-buttons">
                                <button onclick="editTrip(<?php echo htmlspecialchars(json_encode($row)); ?>)" class="btn btn-primary btn-small">Edit</button>
                                <button onclick="deleteTrip(<?php echo $row['trip_id']; ?>)" class="btn btn-danger btn-small">Delete</button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" style="text-align: center;">No trips found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<script>
function editTrip(trip) {
    alert('Edit functionality: Generate UPDATE query for trip ID ' + trip.trip_id);
}

function deleteTrip(id) {
    if(confirm('Generate DELETE query for trip ID ' + id + '?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="operation" value="delete">
            <input type="hidden" name="trip_id" value="${id}">
            <input type="hidden" name="action" value="generate">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php include '../includes/footer.php'; ?>
