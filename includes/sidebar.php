<nav class="sidebar">
    <div class="sidebar-header">
        <h2>🚆 Railway Admin</h2>
    </div>
    <ul class="sidebar-menu">
        <li><a href="dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">📊 Dashboard</a></li>
        <li><a href="stations.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'stations.php' ? 'active' : ''; ?>">🚉 Stations</a></li>
        <li><a href="trains.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'trains.php' ? 'active' : ''; ?>">🚂 Trains</a></li>
        <li><a href="routes.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'routes.php' ? 'active' : ''; ?>">🛤️ Routes</a></li>
        <li><a href="trips.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'trips.php' ? 'active' : ''; ?>">📅 Trips</a></li>
        <li><a href="passengers.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'passengers.php' ? 'active' : ''; ?>">👤 Passengers</a></li>
        <li><a href="bookings.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'bookings.php' ? 'active' : ''; ?>">🎫 Bookings</a></li>
        <li><a href="reports.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>">📈 Advanced Queries</a></li>
    </ul>
</nav>
