<?php
require_once '../config/db.php';
$pageTitle = 'Passengers';

$message = '';
$generatedQuery = '';
$executedQuery = '';
$action = '';
$showQueryBox = false;
$currentOperation = 'create'; // Default tab
$search_results = null;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    $currentOperation = $_POST['operation'] ?? 'create'; // Remember which tab was active
    
    if ($action == 'generate') {
        $operation = $_POST['operation'] ?? '';
        $showQueryBox = true;
        
        if ($operation == 'create') {
            $passenger_name = escapeString($conn, $_POST['passenger_name']);
            $phone = escapeString($conn, $_POST['phone']);
            $email = escapeString($conn, $_POST['email']);
            
            // Server-side BD phone validation
            if (!preg_match('/^01[3-9][0-9]{8}$/', $phone)) {
                $message = '<div class="alert alert-error">✗ Invalid phone number format. Phone must be 11 digits starting with 01 (e.g., 01712345678)</div>';
                $showQueryBox = false;
                $currentOperation = 'create';
            } else {
                $generatedQuery = "INSERT INTO passengers (passenger_name, phone, email) VALUES ('$passenger_name', '$phone', '$email')";
            }
        }
        elseif ($operation == 'update') {
            $passenger_id = (int)$_POST['passenger_id'];
            $passenger_name = escapeString($conn, $_POST['passenger_name']);
            $phone = escapeString($conn, $_POST['phone']);
            $email = escapeString($conn, $_POST['email']);
            
            // Server-side BD phone validation
            if (!preg_match('/^01[3-9][0-9]{8}$/', $phone)) {
                $message = '<div class="alert alert-error">✗ Invalid phone number format. Phone must be 11 digits starting with 01 (e.g., 01712345678)</div>';
                $showQueryBox = false;
                $currentOperation = 'update';
            } else {
                $generatedQuery = "UPDATE passengers SET passenger_name='$passenger_name', phone='$phone', email='$email' WHERE passenger_id=$passenger_id";
            }
        }
        elseif ($operation == 'delete') {
            $passenger_id = (int)$_POST['passenger_id'];
            $generatedQuery = "DELETE FROM passengers WHERE passenger_id=$passenger_id";
        }
        elseif ($operation == 'search') {
            $search_field = $_POST['search_field'] ?? 'passenger_name';
            $search_value = escapeString($conn, $_POST['search_value']);
            $order_by = $_POST['order_by'] ?? 'passenger_id';
            $order_dir = $_POST['order_dir'] ?? 'DESC';
            $limit = (int)($_POST['limit'] ?? 10);
            
            if (!empty($search_value)) {
                $generatedQuery = "SELECT * FROM passengers WHERE $search_field LIKE '%$search_value%' ORDER BY $order_by $order_dir LIMIT $limit";
            } else {
                $generatedQuery = "SELECT * FROM passengers ORDER BY $order_by $order_dir LIMIT $limit";
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
                header("Location: passengers.php?success=1");
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
            
            // Check for specific error types
            $error_msg = $result['error'];
            if (strpos($error_msg, 'Duplicate entry') !== false) {
                if (strpos($error_msg, 'phone') !== false) {
                    $message = '<div class="alert alert-error">✗ Error: Phone number already exists! Each passenger must have a unique phone number. Please use a different number.</div>';
                } else {
                    $message = '<div class="alert alert-error">✗ Error: Duplicate entry detected. Please use unique values.</div>';
                }
            } elseif (strpos($error_msg, 'chk_phone_bd_format') !== false || strpos($error_msg, 'Check constraint') !== false) {
                $message = '<div class="alert alert-error">✗ Error: Invalid phone number format. Phone must be 11 digits starting with 01 (e.g., 01712345678). Bangladesh format required.</div>';
            } else {
                $message = '<div class="alert alert-error">✗ Error: ' . $error_msg . '</div>';
            }
            
            // For UPDATE/DELETE errors, preserve the operation and data
            $currentOperation = $operation;
        }
    }
}

// Check for redirect success message
if (isset($_GET['success'])) {
    $message = '<div class="alert alert-success">✓ Operation completed successfully!</div>';
}

// Get all passengers for display
$sql_view = "SELECT * FROM passengers ORDER BY passenger_id DESC LIMIT 50";
$passengers_result = $search_results ?? $conn->query($sql_view);
$list_title = $search_results ? "Search Results" : "All Passengers";

// Get passenger for edit if edit_id is provided
$edit_passenger = null;
if (isset($_GET['edit_id'])) {
    $edit_id = (int)$_GET['edit_id'];
    $edit_result = $conn->query("SELECT * FROM passengers WHERE passenger_id = $edit_id");
    $edit_passenger = $edit_result->fetch_assoc();
}
?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/sidebar.php'; ?>

