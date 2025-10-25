<?php
require_once '../config/db.php';
$pageTitle = 'Bookings';

// State management variables
$currentOperation = $_POST['operation'] ?? 'create';
$executedQuery = '';
$showQueryBox = false;
$generatedQuery = '';
$search_results = null;
$list_title = 'All Bookings (Last 30)';
$message = '';

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
            $trip_id = (int)$_POST['trip_id'];
            $passenger_id = (int)$_POST['passenger_id'];
            $seats_booked = (int)$_POST['seats_booked'];
            $total_fare = (float)$_POST['total_fare'];
            $status = escapeString($conn, $_POST['status']);
            $generatedQuery = "INSERT INTO bookings (trip_id, passenger_id, seats_booked, total_fare, status) VALUES ($trip_id, $passenger_id, $seats_booked, $total_fare, '$status')";
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
            
            $where_clauses = ["b.total_fare >= $min_fare", "b.total_fare <= $max_fare"];
            if (!empty($status)) {
                $where_clauses[] = "b.status = '$status'";
            }
            if (!empty($date_from)) {
                $where_clauses[] = "b.booking_date >= '$date_from'";
            }
            if (!empty($date_to)) {
                $where_clauses[] = "b.booking_date <= '$date_to'";
            }
            
            $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
            $generatedQuery = "SELECT b.*, p.passenger_name, p.phone, tr.train_name, s1.station_name as from_name, s2.station_name as to_name, t.trip_date 
                               FROM bookings b 
                               INNER JOIN passengers p ON b.passenger_id = p.passenger_id 
                               INNER JOIN trips t ON b.trip_id = t.trip_id 
                               INNER JOIN trains tr ON t.train_id = tr.train_id 
                               INNER JOIN routes r ON t.route_id = r.route_id 
                               INNER JOIN stations s1 ON r.from_station_id = s1.station_id 
                               INNER JOIN stations s2 ON r.to_station_id = s2.station_id 
                               $where_sql 
                               ORDER BY b.$order_by $order_dir 
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
                header("Location: bookings.php?success=1");
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

// Default query for listing all bookings
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

// Use search results if available, otherwise default list
$bookings_result = $search_results ?? $conn->query($sql_view);
?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/sidebar.php'; ?>

