<?php
require_once '../config.php';
requireAdmin();
$conn = getDBConnection();

if (isset($_GET['id'])) {
    $user_id = intval($_GET['id']);
    $stmt = $conn->prepare("DELETE FROM employees WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    
    if ($stmt->execute()) {
        header("Location: employee_crud.php?success=deleted");
    } else {
        header("Location: employee_crud.php?error=1");
    }
    exit;
} else {
    header("Location: employee_crud.php");
    exit;
}
?>