<div class="main-content">
    <div class="page-header">
        <h1>👤 Passengers Management</h1>
    </div>

    <?php echo $message; ?>

    <!-- Single Operations Card -->
    <div class="card">
        <h2>🔧 Passenger Operations</h2>
        
        <!-- Operation Selector -->
        <div class="form-group">
            <label><strong>Select Operation:</strong></label>
            <select id="operation-selector" onchange="switchOperation(this.value)" class="operation-select">
                <option value="create" <?php echo $currentOperation == 'create' ? 'selected' : ''; ?>>➕ Add New Passenger</option>
                <option value="search" <?php echo $currentOperation == 'search' ? 'selected' : ''; ?>>🔍 Search & Filter Passengers</option>
            </select>
        </div>

        <!-- CREATE Operation Form -->
        <form method="POST" action="" id="form-create" style="display: <?php echo $currentOperation == 'create' ? 'block' : 'none'; ?>;">
            <input type="hidden" name="operation" value="create">
            <div class="form-row">
                <div class="form-group">
                    <label>Passenger Name *</label>
                    <input type="text" name="passenger_name" value="<?php echo $_POST['passenger_name'] ?? ''; ?>" required placeholder="e.g., Md. Kamal Hossain">
                </div>
                <div class="form-group">
                    <label>Phone * <small style="color: #666; font-weight: normal;">(Must be unique)</small></label>
                    <input type="text" 
                           name="phone" 
                           value="<?php echo $_POST['phone'] ?? ''; ?>" 
                           required 
                           pattern="01[3-9][0-9]{8}" 
                           title="Phone must be 11 digits starting with 01 (e.g., 01712345678)" 
                           maxlength="11" 
                           placeholder="01XXXXXXXXX">
                    <small style="color: #666; font-size: 12px;">💡 Bangladesh format: 01XXXXXXXXX (11 digits)</small>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" value="<?php echo $_POST['email'] ?? ''; ?>" placeholder="e.g., passenger@email.com">
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
                        <option value="passenger_name">Passenger Name</option>
                        <option value="phone">Phone</option>
                        <option value="email">Email</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Search Value</label>
                    <input type="text" name="search_value" placeholder="Leave empty for all">
                </div>
                <div class="form-group">
                    <label>Order By</label>
                    <select name="order_by">
                        <option value="passenger_id">Passenger ID</option>
                        <option value="passenger_name">Passenger Name</option>
                        <option value="phone">Phone</option>
                        <option value="created_at">Created Date</option>
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

    <!-- Update Passenger -->
    <?php if ($edit_passenger): ?>
    <div class="card">
        <h2>✏️ Update Passenger #<?php echo $edit_passenger['passenger_id']; ?></h2>
        <form method="POST" action="">
            <input type="hidden" name="operation" value="update">
            <input type="hidden" name="passenger_id" value="<?php echo $edit_passenger['passenger_id']; ?>">
            <div class="form-row">
                <div class="form-group">
                    <label>Passenger Name *</label>
                    <input type="text" name="passenger_name" value="<?php echo $edit_passenger['passenger_name']; ?>" required>
                </div>
                <div class="form-group">
                    <label>Phone *</label>
                    <input type="text" 
                           name="phone" 
                           value="<?php echo $edit_passenger['phone']; ?>" 
                           required 
                           pattern="01[3-9][0-9]{8}" 
                           title="Phone must be 11 digits starting with 01 (e.g., 01712345678)" 
                           maxlength="11" 
                           placeholder="01XXXXXXXXX">
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" value="<?php echo $edit_passenger['email']; ?>">
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
                <a href="passengers.php" class="btn btn-secondary">Cancel</a>
            </div>
            <?php else: ?>
            <div class="btn-group">
                <button type="submit" name="action" value="generate" class="btn btn-primary">Generate UPDATE Query</button>
                <a href="passengers.php" class="btn btn-secondary">Cancel</a>
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

    <!-- Passengers List (All or Filtered) -->
    <div class="card">
        <h2>📋 <?php echo $list_title; ?></h2>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Passenger Name</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if ($passengers_result->num_rows > 0):
                        while($row = $passengers_result->fetch_assoc()): 
                    ?>
                    <tr>
                        <td><?php echo $row['passenger_id']; ?></td>
                        <td><?php echo $row['passenger_name']; ?></td>
                        <td><?php echo $row['phone']; ?></td>
                        <td><?php echo $row['email'] ?? '-'; ?></td>
                        <td><?php echo date('Y-m-d H:i', strtotime($row['created_at'])); ?></td>
                        <td>
                            <a href="?edit_id=<?php echo $row['passenger_id']; ?>" class="btn-small btn-primary">Edit</a>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this passenger?');">
                                <input type="hidden" name="operation" value="delete">
                                <input type="hidden" name="passenger_id" value="<?php echo $row['passenger_id']; ?>">
                                <button type="submit" name="action" value="generate" class="btn-small btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php 
                        endwhile;
                    else:
                    ?>
                    <tr>
                        <td colspan="6" style="text-align: center;">No passengers found</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Delete Confirmation -->
    <?php if ($showQueryBox && isset($_POST['operation']) && $_POST['operation'] == 'delete'): ?>
    <div class="card">
        <h2>🗑️ Delete Passenger</h2>
        <form method="POST" action="">
            <div class="query-section">
                <h3>📝 Generated SQL Query:</h3>
                <textarea class="query-box" name="query" readonly><?php echo $generatedQuery; ?></textarea>
            </div>
            <p class="warning">⚠️ Warning: This will permanently delete the passenger and all related bookings!</p>
            <div class="btn-group">
                <button type="submit" name="action" value="execute" class="btn btn-danger">✓ Confirm Delete</button>
                <a href="passengers.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
    <?php endif; ?>

</div>

<?php include '../includes/footer.php'; ?>
