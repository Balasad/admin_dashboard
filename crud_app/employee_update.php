<?php
require_once '../config.php';
requireAdmin();
$conn = getDBConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_POST['user_id'];
    $name    = $_POST['name'];
    $email   = $_POST['email'];
    $phone   = $_POST['phone'] ?? null;
    $address = $_POST['address'];
    $role    = $_POST['role'];
    
    // Get current admin's user_id and role for tracking who updated
    $updated_by = null;
    $updated_by_role = null;
    
    if (isset($_SESSION['admin_id'])) {
        // Fetch the user_id and role from employees table
        $check = $conn->query("SELECT user_id, role FROM employees WHERE user_id = " . intval($_SESSION['admin_id']));
        if ($check && $check->num_rows > 0) {
            $admin_data = $check->fetch_assoc();
            $updated_by = $admin_data['user_id'];
            $updated_by_role = $admin_data['role'];
        }
    }
    
    // Fallback: if session has role but no admin_id in employees table
    if ($updated_by_role === null && isset($_SESSION['role'])) {
        $updated_by_role = $_SESSION['role'];
    }
    
    // Final fallback: assume admin
    if ($updated_by_role === null) {
        $updated_by_role = 'admin';
    }

    $stmt = $conn->prepare(
        "UPDATE employees 
         SET name=?, email=?, phone=?, address=?, role=?, updated_by=?, updated_by_role=?, updated_at=NOW()
         WHERE user_id=?"
    );

    // 8 parameters - s s s s s i s i
    $stmt->bind_param("sssssiis", $name, $email, $phone, $address, $role, $updated_by, $updated_by_role, $user_id);

    if ($stmt->execute()) {
        header("Location: employee_crud.php?success=updated");
        exit;
    } else {
        echo "Update failed: " . $stmt->error;
    }
}
?>
