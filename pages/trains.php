<?php
require_once '../config/db.php';
$pageTitle = 'Trains';

$message = '';
$generatedQuery = '';
$action = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action == 'generate') {
        $operation = $_POST['operation'] ?? '';
        
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
            $search_field = $_POST['search_field'];
            $search_value = escapeString($conn, $_POST['search_value']);
            $order_by = $_POST['order_by'] ?? 'train_id';
            $order_dir = $_POST['order_dir'] ?? 'ASC';
            $limit = (int)($_POST['limit'] ?? 10);
            
            if ($search_field == 'train_type') {
                $generatedQuery = "SELECT * FROM trains WHERE $search_field = '$search_value' ORDER BY $order_by $order_dir LIMIT $limit";
            } else {
                $generatedQuery = "SELECT * FROM trains WHERE $search_field LIKE '%$search_value%' ORDER BY $order_by $order_dir LIMIT $limit";
            }
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

$sql_view = "SELECT * FROM trains ORDER BY train_id DESC";
$trains_result = $conn->query($sql_view);
?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/sidebar.php'; ?>

<div class="main-content">
    <div class="page-header">
        <h1>🚂 Trains Management</h1>
    </div>

    <?php echo $message; ?>

    <div class="card">
        <h2>➕ Add New Train</h2>
        <form method="POST" action="">
            <input type="hidden" name="operation" value="create">
            <div class="form-row">
                <div class="form-group">
                    <label>Train Name *</label>
                    <input type="text" name="train_name" required>
                </div>
                <div class="form-group">
                    <label>Train Type *</label>
                    <select name="train_type" required>
                        <option value="">Select Type</option>
                        <option value="Express">Express</option>
                        <option value="Intercity">Intercity</option>
                        <option value="Local">Local</option>
                        <option value="Mail">Mail</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Total Seats *</label>
                    <input type="number" name="total_seats" value="200" min="1" required>
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
        <h2>🔍 Search & Filter Trains (WHERE, ORDER BY, LIMIT)</h2>
        <form method="POST" action="">
            <input type="hidden" name="operation" value="search">
            <div class="form-row">
                <div class="form-group">
                    <label>Search Field</label>
                    <select name="search_field">
                        <option value="train_name">Train Name</option>
                        <option value="train_type">Train Type</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Search Value</label>
                    <input type="text" name="search_value" required>
                </div>
                <div class="form-group">
                    <label>Order By</label>
                    <select name="order_by">
                        <option value="train_id">Train ID</option>
                        <option value="train_name">Train Name</option>
                        <option value="train_type">Train Type</option>
                        <option value="total_seats">Total Seats</option>
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
        <h2>📋 All Trains</h2>
        <div class="query-section">
            <h3>📝 Query Used:</h3>
            <textarea class="query-box" readonly><?php echo $sql_view; ?></textarea>
        </div>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Train Name</th>
                        <th>Train Type</th>
                        <th>Total Seats</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($trains_result->num_rows > 0): ?>
                        <?php while($row = $trains_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['train_id']; ?></td>
                            <td><?php echo $row['train_name']; ?></td>
                            <td><?php echo $row['train_type']; ?></td>
                            <td><?php echo $row['total_seats']; ?></td>
                            <td><?php echo date('Y-m-d H:i', strtotime($row['created_at'])); ?></td>
                            <td class="action-buttons">
                                <button onclick="editTrain(<?php echo htmlspecialchars(json_encode($row)); ?>)" class="btn btn-primary btn-small">Edit</button>
                                <button onclick="deleteTrain(<?php echo $row['train_id']; ?>, '<?php echo $row['train_name']; ?>')" class="btn btn-danger btn-small">Delete</button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center;">No trains found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>✏️ Edit Train</h2>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="operation" value="update">
                <input type="hidden" name="train_id" id="edit_train_id">
                <div class="form-group">
                    <label>Train Name *</label>
                    <input type="text" name="train_name" id="edit_train_name" required>
                </div>
                <div class="form-group">
                    <label>Train Type *</label>
                    <select name="train_type" id="edit_train_type" required>
                        <option value="Express">Express</option>
                        <option value="Intercity">Intercity</option>
                        <option value="Local">Local</option>
                        <option value="Mail">Mail</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Total Seats *</label>
                    <input type="number" name="total_seats" id="edit_total_seats" min="1" required>
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
                <h2>🗑️ Delete Train</h2>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="operation" value="delete">
                <input type="hidden" name="train_id" id="delete_train_id">
                <p>Are you sure you want to delete train: <strong id="delete_train_name"></strong>?</p>
                
                <div class="btn-group">
                    <button type="submit" name="action" value="generate" class="btn btn-primary">Generate DELETE Query</button>
                    <button type="button" onclick="closeModal()" class="btn btn-secondary">Cancel</button>
                </div>
            </form>
        </div>
    </div>

</div>

<script>
function editTrain(train) {
    document.getElementById('edit_train_id').value = train.train_id;
    document.getElementById('edit_train_name').value = train.train_name;
    document.getElementById('edit_train_type').value = train.train_type;
    document.getElementById('edit_total_seats').value = train.total_seats;
    document.getElementById('editModal').classList.add('active');
}

function deleteTrain(id, name) {
    document.getElementById('delete_train_id').value = id;
    document.getElementById('delete_train_name').textContent = name;
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
