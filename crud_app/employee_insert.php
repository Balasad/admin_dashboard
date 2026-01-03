<?php
require_once '../config.php';
requireAdmin();
$conn = getDBConnection();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'] ?? null;
    $address = $_POST['address'];
    $role = $_POST['role'];
    
    // Get current admin's user_id for created_by tracking
    // Check if admin_id exists in employees table, otherwise set to null
    $created_by = null;
    
    if (isset($_SESSION['admin_id'])) {
        $check = $conn->query("SELECT user_id FROM employees WHERE user_id = " . intval($_SESSION['admin_id']));
        if ($check && $check->num_rows > 0) {
            $created_by = $_SESSION['admin_id'];
        }
    }

    $stmt = $conn->prepare(
        "INSERT INTO employees (name, email, phone, address, role, created_by) VALUES (?, ?, ?, ?, ?, ?)"
    );

    $stmt->bind_param("sssssi", $name, $email, $phone, $address, $role, $created_by);

    if ($stmt->execute()) {
        header("Location: employee_crud.php?success=added");
        exit;
    } else {
        header("Location: employee_crud.php?error=1");
        exit;
    }
} else {
    header("Location: employee_crud.php");
    exit;
}
?>