<?php
require_once '../config/db.php';
$pageTitle = 'Trains';

$message = '';
$generatedQuery = '';
$executedQuery = '';
$action = '';
$showQueryBox = false;
$currentOperation = 'create'; // Default tab
$search_results = null;

// Get all train types for dropdown
$train_types = ['Express', 'Intercity', 'Local', 'Mail'];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    $currentOperation = $_POST['operation'] ?? 'create'; // Remember which tab was active
    
    if ($action == 'generate') {
        $operation = $_POST['operation'] ?? '';
        $showQueryBox = true;
        
        if ($operation == 'create') {
            $train_name = escapeString($conn, $_POST['train_name']);
            $train_type = escapeString($conn, $_POST['train_type']);
            $total_seats = (int)$_POST['total_seats'];
            $generatedQuery = "INSERT INTO trains (train_name, train_type, total_seats) VALUES ('$train_name', '$train_type', $total_seats)";
        }
        elseif ($operation == 'update') {
            $train_id = (int)$_POST['train_id'];
            $train_name = escapeString($conn, $_POST['train_name']);
            $train_type = escapeString($conn, $_POST['train_type']);
            $total_seats = (int)$_POST['total_seats'];
            $generatedQuery = "UPDATE trains SET train_name='$train_name', train_type='$train_type', total_seats=$total_seats WHERE train_id=$train_id";
        }
        elseif ($operation == 'delete') {
            $train_id = (int)$_POST['train_id'];
            $generatedQuery = "DELETE FROM trains WHERE train_id=$train_id";
        }
        elseif ($operation == 'search') {
            $train_type = $_POST['train_type'] ?? '';
            $order_by = $_POST['order_by'] ?? 'train_id';
            $order_dir = $_POST['order_dir'] ?? 'ASC';
            $limit = (int)($_POST['limit'] ?? 10);
            
            if (!empty($train_type)) {
                $generatedQuery = "SELECT * FROM trains WHERE train_type = '$train_type' ORDER BY $order_by $order_dir LIMIT $limit";
            } else {
                $generatedQuery = "SELECT * FROM trains ORDER BY $order_by $order_dir LIMIT $limit";
            }
        }
    }
    elseif ($action == 'execute' && !empty($_POST['query'])) {
        $generatedQuery = $_POST['query'];
        $operation = $_POST['operation'] ?? '';
        $result = executeQuery($conn, $generatedQuery);
        
        if ($result['success']) {
            // For INSERT/UPDATE/DELETE - redirect to refresh list
            if (stripos($generatedQuery, 'INSERT') === 0 || 
                stripos($generatedQuery, 'UPDATE') === 0 || 
                stripos($generatedQuery, 'DELETE') === 0) {
                header("Location: trains.php?success=1");
                exit;
            }
            // For SELECT - execute and show results
            elseif (stripos($generatedQuery, 'SELECT') === 0) {
                $search_results = $conn->query($generatedQuery);
                $list_title = 'Search Results';
                $executedQuery = $generatedQuery;
                $currentOperation = 'search';
            }
        } else {
            // Keep form filled and show error - DO NOT REDIRECT
            $showQueryBox = true;
            $executedQuery = '';  // Don't show in "Executed Query" section since it failed
            
            $message = '<div class="alert alert-error">✗ Error: ' . $result['error'] . '</div>';
            
            // For UPDATE/DELETE errors, preserve the operation and data
            $currentOperation = $operation;
        }
    }
}

// Check for redirect success message
if (isset($_GET['success'])) {
    $message = '<div class="alert alert-success">✓ Operation completed successfully!</div>';
}

// Get all trains for display
$sql_view = "SELECT * FROM trains ORDER BY train_id DESC";
$trains_result = $search_results ?? $conn->query($sql_view);
$list_title = $search_results ? "Search Results" : "All Trains";

// Get train for edit if edit_id is provided
$edit_train = null;
if (isset($_GET['edit_id'])) {
    $edit_id = (int)$_GET['edit_id'];
    $edit_result = $conn->query("SELECT * FROM trains WHERE train_id = $edit_id");
    $edit_train = $edit_result->fetch_assoc();
}
?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/sidebar.php'; ?>

