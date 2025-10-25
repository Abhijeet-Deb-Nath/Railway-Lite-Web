<?php
require_once '../config/db.php';
$pageTitle = 'Stations';

$message = '';
$generatedQuery = '';
$action = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action == 'generate') {
        $operation = $_POST['operation'] ?? '';
        
        if ($operation == 'create') {
            $station_name = escapeString($conn, $_POST['station_name']);
            $city = escapeString($conn, $_POST['city']);
            $station_code = escapeString($conn, $_POST['station_code']);
            $generatedQuery = "INSERT INTO stations (station_name, city, station_code) VALUES ('$station_name', '$city', '$station_code')";
        }
        elseif ($operation == 'update') {
            $station_id = (int)$_POST['station_id'];
            $station_name = escapeString($conn, $_POST['station_name']);
            $city = escapeString($conn, $_POST['city']);
            $station_code = escapeString($conn, $_POST['station_code']);
            $generatedQuery = "UPDATE stations SET station_name='$station_name', city='$city', station_code='$station_code' WHERE station_id=$station_id";
        }
        elseif ($operation == 'delete') {
            $station_id = (int)$_POST['station_id'];
            $generatedQuery = "DELETE FROM stations WHERE station_id=$station_id";
        }
        elseif ($operation == 'search') {
            $search_field = $_POST['search_field'];
            $search_value = escapeString($conn, $_POST['search_value']);
            $order_by = $_POST['order_by'] ?? 'station_id';
            $order_dir = $_POST['order_dir'] ?? 'ASC';
            $limit = (int)($_POST['limit'] ?? 10);
            
            $generatedQuery = "SELECT * FROM stations WHERE $search_field LIKE '%$search_value%' ORDER BY $order_by $order_dir LIMIT $limit";
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

// Get all stations for display (default view)
$sql_view = "SELECT * FROM stations ORDER BY station_id DESC";
$stations_result = $conn->query($sql_view);
?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/sidebar.php'; ?>

<div class="main-content">
    <div class="page-header">
        <h1>🚉 Stations Management</h1>
    </div>

    <?php echo $message; ?>

    <!-- Add New Station -->
    <div class="card">
        <h2>➕ Add New Station</h2>
        <form method="POST" action="">
            <input type="hidden" name="operation" value="create">
            <div class="form-row">
                <div class="form-group">
                    <label>Station Name *</label>
                    <input type="text" name="station_name" required>
                </div>
                <div class="form-group">
                    <label>City *</label>
                    <input type="text" name="city" required>
                </div>
                <div class="form-group">
                    <label>Station Code *</label>
                    <input type="text" name="station_code" required maxlength="10">
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

    <!-- Search/Filter Stations -->
    <div class="card">
        <h2>🔍 Search & Filter Stations (WHERE, ORDER BY, LIMIT)</h2>
        <form method="POST" action="">
            <input type="hidden" name="operation" value="search">
            <div class="form-row">
                <div class="form-group">
                    <label>Search Field</label>
                    <select name="search_field">
                        <option value="station_name">Station Name</option>
                        <option value="city">City</option>
                        <option value="station_code">Station Code</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Search Value</label>
                    <input type="text" name="search_value" required>
                </div>
                <div class="form-group">
                    <label>Order By</label>
                    <select name="order_by">
                        <option value="station_id">Station ID</option>
                        <option value="station_name">Station Name</option>
                        <option value="city">City</option>
                        <option value="created_at">Created Date</option>
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

    <!-- View All Stations -->
    <div class="card">
        <h2>📋 All Stations</h2>
        <div class="query-section">
            <h3>📝 Query Used:</h3>
            <textarea class="query-box" readonly><?php echo $sql_view; ?></textarea>
        </div>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Station Name</th>
                        <th>City</th>
                        <th>Station Code</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($stations_result->num_rows > 0): ?>
                        <?php while($row = $stations_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['station_id']; ?></td>
                            <td><?php echo $row['station_name']; ?></td>
                            <td><?php echo $row['city']; ?></td>
                            <td><?php echo $row['station_code']; ?></td>
                            <td><?php echo date('Y-m-d H:i', strtotime($row['created_at'])); ?></td>
                            <td class="action-buttons">
                                <button onclick="editStation(<?php echo htmlspecialchars(json_encode($row)); ?>)" class="btn btn-primary btn-small">Edit</button>
                                <button onclick="deleteStation(<?php echo $row['station_id']; ?>, '<?php echo $row['station_name']; ?>')" class="btn btn-danger btn-small">Delete</button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center;">No stations found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>✏️ Edit Station</h2>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="operation" value="update">
                <input type="hidden" name="station_id" id="edit_station_id">
                <div class="form-group">
                    <label>Station Name *</label>
                    <input type="text" name="station_name" id="edit_station_name" required>
                </div>
                <div class="form-group">
                    <label>City *</label>
                    <input type="text" name="city" id="edit_city" required>
                </div>
                <div class="form-group">
                    <label>Station Code *</label>
                    <input type="text" name="station_code" id="edit_station_code" required maxlength="10">
                </div>
                
                <div class="btn-group">
                    <button type="submit" name="action" value="generate" class="btn btn-primary">Generate UPDATE Query</button>
                    <button type="button" onclick="closeModal()" class="btn btn-secondary">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>🗑️ Delete Station</h2>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="operation" value="delete">
                <input type="hidden" name="station_id" id="delete_station_id">
                <p>Are you sure you want to delete station: <strong id="delete_station_name"></strong>?</p>
                
                <div class="btn-group">
                    <button type="submit" name="action" value="generate" class="btn btn-primary">Generate DELETE Query</button>
                    <button type="button" onclick="closeModal()" class="btn btn-secondary">Cancel</button>
                </div>
            </form>
        </div>
    </div>

</div>

<script>
function editStation(station) {
    document.getElementById('edit_station_id').value = station.station_id;
    document.getElementById('edit_station_name').value = station.station_name;
    document.getElementById('edit_city').value = station.city;
    document.getElementById('edit_station_code').value = station.station_code;
    document.getElementById('editModal').classList.add('active');
}

function deleteStation(id, name) {
    document.getElementById('delete_station_id').value = id;
    document.getElementById('delete_station_name').textContent = name;
    document.getElementById('deleteModal').classList.add('active');
}

function closeModal() {
    document.querySelectorAll('.modal').forEach(modal => {
        modal.classList.remove('active');
    });
}

// Close modal when clicking outside
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        closeModal();
    }
}
</script>

<?php include '../includes/footer.php'; ?>
