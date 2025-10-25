<?php
require_once '../config/db.php';
$pageTitle = 'Routes';

$message = '';
$generatedQuery = '';

// Get stations for dropdowns
$stations_query = "SELECT station_id, station_name, station_code FROM stations ORDER BY station_name";
$stations_result = $conn->query($stations_query);
$stations = [];
while($row = $stations_result->fetch_assoc()) {
    $stations[] = $row;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action == 'generate') {
        $operation = $_POST['operation'] ?? '';
        
        if ($operation == 'create') {
            $from_station_id = (int)$_POST['from_station_id'];
            $to_station_id = (int)$_POST['to_station_id'];
            $distance_km = (float)$_POST['distance_km'];
            $base_fare = (float)$_POST['base_fare'];
            $generatedQuery = "INSERT INTO routes (from_station_id, to_station_id, distance_km, base_fare) VALUES ($from_station_id, $to_station_id, $distance_km, $base_fare)";
        }
        elseif ($operation == 'update') {
            $route_id = (int)$_POST['route_id'];
            $from_station_id = (int)$_POST['from_station_id'];
            $to_station_id = (int)$_POST['to_station_id'];
            $distance_km = (float)$_POST['distance_km'];
            $base_fare = (float)$_POST['base_fare'];
            $generatedQuery = "UPDATE routes SET from_station_id=$from_station_id, to_station_id=$to_station_id, distance_km=$distance_km, base_fare=$base_fare WHERE route_id=$route_id";
        }
        elseif ($operation == 'delete') {
            $route_id = (int)$_POST['route_id'];
            $generatedQuery = "DELETE FROM routes WHERE route_id=$route_id";
        }
        elseif ($operation == 'search') {
            $min_distance = (float)($_POST['min_distance'] ?? 0);
            $max_distance = (float)($_POST['max_distance'] ?? 10000);
            $order_by = $_POST['order_by'] ?? 'route_id';
            $order_dir = $_POST['order_dir'] ?? 'ASC';
            $limit = (int)($_POST['limit'] ?? 10);
            
            $generatedQuery = "SELECT * FROM routes WHERE distance_km >= $min_distance AND distance_km <= $max_distance ORDER BY $order_by $order_dir LIMIT $limit";
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

$sql_view = "SELECT r.*, s1.station_name as from_name, s2.station_name as to_name 
             FROM routes r 
             INNER JOIN stations s1 ON r.from_station_id = s1.station_id 
             INNER JOIN stations s2 ON r.to_station_id = s2.station_id 
             ORDER BY r.route_id DESC";
$routes_result = $conn->query($sql_view);
?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/sidebar.php'; ?>

<div class="main-content">
    <div class="page-header">
        <h1>🛤️ Routes Management</h1>
    </div>

    <?php echo $message; ?>

    <div class="card">
        <h2>➕ Add New Route</h2>
        <form method="POST" action="">
            <input type="hidden" name="operation" value="create">
            <div class="form-row">
                <div class="form-group">
                    <label>From Station *</label>
                    <select name="from_station_id" required>
                        <option value="">Select Station</option>
                        <?php foreach($stations as $station): ?>
                        <option value="<?php echo $station['station_id']; ?>">
                            <?php echo $station['station_name'] . ' (' . $station['station_code'] . ')'; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>To Station *</label>
                    <select name="to_station_id" required>
                        <option value="">Select Station</option>
                        <?php foreach($stations as $station): ?>
                        <option value="<?php echo $station['station_id']; ?>">
                            <?php echo $station['station_name'] . ' (' . $station['station_code'] . ')'; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Distance (KM) *</label>
                    <input type="number" name="distance_km" step="0.01" min="0" required>
                </div>
                <div class="form-group">
                    <label>Base Fare (₹) *</label>
                    <input type="number" name="base_fare" step="0.01" min="0" required>
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
        <h2>🔍 Filter Routes by Distance (WHERE with Range)</h2>
        <form method="POST" action="">
            <input type="hidden" name="operation" value="search">
            <div class="form-row">
                <div class="form-group">
                    <label>Min Distance (KM)</label>
                    <input type="number" name="min_distance" value="0" step="0.01" min="0">
                </div>
                <div class="form-group">
                    <label>Max Distance (KM)</label>
                    <input type="number" name="max_distance" value="1000" step="0.01" min="0">
                </div>
                <div class="form-group">
                    <label>Order By</label>
                    <select name="order_by">
                        <option value="route_id">Route ID</option>
                        <option value="distance_km">Distance</option>
                        <option value="base_fare">Base Fare</option>
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
        <h2>📋 All Routes (with INNER JOIN)</h2>
        <div class="query-section">
            <h3>📝 Query Used (Joining routes with stations):</h3>
            <textarea class="query-box" readonly><?php echo $sql_view; ?></textarea>
        </div>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>From Station</th>
                        <th>To Station</th>
                        <th>Distance (KM)</th>
                        <th>Base Fare (₹)</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($routes_result->num_rows > 0): ?>
                        <?php while($row = $routes_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['route_id']; ?></td>
                            <td><?php echo $row['from_name']; ?></td>
                            <td><?php echo $row['to_name']; ?></td>
                            <td><?php echo $row['distance_km']; ?></td>
                            <td><?php echo number_format($row['base_fare'], 2); ?></td>
                            <td class="action-buttons">
                                <button onclick="editRoute(<?php echo htmlspecialchars(json_encode($row)); ?>)" class="btn btn-primary btn-small">Edit</button>
                                <button onclick="deleteRoute(<?php echo $row['route_id']; ?>)" class="btn btn-danger btn-small">Delete</button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center;">No routes found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>✏️ Edit Route</h2>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="operation" value="update">
                <input type="hidden" name="route_id" id="edit_route_id">
                <div class="form-group">
                    <label>From Station *</label>
                    <select name="from_station_id" id="edit_from_station_id" required>
                        <?php foreach($stations as $station): ?>
                        <option value="<?php echo $station['station_id']; ?>">
                            <?php echo $station['station_name'] . ' (' . $station['station_code'] . ')'; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>To Station *</label>
                    <select name="to_station_id" id="edit_to_station_id" required>
                        <?php foreach($stations as $station): ?>
                        <option value="<?php echo $station['station_id']; ?>">
                            <?php echo $station['station_name'] . ' (' . $station['station_code'] . ')'; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Distance (KM) *</label>
                    <input type="number" name="distance_km" id="edit_distance_km" step="0.01" min="0" required>
                </div>
                <div class="form-group">
                    <label>Base Fare (₹) *</label>
                    <input type="number" name="base_fare" id="edit_base_fare" step="0.01" min="0" required>
                </div>
                
                <div class="btn-group">
                    <button type="submit" name="action" value="generate" class="btn btn-primary">Generate UPDATE Query</button>
                    <button type="button" onclick="closeModal()" class="btn btn-secondary">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>🗑️ Delete Route</h2>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="operation" value="delete">
                <input type="hidden" name="route_id" id="delete_route_id">
                <p>Are you sure you want to delete this route?</p>
                
                <div class="btn-group">
                    <button type="submit" name="action" value="generate" class="btn btn-primary">Generate DELETE Query</button>
                    <button type="button" onclick="closeModal()" class="btn btn-secondary">Cancel</button>
                </div>
            </form>
        </div>
    </div>

</div>

<script>
function editRoute(route) {
    document.getElementById('edit_route_id').value = route.route_id;
    document.getElementById('edit_from_station_id').value = route.from_station_id;
    document.getElementById('edit_to_station_id').value = route.to_station_id;
    document.getElementById('edit_distance_km').value = route.distance_km;
    document.getElementById('edit_base_fare').value = route.base_fare;
    document.getElementById('editModal').classList.add('active');
}

function deleteRoute(id) {
    document.getElementById('delete_route_id').value = id;
    document.getElementById('deleteModal').classList.add('active');
}

function closeModal() {
    document.querySelectorAll('.modal').forEach(modal => {
        modal.classList.remove('active');
    });
}

window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        closeModal();
    }
}
</script>

<?php include '../includes/footer.php'; ?>
