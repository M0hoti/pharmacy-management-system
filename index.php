<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pharmacy Management System</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<header>
    <h1>Pharmacy Management System</h1>
</header>

<nav>
    <a href="index.php">Home</a>

    <?php if (isset($_SESSION['user_id'])): ?>
        <a href="customer/search.php">Find Medicine</a>
        <a href="logout.php">Logout</a>

        <?php if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'pharmacist'): ?>
            <a href="admin/update_stock.php">Update Stock</a>
            <a href="admin/manage_orders.php">Manage Orders</a>
        <?php endif; ?>

    <?php else: ?>
        <a href="login.php">Login</a>
        <a href="register.php">Register</a>
    <?php endif; ?>
</nav>

<main>
    <h2>Welcome to the Pharmacy Management System</h2>

    <p>
        This system allows customers to search for medicines, locate the nearest
        pharmacy, place medicine orders, and allows pharmacy staff to manage stock
        and customer orders.
    </p>

    <?php if (isset($_SESSION['user_id'])): ?>
        <p>Welcome back, <strong><?php echo htmlspecialchars($_SESSION['full_name']); ?></strong>.</p>

        <a class="button" href="customer/search.php">Find Medicine Near You</a>
    <?php else: ?>
        <a class="button" href="register.php">Create an Account</a>
        <a class="button" href="login.php">Login</a>
    <?php endif; ?>
</main>

</body>
</html>