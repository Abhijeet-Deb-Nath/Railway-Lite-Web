<?php
require_once '../config/db.php';
$pageTitle = 'Passengers';

$message = '';
$generatedQuery = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action == 'generate') {
        $operation = $_POST['operation'] ?? '';
        
        if ($operation == 'create') {
            $passenger_name = escapeString($conn, $_POST['passenger_name']);
            $phone = escapeString($conn, $_POST['phone']);
            $email = escapeString($conn, $_POST['email']);
            $generatedQuery = "INSERT INTO passengers (passenger_name, phone, email) VALUES ('$passenger_name', '$phone', '$email')";
        }
        elseif ($operation == 'update') {
            $passenger_id = (int)$_POST['passenger_id'];
            $passenger_name = escapeString($conn, $_POST['passenger_name']);
            $phone = escapeString($conn, $_POST['phone']);
            $email = escapeString($conn, $_POST['email']);
            $generatedQuery = "UPDATE passengers SET passenger_name='$passenger_name', phone='$phone', email='$email' WHERE passenger_id=$passenger_id";
        }
        elseif ($operation == 'delete') {
            $passenger_id = (int)$_POST['passenger_id'];
            $generatedQuery = "DELETE FROM passengers WHERE passenger_id=$passenger_id";
        }
        elseif ($operation == 'search') {
            $search_field = $_POST['search_field'];
            $search_value = escapeString($conn, $_POST['search_value']);
            $order_by = $_POST['order_by'] ?? 'passenger_id';
            $order_dir = $_POST['order_dir'] ?? 'DESC';
            $limit = (int)($_POST['limit'] ?? 10);
            
            $generatedQuery = "SELECT * FROM passengers WHERE $search_field LIKE '%$search_value%' ORDER BY $order_by $order_dir LIMIT $limit";
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

$sql_view = "SELECT * FROM passengers ORDER BY passenger_id DESC LIMIT 50";
$passengers_result = $conn->query($sql_view);
?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/sidebar.php'; ?>

<div class="main-content">
    <div class="page-header">
        <h1>👤 Passengers Management</h1>
    </div>

    <?php echo $message; ?>

    <div class="card">
        <h2>➕ Add New Passenger</h2>
        <form method="POST" action="">
            <input type="hidden" name="operation" value="create">
            <div class="form-row">
                <div class="form-group">
                    <label>Passenger Name *</label>
                    <input type="text" name="passenger_name" required>
                </div>
                <div class="form-group">
                    <label>Phone *</label>
                    <input type="text" name="phone" required maxlength="15">
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email">
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
        <h2>🔍 Search Passengers</h2>
        <form method="POST" action="">
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
                    <input type="text" name="search_value" required>
                </div>
                <div class="form-group">
                    <label>Order By</label>
                    <select name="order_by">
                        <option value="passenger_id">Passenger ID</option>
                        <option value="passenger_name">Name</option>
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

    <div class="card">
        <h2>📋 All Passengers</h2>
        <div class="query-section">
            <h3>📝 Query Used:</h3>
            <textarea class="query-box" readonly><?php echo $sql_view; ?></textarea>
        </div>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($passengers_result->num_rows > 0): ?>
                        <?php while($row = $passengers_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['passenger_id']; ?></td>
                            <td><?php echo $row['passenger_name']; ?></td>
                            <td><?php echo $row['phone']; ?></td>
                            <td><?php echo $row['email'] ?? 'N/A'; ?></td>
                            <td><?php echo date('Y-m-d H:i', strtotime($row['created_at'])); ?></td>
                            <td class="action-buttons">
                                <button onclick="editPassenger(<?php echo htmlspecialchars(json_encode($row)); ?>)" class="btn btn-primary btn-small">Edit</button>
                                <button onclick="deletePassenger(<?php echo $row['passenger_id']; ?>, '<?php echo $row['passenger_name']; ?>')" class="btn btn-danger btn-small">Delete</button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center;">No passengers found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>✏️ Edit Passenger</h2>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="operation" value="update">
                <input type="hidden" name="passenger_id" id="edit_passenger_id">
                <div class="form-group">
                    <label>Passenger Name *</label>
                    <input type="text" name="passenger_name" id="edit_passenger_name" required>
                </div>
                <div class="form-group">
                    <label>Phone *</label>
                    <input type="text" name="phone" id="edit_phone" required maxlength="15">
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" id="edit_email">
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
                <h2>🗑️ Delete Passenger</h2>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="operation" value="delete">
                <input type="hidden" name="passenger_id" id="delete_passenger_id">
                <p>Are you sure you want to delete passenger: <strong id="delete_passenger_name"></strong>?</p>
                
                <div class="btn-group">
                    <button type="submit" name="action" value="generate" class="btn btn-primary">Generate DELETE Query</button>
                    <button type="button" onclick="closeModal()" class="btn btn-secondary">Cancel</button>
                </div>
            </form>
        </div>
    </div>

</div>

<script>
function editPassenger(passenger) {
    document.getElementById('edit_passenger_id').value = passenger.passenger_id;
    document.getElementById('edit_passenger_name').value = passenger.passenger_name;
    document.getElementById('edit_phone').value = passenger.phone;
    document.getElementById('edit_email').value = passenger.email || '';
    document.getElementById('editModal').classList.add('active');
}

function deletePassenger(id, name) {
    document.getElementById('delete_passenger_id').value = id;
    document.getElementById('delete_passenger_name').textContent = name;
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
