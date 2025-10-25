<?php
require_once '../config/db.php';
$pageTitle = 'Stations';

$message = '';
$generatedQuery = '';
$executedQuery = '';
$action = '';
$showQueryBox = false;
$currentOperation = 'create'; // Default tab
$search_results = null;

// Get all cities for dropdown
$cities_result = $conn->query("SELECT DISTINCT city FROM stations ORDER BY city");
$cities = [];
while($row = $cities_result->fetch_assoc()) {
    $cities[] = $row['city'];
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    $currentOperation = $_POST['operation'] ?? 'create'; // Remember which tab was active
    
    if ($action == 'generate') {
        $operation = $_POST['operation'] ?? '';
        $showQueryBox = true;
        
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
            $search_field = $_POST['search_field'] ?? 'station_name';
            $search_value = escapeString($conn, $_POST['search_value']);
            $order_by = $_POST['order_by'] ?? 'station_id';
            $order_dir = $_POST['order_dir'] ?? 'ASC';
            $limit = (int)($_POST['limit'] ?? 10);
            
            if (!empty($search_value)) {
                $generatedQuery = "SELECT * FROM stations WHERE $search_field LIKE '%$search_value%' ORDER BY $order_by $order_dir LIMIT $limit";
            } else {
                $generatedQuery = "SELECT * FROM stations ORDER BY $order_by $order_dir LIMIT $limit";
            }
        }
    }
    elseif ($action == 'execute' && !empty($_POST['query'])) {
        $generatedQuery = $_POST['query'];
        $executedQuery = $generatedQuery;
        $showQueryBox = true;
        $result = executeQuery($conn, $generatedQuery);
        
        if ($result['success']) {
            $message = '<div class="alert alert-success">✓ Query executed successfully!</div>';
            
            // For INSERT/UPDATE/DELETE - redirect to refresh list
            if (stripos($generatedQuery, 'INSERT') === 0 || 
                stripos($generatedQuery, 'UPDATE') === 0 || 
                stripos($generatedQuery, 'DELETE') === 0) {
                header("Location: stations.php?success=1");
                exit;
            }
            // For SELECT - execute and show results
            elseif (stripos($generatedQuery, 'SELECT') === 0) {
                $search_results = $conn->query($generatedQuery);
            }
        } else {
            $message = '<div class="alert alert-error">✗ Error: ' . $result['error'] . '</div>';
        }
    }
}

// Check for redirect success message
if (isset($_GET['success'])) {
    $message = '<div class="alert alert-success">✓ Operation completed successfully!</div>';
}

// Get all stations for display (default view - NO QUERY SHOWN)
$sql_view = "SELECT * FROM stations ORDER BY station_id DESC";
$stations_result = $search_results ?? $conn->query($sql_view);
$list_title = $search_results ? "Search Results" : "All Stations";

// Get station for edit if edit_id is provided
$edit_station = null;
if (isset($_GET['edit_id'])) {
    $edit_id = (int)$_GET['edit_id'];
    $edit_result = $conn->query("SELECT * FROM stations WHERE station_id = $edit_id");
    $edit_station = $edit_result->fetch_assoc();
}
?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/sidebar.php'; ?>

