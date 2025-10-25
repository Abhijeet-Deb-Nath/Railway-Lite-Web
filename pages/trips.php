<?php
require_once '../config/db.php';
$pageTitle = 'Trips';

// State management variables
$currentOperation = $_POST['operation'] ?? 'create';
$executedQuery = '';
$showQueryBox = false;
$generatedQuery = '';
$search_results = null;
$list_title = 'All Trips (Last 20)';
$message = '';

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
                 INNER JOIN stations s2 ON r.to_station_id = s2.station_id
                 ORDER BY s1.station_name";
$routes_result = $conn->query($routes_query);
$routes = [];
while($row = $routes_result->fetch_assoc()) {
    $routes[] = $row;
}

// Display success message if redirected after successful operation
if (isset($_GET['success']) && $_GET['success'] == 1) {
    $message = '<div class="alert alert-success">✓ Operation completed successfully!</div>';
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action == 'generate') {
        $operation = $_POST['operation'] ?? '';
        $showQueryBox = true;
        
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
        elseif ($operation == 'search') {
            $status = escapeString($conn, $_POST['search_status']);
            $date_from = escapeString($conn, $_POST['date_from']);
            $date_to = escapeString($conn, $_POST['date_to']);
            $order_by = $_POST['order_by'] ?? 'trip_date';
            $order_dir = $_POST['order_dir'] ?? 'DESC';
            $limit = (int)($_POST['limit'] ?? 10);
            
            $where_clauses = [];
            if (!empty($status)) {
                $where_clauses[] = "t.status = '$status'";
            }
            if (!empty($date_from)) {
                $where_clauses[] = "t.trip_date >= '$date_from'";
            }
            if (!empty($date_to)) {
                $where_clauses[] = "t.trip_date <= '$date_to'";
            }
            
            $where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';
            $generatedQuery = "SELECT t.*, tr.train_name, s1.station_name as from_name, s2.station_name as to_name 
                               FROM trips t 
                               INNER JOIN trains tr ON t.train_id = tr.train_id 
                               INNER JOIN routes r ON t.route_id = r.route_id 
                               INNER JOIN stations s1 ON r.from_station_id = s1.station_id 
                               INNER JOIN stations s2 ON r.to_station_id = s2.station_id 
                               $where_sql 
                               ORDER BY t.$order_by $order_dir 
                               LIMIT $limit";
        }
    }
    elseif ($action == 'execute' && !empty($_POST['query'])) {
        $generatedQuery = $_POST['query'];
        $operation = $_POST['operation'] ?? '';
        
        $result = executeQuery($conn, $generatedQuery);
        
        if ($result['success']) {
            // For INSERT/UPDATE/DELETE, redirect to avoid form resubmission
            if ($operation == 'create') {
                $executedQuery = $generatedQuery;
                header("Location: trips.php?success=1");
                exit;
            }
            // For SELECT, update search results
            elseif ($operation == 'search') {
                $search_results = $conn->query($generatedQuery);
                $list_title = 'Search Results';
                $executedQuery = $generatedQuery;
                $currentOperation = 'search'; // Keep on search tab
            }
        } else {
            $message = '<div class="alert alert-error">✗ Error: ' . $result['error'] . '</div>';
            $showQueryBox = true;
        }
    }
}

// Default query for listing all trips
$sql_view = "SELECT t.*, tr.train_name, s1.station_name as from_name, s2.station_name as to_name 
             FROM trips t 
             INNER JOIN trains tr ON t.train_id = tr.train_id 
             INNER JOIN routes r ON t.route_id = r.route_id 
             INNER JOIN stations s1 ON r.from_station_id = s1.station_id 
             INNER JOIN stations s2 ON r.to_station_id = s2.station_id 
             ORDER BY t.trip_date DESC, t.departure_time DESC 
             LIMIT 20";

// Use search results if available, otherwise default list
$trips_result = $search_results ?? $conn->query($sql_view);
?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/sidebar.php'; ?>

