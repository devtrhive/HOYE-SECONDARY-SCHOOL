<?php
session_start();

// Import environmental parameters from the hidden parent directory config file
require_once __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = $_POST['username'];
    $pass = $_POST['password'];

    // Validates matching values dynamically via parameters defined in config.php
    if ($user === ADMIN_USER && $pass === ADMIN_PASS) {
        $_SESSION['admin_id'] = 1;
        $_SESSION['admin_name'] = 'System Admin';
        header("Location: index.php"); 
        exit();
    } else {
        echo "<script>alert('Invalid Credentials'); window.location='login.php';</script>";
    }
}
?>