<div class="main-content">
    <div class="page-header">
        <h1>🚉 Stations Management</h1>
    </div>

    <?php echo $message; ?>

    <!-- Single Operations Card -->
    <div class="card">
        <h2>🔧 Station Operations</h2>
        
        <!-- Operation Selector -->
        <div class="form-group">
            <label><strong>Select Operation:</strong></label>
            <select id="operation-selector" onchange="switchOperation(this.value)" class="operation-select">
                <option value="create" <?php echo $currentOperation == 'create' ? 'selected' : ''; ?>>➕ Add New Station</option>
                <option value="search" <?php echo $currentOperation == 'search' ? 'selected' : ''; ?>>🔍 Search & Filter Stations</option>
            </select>
        </div>

        <!-- CREATE Operation Form -->
        <form method="POST" action="" id="form-create" style="display: <?php echo $currentOperation == 'create' ? 'block' : 'none'; ?>;">
            <input type="hidden" name="operation" value="create">
            <div class="form-row">
                <div class="form-group">
                    <label>Station Name *</label>
                    <input type="text" name="station_name" required>
                </div>
                <div class="form-group">
                    <label>City *</label>
                    <input type="text" name="city" required list="city-list">
                    <datalist id="city-list">
                        <?php foreach($cities as $city): ?>
                        <option value="<?php echo $city; ?>">
                        <?php endforeach; ?>
                        <option value="Dhaka">
                        <option value="Chittagong">
                        <option value="Sylhet">
                        <option value="Rajshahi">
                        <option value="Khulna">
                    </datalist>
                </div>
                <div class="form-group">
                    <label>Station Code *</label>
                    <input type="text" name="station_code" required maxlength="10">
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
                    <label>Search Field</label>
                    <select name="search_field">
                        <option value="station_name">Station Name</option>
                        <option value="city">City</option>
                        <option value="station_code">Station Code</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Search Value</label>
                    <input type="text" name="search_value" placeholder="Leave empty for all">
                </div>
                <div class="form-group">
                    <label>Order By</label>
                    <select name="order_by">
                        <option value="station_id">Station ID</option>
                        <option value="station_name">Station Name</option>
                        <option value="city">City</option>
                        <option value="station_code">Station Code</option>
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
        // Hide all forms
        document.getElementById('form-create').style.display = 'none';
        document.getElementById('form-search').style.display = 'none';
        
        // Show selected form
        document.getElementById('form-' + operation).style.display = 'block';
    }
    
    // On page load, show the correct tab based on server state
    window.addEventListener('DOMContentLoaded', function() {
        var currentOp = '<?php echo $currentOperation; ?>';
        switchOperation(currentOp);
        document.getElementById('operation-selector').value = currentOp;
    });
    </script>

    <!-- Update Station -->
    <?php if ($edit_station): ?>
    <div class="card">
        <h2>✏️ Update Station #<?php echo $edit_station['station_id']; ?></h2>
        <form method="POST" action="">
            <input type="hidden" name="operation" value="update">
            <input type="hidden" name="station_id" value="<?php echo $edit_station['station_id']; ?>">
            <div class="form-row">
                <div class="form-group">
                    <label>Station Name *</label>
                    <input type="text" name="station_name" value="<?php echo $edit_station['station_name']; ?>" required>
                </div>
                <div class="form-group">
                    <label>City *</label>
                    <input type="text" name="city" value="<?php echo $edit_station['city']; ?>" required>
                </div>
                <div class="form-group">
                    <label>Station Code *</label>
                    <input type="text" name="station_code" value="<?php echo $edit_station['station_code']; ?>" required maxlength="10">
                </div>
            </div>
            
            <?php if ($showQueryBox && isset($_POST['operation']) && $_POST['operation'] == 'update'): ?>
            <div class="query-section">
                <h3>📝 Generated SQL Query:</h3>
                <textarea class="query-box" name="query" readonly><?php echo $generatedQuery; ?></textarea>
            </div>
            <div class="btn-group">
                <button type="submit" name="action" value="execute" class="btn btn-success">✓ Execute Query</button>
                <button type="submit" name="action" value="generate" class="btn btn-secondary">🔄 Regenerate</button>
                <a href="stations.php" class="btn btn-secondary">Cancel</a>
            </div>
            <?php else: ?>
            <div class="btn-group">
                <button type="submit" name="action" value="generate" class="btn btn-primary">Generate UPDATE Query</button>
                <a href="stations.php" class="btn btn-secondary">Cancel</a>
            </div>
            <?php endif; ?>
        </form>
    </div>
    <?php endif; ?>

    <!-- Executed Query Display -->
    <?php if (!empty($executedQuery)): ?>
    <div class="card">
        <h2>✅ Executed Query</h2>
        <div class="query-section">
            <textarea class="query-box" readonly><?php echo $executedQuery; ?></textarea>
        </div>
    </div>
    <?php endif; ?>

    <!-- Stations List (All or Filtered) -->
    <div class="card">
        <h2>📋 <?php echo $list_title; ?></h2>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Station Name</th>
                        <th>City</th>
                        <th>Code</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if ($stations_result->num_rows > 0):
                        while($row = $stations_result->fetch_assoc()): 
                    ?>
                    <tr>
                        <td><?php echo $row['station_id']; ?></td>
                        <td><?php echo $row['station_name']; ?></td>
                        <td><?php echo $row['city']; ?></td>
                        <td><?php echo $row['station_code']; ?></td>
                        <td><?php echo date('Y-m-d H:i', strtotime($row['created_at'])); ?></td>
                        <td>
                            <a href="?edit_id=<?php echo $row['station_id']; ?>" class="btn-small btn-primary">Edit</a>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this station?');">
                                <input type="hidden" name="operation" value="delete">
                                <input type="hidden" name="station_id" value="<?php echo $row['station_id']; ?>">
                                <button type="submit" name="action" value="generate" class="btn-small btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php 
                        endwhile;
                    else:
                    ?>
                    <tr>
                        <td colspan="6" style="text-align: center;">No stations found</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Delete Confirmation -->
    <?php if ($showQueryBox && isset($_POST['operation']) && $_POST['operation'] == 'delete'): ?>
    <div class="card">
        <h2>🗑️ Delete Station</h2>
        <form method="POST" action="">
            <div class="query-section">
                <h3>📝 Generated SQL Query:</h3>
                <textarea class="query-box" name="query" readonly><?php echo $generatedQuery; ?></textarea>
            </div>
            <p class="warning">⚠️ Warning: This will permanently delete the station and all related records!</p>
            <div class="btn-group">
                <button type="submit" name="action" value="execute" class="btn btn-danger">✓ Confirm Delete</button>
                <a href="stations.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
    <?php endif; ?>

</div>

<?php include '../includes/footer.php'; ?>
