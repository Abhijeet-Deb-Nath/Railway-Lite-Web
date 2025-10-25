# Station Page Pattern - Applied to All CRUD Pages

## Key Modifications from Your Stations Page:

### 1. **Consolidated Operations Card**
- Single card with dropdown selector for operations
- Add and Search forms in one place
- JavaScript to switch between forms

### 2. **Tab Persistence** 
```php
$currentOperation = 'create'; // Default tab
// Remember tab after form submission
$currentOperation = $_POST['operation'] ?? 'create';
```

### 3. **Executed Query Display**
```php
$executedQuery = '';
// After execution:
$executedQuery = $generatedQuery;
// Then display in separate card:
<?php if (!empty($executedQuery)): ?>
<div class="card">
    <h2>✅ Executed Query</h2>
    ...
</div>
<?php endif; ?>
```

### 4. **Search Results Replace List**
```php
$search_results = null;
// For SELECT queries:
$search_results = $conn->query($generatedQuery);
// Use in display:
$trains_result = $search_results ?? $conn->query($sql_view);
$list_title = $search_results ? "Search Results" : "All Trains";
```

### 5. **Redirect After INSERT/UPDATE/DELETE**
```php
if (stripos($generatedQuery, 'INSERT') === 0 || 
    stripos($generatedQuery, 'UPDATE') === 0 || 
    stripos($generatedQuery, 'DELETE') === 0) {
    header("Location: trains.php?success=1");
    exit;
}
```

### 6. **Success Message from Redirect**
```php
if (isset($_GET['success'])) {
    $message = '<div class="alert alert-success">✓ Operation completed successfully!</div>';
}
```

### 7. **Search by Specific Field (Dropdown)**
- Stations: Search by City (dropdown of existing cities)
- Trains: Search by Train Type (dropdown of types)
- Each page has context-appropriate search

### 8. **JavaScript for Tab Switching**
```javascript
function switchOperation(operation) {
    document.getElementById('form-create').style.display = 'none';
    document.getElementById('form-search').style.display = 'none';
    document.getElementById('form-' + operation).style.display = 'block';
}

// On page load, restore correct tab
window.addEventListener('DOMContentLoaded', function() {
    var currentOp = '<?php echo $currentOperation; ?>';
    switchOperation(currentOp);
    document.getElementById('operation-selector').value = currentOp;
});
```

---

## Pages Status:

✅ **Stations** - Fully updated by user
✅ **Trains** - Just updated with all modifications
⏳ **Routes** - Needs update
⏳ **Trips** - Needs update
⏳ **Passengers** - Needs update
⏳ **Bookings** - Needs update

---

## Next: Apply to Remaining Pages

Each page will have:
1. Operation selector dropdown
2. Tab persistence
3. Executed query display
4. Search replaces list
5. Redirect after mutations
6. Context-specific search filters