<div class="main-content">
    <div class="page-header">
        <h1>📅 Trips Management</h1>
    </div>

    <?php echo $message; ?>

    <!-- Single Operations Card -->
    <div class="card">
        <h2>🔧 Trip Operations</h2>
        
        <!-- Operation Selector -->
        <div class="form-group">
            <label><strong>Select Operation:</strong></label>
            <select id="operation-selector" onchange="switchOperation(this.value)" class="operation-select">
                <option value="create" <?php echo $currentOperation == 'create' ? 'selected' : ''; ?>>➕ Add New Trip</option>
                <option value="search" <?php echo $currentOperation == 'search' ? 'selected' : ''; ?>>🔍 Search & Filter Trips</option>
            </select>
        </div>

        <!-- CREATE Operation Form -->
        <form method="POST" action="" id="form-create" style="display: <?php echo $currentOperation == 'create' ? 'block' : 'none'; ?>;">
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
                    <label>Trip Date * <small style="color: #666; font-weight: normal;">(Cannot schedule past dates)</small></label>
                    <input type="date" name="trip_date" required min="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="form-group">
                    <label>Departure Time *</label>
                    <input type="time" name="departure_time" required placeholder="HH:MM">
                </div>
                <div class="form-group">
                    <label>Arrival Time *</label>
                    <input type="time" name="arrival_time" required placeholder="HH:MM">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Available Seats *</label>
                    <input type="number" name="available_seats" required min="1" max="500" placeholder="e.g., 200">
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
            
            <?php if ($showQueryBox && isset($_POST['operation']) && $_POST['operation'] == 'create'): ?>
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

        <!-- SEARCH Operation Form -->
        <form method="POST" action="" id="form-search" style="display: <?php echo $currentOperation == 'search' ? 'block' : 'none'; ?>;">
            <input type="hidden" name="operation" value="search">
            <div class="form-row">
                <div class="form-group">
                    <label>Status</label>
                    <select name="search_status">
                        <option value="">All Statuses</option>
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
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Order By</label>
                    <select name="order_by">
                        <option value="trip_date">Trip Date</option>
                        <option value="departure_time">Departure Time</option>
                        <option value="status">Status</option>
                        <option value="available_seats">Available Seats</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Order Direction</label>
                    <select name="order_dir">
                        <option value="ASC">Ascending</option>
                        <option value="DESC" selected>Descending</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Limit</label>
                    <select name="limit">
                        <option value="5">5</option>
                        <option value="10" selected>10</option>
                        <option value="20">20</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>
            </div>
            
            <?php if ($showQueryBox && isset($_POST['operation']) && $_POST['operation'] == 'search'): ?>
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
                <button type="submit" name="action" value="generate" class="btn btn-primary">Generate SELECT Query</button>
            </div>
            <?php endif; ?>
        </form>
    </div>

    <script>
    // Switch operation tabs
    function switchOperation(operation) {
        document.getElementById('form-create').style.display = 'none';
        document.getElementById('form-search').style.display = 'none';
        document.getElementById('form-' + operation).style.display = 'block';
    }
    
    // On page load, show the correct tab based on server state
    window.addEventListener('DOMContentLoaded', function() {
        var currentOp = '<?php echo $currentOperation; ?>';
        switchOperation(currentOp);
        document.getElementById('operation-selector').value = currentOp;
    });
    </script>

    <!-- Executed Query Display -->
    <?php if (!empty($executedQuery)): ?>
    <div class="card">
        <h2>✅ Executed Query</h2>
        <div class="query-section">
            <textarea class="query-box" readonly><?php echo $executedQuery; ?></textarea>
        </div>
    </div>
    <?php endif; ?>

    <!-- Trips List (All or Filtered) -->
    <div class="card">
        <h2>📋 <?php echo $list_title; ?></h2>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Train</th>
                        <th>Route</th>
                        <th>Trip Date</th>
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
                            <td><?php echo date('h:i A', strtotime($row['departure_time'])); ?></td>
                            <td><?php echo date('h:i A', strtotime($row['arrival_time'])); ?></td>
                            <td><?php echo $row['available_seats']; ?></td>
                            <td><span class="badge badge-<?php echo strtolower($row['status']); ?>"><?php echo $row['status']; ?></span></td>
                            <td>
                                <button class="btn-icon" onclick="editTrip(<?php echo $row['trip_id']; ?>)" title="Edit">✏️</button>
                                <button class="btn-icon" onclick="deleteTrip(<?php echo $row['trip_id']; ?>)" title="Delete">🗑️</button>
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

    <!-- Update Modal -->
    <div id="updateModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2>✏️ Update Trip</h2>
            <form method="POST" action="" id="updateForm">
                <input type="hidden" name="operation" value="update">
                <input type="hidden" name="trip_id" id="update_trip_id">
                <div class="form-row">
                    <div class="form-group">
                        <label>Train *</label>
                        <select name="train_id" id="update_train_id" required>
                            <option value="">Select Train</option>
                            <?php foreach($trains as $train): ?>
                            <option value="<?php echo $train['train_id']; ?>"><?php echo $train['train_name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Route *</label>
                        <select name="route_id" id="update_route_id" required>
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
                        <input type="date" name="trip_date" id="update_trip_date" required min="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="form-group">
                        <label>Departure Time *</label>
                        <input type="time" name="departure_time" id="update_departure_time" required>
                    </div>
                    <div class="form-group">
                        <label>Arrival Time *</label>
                        <input type="time" name="arrival_time" id="update_arrival_time" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Available Seats *</label>
                        <input type="number" name="available_seats" id="update_available_seats" required min="1" max="500">
                    </div>
                    <div class="form-group">
                        <label>Status *</label>
                        <select name="status" id="update_status" required>
                            <option value="Scheduled">Scheduled</option>
                            <option value="Running">Running</option>
                            <option value="Completed">Completed</option>
                            <option value="Cancelled">Cancelled</option>
                        </select>
                    </div>
                </div>
                
                <div id="updateQuerySection" style="display: none;">
                    <div class="query-section">
                        <h3>📝 Generated SQL Query:</h3>
                        <textarea class="query-box" name="query" id="updateQueryBox" readonly></textarea>
                    </div>
                    <div class="btn-group">
                        <button type="submit" name="action" value="execute" class="btn btn-success">✓ Execute Query</button>
                        <button type="button" onclick="generateUpdateQuery()" class="btn btn-secondary">🔄 Regenerate</button>
                    </div>
                </div>
                <div id="updateGenerateBtn">
                    <div class="btn-group">
                        <button type="button" onclick="generateUpdateQuery()" class="btn btn-primary">Generate UPDATE Query</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeDeleteModal()">&times;</span>
            <h2>🗑️ Delete Trip</h2>
            <form method="POST" action="" id="deleteForm">
                <input type="hidden" name="operation" value="delete">
                <input type="hidden" name="trip_id" id="delete_trip_id">
                <p>Are you sure you want to delete this trip?</p>
                
                <div id="deleteQuerySection" style="display: none;">
                    <div class="query-section">
                        <h3>📝 Generated SQL Query:</h3>
                        <textarea class="query-box" name="query" id="deleteQueryBox" readonly></textarea>
                    </div>
                    <div class="btn-group">
                        <button type="submit" name="action" value="execute" class="btn btn-danger">✓ Execute Query</button>
                        <button type="button" onclick="generateDeleteQuery()" class="btn btn-secondary">🔄 Regenerate</button>
                    </div>
                </div>
                <div id="deleteGenerateBtn">
                    <div class="btn-group">
                        <button type="button" onclick="generateDeleteQuery()" class="btn btn-danger">Generate DELETE Query</button>
                        <button type="button" onclick="closeDeleteModal()" class="btn btn-secondary">Cancel</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
    function editTrip(id) {
        fetch('?action=get_trip&id=' + id)
            .then(response => response.json())
            .then(data => {
                document.getElementById('update_trip_id').value = data.trip_id;
                document.getElementById('update_train_id').value = data.train_id;
                document.getElementById('update_route_id').value = data.route_id;
                document.getElementById('update_trip_date').value = data.trip_date;
                document.getElementById('update_departure_time').value = data.departure_time;
                document.getElementById('update_arrival_time').value = data.arrival_time;
                document.getElementById('update_available_seats').value = data.available_seats;
                document.getElementById('update_status').value = data.status;
                document.getElementById('updateModal').style.display = 'block';
            });
    }

    function deleteTrip(id) {
        document.getElementById('delete_trip_id').value = id;
        document.getElementById('deleteModal').style.display = 'block';
    }

    function closeModal() {
        document.getElementById('updateModal').style.display = 'none';
        document.getElementById('updateQuerySection').style.display = 'none';
        document.getElementById('updateGenerateBtn').style.display = 'block';
    }

    function closeDeleteModal() {
        document.getElementById('deleteModal').style.display = 'none';
        document.getElementById('deleteQuerySection').style.display = 'none';
        document.getElementById('deleteGenerateBtn').style.display = 'block';
    }

    function generateUpdateQuery() {
        const form = document.getElementById('updateForm');
        const formData = new FormData(form);
        const trip_id = formData.get('trip_id');
        const train_id = formData.get('train_id');
        const route_id = formData.get('route_id');
        const trip_date = formData.get('trip_date');
        const departure_time = formData.get('departure_time');
        const arrival_time = formData.get('arrival_time');
        const available_seats = formData.get('available_seats');
        const status = formData.get('status');
        
        const query = `UPDATE trips SET train_id=${train_id}, route_id=${route_id}, trip_date='${trip_date}', departure_time='${departure_time}', arrival_time='${arrival_time}', available_seats=${available_seats}, status='${status}' WHERE trip_id=${trip_id}`;
        
        document.getElementById('updateQueryBox').value = query;
        document.getElementById('updateQuerySection').style.display = 'block';
        document.getElementById('updateGenerateBtn').style.display = 'none';
    }

    function generateDeleteQuery() {
        const trip_id = document.getElementById('delete_trip_id').value;
        const query = `DELETE FROM trips WHERE trip_id=${trip_id}`;
        
        document.getElementById('deleteQueryBox').value = query;
        document.getElementById('deleteQuerySection').style.display = 'block';
        document.getElementById('deleteGenerateBtn').style.display = 'none';
    }

    window.onclick = function(event) {
        if (event.target.className === 'modal') {
            closeModal();
            closeDeleteModal();
        }
    }
    </script>

</div>

<?php include '../includes/footer.php'; ?>
<?php
// AJAX endpoint for getting trip data
if (isset($_GET['action']) && $_GET['action'] == 'get_trip' && isset($_GET['id'])) {
    $trip_id = (int)$_GET['id'];
    $result = $conn->query("SELECT * FROM trips WHERE trip_id = $trip_id");
    if ($row = $result->fetch_assoc()) {
        header('Content-Type: application/json');
        echo json_encode($row);
        exit;
    }
}
?>
