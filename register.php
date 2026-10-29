<?php
session_start();
include "config/db.php";

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? 'customer';

    if ($full_name === "" || $email === "" || $password === "") {
        $error = "All fields are required.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } elseif (!in_array($role, ['customer', 'pharmacist', 'admin'])) {
        $error = "Invalid role selected.";
    } else {
        $check = $conn->prepare("
            SELECT user_id 
            FROM users 
            WHERE email = ?
        ");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $error = "An account with this email already exists.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("
                INSERT INTO users (full_name, email, password, role)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->bind_param("ssss", $full_name, $email, $hashed_password, $role);

            if ($stmt->execute()) {
                $success = "Account created successfully. You can now log in.";
            } else {
                $error = "Registration failed. Please try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Pharmacy Management System</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<main class="form-page">
    <h2>Create Account</h2>

    <?php if ($error): ?>
        <p class="error"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <?php if ($success): ?>
        <p class="success"><?php echo htmlspecialchars($success); ?></p>
    <?php endif; ?>

    <form method="POST" action="register.php">
        <label>Full Name</label>
        <input type="text" name="full_name" required>

        <label>Email</label>
        <input type="email" name="email" required>

        <label>Password</label>
        <input type="password" name="password" minlength="6" required>

        <label>Confirm Password</label>
        <input type="password" name="confirm_password" minlength="6" required>

        <label>Account Type</label>
        <select name="role" required>
            <option value="customer">Customer</option>
            <option value="pharmacist">Pharmacist</option>
            <option value="admin">Administrator</option>
        </select>

        <button type="submit">Register</button>
    </form>

    <p>
        Already have an account?
        <a href="login.php">Login here</a>
    </p>
</main>

</body>
</html>