<div class="main-content">
    <div class="page-header">
        <h1>🚂 Trains Management</h1>
    </div>

    <?php echo $message; ?>

    <!-- Single Operations Card -->
    <div class="card">
        <h2>🔧 Train Operations</h2>
        
        <!-- Operation Selector -->
        <div class="form-group">
            <label><strong>Select Operation:</strong></label>
            <select id="operation-selector" onchange="switchOperation(this.value)" class="operation-select">
                <option value="create" <?php echo $currentOperation == 'create' ? 'selected' : ''; ?>>➕ Add New Train</option>
                <option value="search" <?php echo $currentOperation == 'search' ? 'selected' : ''; ?>>🔍 Search & Filter Trains</option>
            </select>
        </div>

        <!-- CREATE Operation Form -->
        <form method="POST" action="" id="form-create" style="display: <?php echo $currentOperation == 'create' ? 'block' : 'none'; ?>;">
            <input type="hidden" name="operation" value="create">
            <div class="form-row">
                <div class="form-group">
                    <label>Train Name *</label>
                    <input type="text" name="train_name" value="<?php echo $_POST['train_name'] ?? ''; ?>" required placeholder="e.g., Suborna Express">
                </div>
                <div class="form-group">
                    <label>Train Type *</label>
                    <select name="train_type" required>
                        <option value="">Select Type</option>
                        <option value="Express" <?php echo (isset($_POST['train_type']) && $_POST['train_type'] == 'Express') ? 'selected' : ''; ?>>Express</option>
                        <option value="Intercity" <?php echo (isset($_POST['train_type']) && $_POST['train_type'] == 'Intercity') ? 'selected' : ''; ?>>Intercity</option>
                        <option value="Local" <?php echo (isset($_POST['train_type']) && $_POST['train_type'] == 'Local') ? 'selected' : ''; ?>>Local</option>
                        <option value="Mail" <?php echo (isset($_POST['train_type']) && $_POST['train_type'] == 'Mail') ? 'selected' : ''; ?>>Mail</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Total Seats *</label>
                    <input type="number" name="total_seats" value="<?php echo $_POST['total_seats'] ?? ''; ?>" required min="1" placeholder="e.g., 450">
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
                    <label>Filter by Train Type</label>
                    <select name="train_type">
                        <option value="">-- Select Type (or All) --</option>
                        <option value="Express">Express</option>
                        <option value="Intercity">Intercity</option>
                        <option value="Local">Local</option>
                        <option value="Mail">Mail</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Order By</label>
                    <select name="order_by">
                        <option value="train_id">Train ID</option>
                        <option value="train_name">Train Name</option>
                        <option value="train_type">Train Type</option>
                        <option value="total_seats">Total Seats</option>
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

    <!-- Update Train -->
    <?php if ($edit_train): ?>
    <div class="card">
        <h2>✏️ Update Train #<?php echo $edit_train['train_id']; ?></h2>
        <form method="POST" action="">
            <input type="hidden" name="operation" value="update">
            <input type="hidden" name="train_id" value="<?php echo $edit_train['train_id']; ?>">
            <div class="form-row">
                <div class="form-group">
                    <label>Train Name *</label>
                    <input type="text" name="train_name" value="<?php echo $edit_train['train_name']; ?>" required>
                </div>
                <div class="form-group">
                    <label>Train Type *</label>
                    <select name="train_type" required>
                        <option value="Express" <?php echo $edit_train['train_type'] == 'Express' ? 'selected' : ''; ?>>Express</option>
                        <option value="Intercity" <?php echo $edit_train['train_type'] == 'Intercity' ? 'selected' : ''; ?>>Intercity</option>
                        <option value="Local" <?php echo $edit_train['train_type'] == 'Local' ? 'selected' : ''; ?>>Local</option>
                        <option value="Mail" <?php echo $edit_train['train_type'] == 'Mail' ? 'selected' : ''; ?>>Mail</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Total Seats *</label>
                    <input type="number" name="total_seats" value="<?php echo $edit_train['total_seats']; ?>" required min="1">
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
                <a href="trains.php" class="btn btn-secondary">Cancel</a>
            </div>
            <?php else: ?>
            <div class="btn-group">
                <button type="submit" name="action" value="generate" class="btn btn-primary">Generate UPDATE Query</button>
                <a href="trains.php" class="btn btn-secondary">Cancel</a>
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

    <!-- Trains List (All or Filtered) -->
    <div class="card">
        <h2>📋 <?php echo $list_title; ?></h2>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Train Name</th>
                        <th>Type</th>
                        <th>Total Seats</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if ($trains_result->num_rows > 0):
                        while($row = $trains_result->fetch_assoc()): 
                    ?>
                    <tr>
                        <td><?php echo $row['train_id']; ?></td>
                        <td><?php echo $row['train_name']; ?></td>
                        <td><?php echo $row['train_type']; ?></td>
                        <td><?php echo $row['total_seats']; ?></td>
                        <td><?php echo date('Y-m-d H:i', strtotime($row['created_at'])); ?></td>
                        <td>
                            <a href="?edit_id=<?php echo $row['train_id']; ?>" class="btn-small btn-primary">Edit</a>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this train?');">
                                <input type="hidden" name="operation" value="delete">
                                <input type="hidden" name="train_id" value="<?php echo $row['train_id']; ?>">
                                <button type="submit" name="action" value="generate" class="btn-small btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php 
                        endwhile;
                    else:
                    ?>
                    <tr>
                        <td colspan="6" style="text-align: center;">No trains found</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Delete Confirmation -->
    <?php if ($showQueryBox && isset($_POST['operation']) && $_POST['operation'] == 'delete'): ?>
    <div class="card">
        <h2>🗑️ Delete Train</h2>
        <form method="POST" action="">
            <div class="query-section">
                <h3>📝 Generated SQL Query:</h3>
                <textarea class="query-box" name="query" readonly><?php echo $generatedQuery; ?></textarea>
            </div>
            <p class="warning">⚠️ Warning: This will permanently delete the train and all related records!</p>
            <div class="btn-group">
                <button type="submit" name="action" value="execute" class="btn btn-danger">✓ Confirm Delete</button>
                <a href="trains.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
    <?php endif; ?>

</div>

<?php include '../includes/footer.php'; ?>
