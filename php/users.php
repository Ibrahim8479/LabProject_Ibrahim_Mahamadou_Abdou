<?php
// Enable error display for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if auth_check.php exists
if (!file_exists('auth_check.php')) {
    die('Error: auth_check.php not found in current directory: ' . __DIR__);
}

require_once 'auth_check.php';

    header('Location: login.php');
    exit();


try {
    $conn = getDBConnection();
    
    if (!$conn) {
        throw new Exception('Database connection failed');
    }
    
    $users_query = "SELECT u.user_id, u.first_name, u.last_name, u.email, u.role, u.dob, u.created_at 
                    FROM users u 
                    ORDER BY u.created_at DESC";
    
    $users = $conn->query($users_query);
    
    if (!$users) {
        throw new Exception('Query failed: ' . $conn->error);
    }
    
} catch (Exception $e) {
    die('Database Error: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management</title>
    <link rel="stylesheet" href="../css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <?php 
    if (file_exists('navbar.php')) {
        include 'navbar.php'; 
    } else {
        echo '<div style="padding: 1rem; background: #f44336; color: white;">Warning: navbar.php not found</div>';
    }
    ?>
    
    <div class="container">
        <h1>User Management</h1>
        
        <div class="table-section">
            <h2>All Users (<?php echo $users->num_rows; ?>)</h2>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Date of Birth</th>
                        <th>Registered</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($users->num_rows > 0): ?>
                        <?php while ($user = $users->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $user['user_id']; ?></td>
                            <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><span class="status-badge"><?php echo ucfirst($user['role']); ?></span></td>
                            <td><?php echo $user['dob'] ? date('M d, Y', strtotime($user['dob'])) : '-'; ?></td>
                            <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center;">No users found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <script src="../js/logout.js"></script>
</body>
</html>
<?php
if (isset($conn)) {
    $conn->close();
}
?>