<div class="main-content">
    <div class="page-header">
        <h1>🎫 Bookings Management</h1>
    </div>

    <?php echo $message; ?>

    <!-- Single Operations Card -->
    <div class="card">
        <h2>🔧 Booking Operations</h2>
        
        <!-- Operation Selector -->
        <div class="form-group">
            <label><strong>Select Operation:</strong></label>
            <select id="operation-selector" onchange="switchOperation(this.value)" class="operation-select">
                <option value="create" <?php echo $currentOperation == 'create' ? 'selected' : ''; ?>>➕ Add New Booking</option>
                <option value="search" <?php echo $currentOperation == 'search' ? 'selected' : ''; ?>>🔍 Search & Filter Bookings</option>
            </select>
        </div>

        <!-- CREATE Operation Form -->
        <form method="POST" action="" id="form-create" style="display: <?php echo $currentOperation == 'create' ? 'block' : 'none'; ?>;">
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
                    <label>Seats Booked * <small style="color: #666; font-weight: normal;">(Max 10 seats per booking)</small></label>
                    <input type="number" name="seats_booked" required min="1" max="10" placeholder="e.g., 2">
                </div>
                <div class="form-group">
                    <label>Total Fare (৳) *</label>
                    <input type="number" name="total_fare" step="0.01" min="0.01" required placeholder="e.g., 900.00">
                </div>
                <div class="form-group">
                    <label>Status *</label>
                    <select name="status" required>
                        <option value="Confirmed">Confirmed</option>
                        <option value="Pending">Pending</option>
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
                        <option value="Confirmed">Confirmed</option>
                        <option value="Pending">Pending</option>
                        <option value="Cancelled">Cancelled</option>
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
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Min Fare (৳)</label>
                    <input type="number" name="min_fare" step="0.01" value="0" min="0" placeholder="0">
                </div>
                <div class="form-group">
                    <label>Max Fare (৳)</label>
                    <input type="number" name="max_fare" step="0.01" value="999999" min="0" placeholder="999999">
                </div>
                <div class="form-group">
                    <label>Order By</label>
                    <select name="order_by">
                        <option value="booking_date">Booking Date</option>
                        <option value="total_fare">Total Fare</option>
                        <option value="seats_booked">Seats Booked</option>
                        <option value="status">Status</option>
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
                        <option value="10">10</option>
                        <option value="20" selected>20</option>
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

    <!-- Bookings List (All or Filtered) -->
    <div class="card">
        <h2>📋 <?php echo $list_title; ?></h2>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Passenger</th>
                        <th>Phone</th>
                        <th>Train</th>
                        <th>Route</th>
                        <th>Trip Date</th>
                        <th>Seats</th>
                        <th>Fare (৳)</th>
                        <th>Status</th>
                        <th>Booking Date</th>
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
                            <td><span class="badge badge-<?php echo strtolower($row['status']); ?>"><?php echo $row['status']; ?></span></td>
                            <td><?php echo date('d M Y', strtotime($row['booking_date'])); ?></td>
                            <td>
                                <button class="btn-icon" onclick="editBooking(<?php echo $row['booking_id']; ?>)" title="Edit">✏️</button>
                                <button class="btn-icon" onclick="deleteBooking(<?php echo $row['booking_id']; ?>)" title="Delete">🗑️</button>
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

    <!-- Update Modal -->
    <div id="updateModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2>✏️ Update Booking</h2>
            <form method="POST" action="" id="updateForm">
                <input type="hidden" name="operation" value="update">
                <input type="hidden" name="booking_id" id="update_booking_id">
                <div class="form-row">
                    <div class="form-group">
                        <label>Seats Booked * <small style="color: #666; font-weight: normal;">(Max 10)</small></label>
                        <input type="number" name="seats_booked" id="update_seats_booked" required min="1" max="10">
                    </div>
                    <div class="form-group">
                        <label>Total Fare (৳) *</label>
                        <input type="number" name="total_fare" id="update_total_fare" step="0.01" min="0.01" required>
                    </div>
                    <div class="form-group">
                        <label>Status *</label>
                        <select name="status" id="update_status" required>
                            <option value="Confirmed">Confirmed</option>
                            <option value="Pending">Pending</option>
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
            <h2>🗑️ Delete Booking</h2>
            <form method="POST" action="" id="deleteForm">
                <input type="hidden" name="operation" value="delete">
                <input type="hidden" name="booking_id" id="delete_booking_id">
                <p>Are you sure you want to delete this booking?</p>
                
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
    function editBooking(id) {
        fetch('?action=get_booking&id=' + id)
            .then(response => response.json())
            .then(data => {
                document.getElementById('update_booking_id').value = data.booking_id;
                document.getElementById('update_seats_booked').value = data.seats_booked;
                document.getElementById('update_total_fare').value = data.total_fare;
                document.getElementById('update_status').value = data.status;
                document.getElementById('updateModal').style.display = 'block';
            });
    }

    function deleteBooking(id) {
        document.getElementById('delete_booking_id').value = id;
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
        const booking_id = formData.get('booking_id');
        const seats_booked = formData.get('seats_booked');
        const total_fare = formData.get('total_fare');
        const status = formData.get('status');
        
        const query = `UPDATE bookings SET seats_booked=${seats_booked}, total_fare=${total_fare}, status='${status}' WHERE booking_id=${booking_id}`;
        
        document.getElementById('updateQueryBox').value = query;
        document.getElementById('updateQuerySection').style.display = 'block';
        document.getElementById('updateGenerateBtn').style.display = 'none';
    }

    function generateDeleteQuery() {
        const booking_id = document.getElementById('delete_booking_id').value;
        const query = `DELETE FROM bookings WHERE booking_id=${booking_id}`;
        
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
// AJAX endpoint for getting booking data
if (isset($_GET['action']) && $_GET['action'] == 'get_booking' && isset($_GET['id'])) {
    $booking_id = (int)$_GET['id'];
    $result = $conn->query("SELECT * FROM bookings WHERE booking_id = $booking_id");
    if ($row = $result->fetch_assoc()) {
        header('Content-Type: application/json');
        echo json_encode($row);
        exit;
    }
}
?>
