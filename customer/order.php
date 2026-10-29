<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$pharmacy_id = intval($_GET['pharmacy_id'] ?? 0);
$medicine_name = trim($_GET['medicine'] ?? '');

$medicine = $conn->prepare("
    SELECT medicine_id, medicine_name, price, stock_quantity 
    FROM medicines 
    WHERE pharmacy_id = ? AND medicine_name LIKE ?
    LIMIT 1
");
$search = "%" . $medicine_name . "%";
$medicine->bind_param("is", $pharmacy_id, $search);
$medicine->execute();
$medicine_result = $medicine->get_result();
$med = $medicine_result->fetch_assoc();

if (!$med) {
    die("Medicine not found.");
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Order Medicine</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<h2>Order: <?php echo htmlspecialchars($med['medicine_name']); ?></h2>

<p>Available stock: <?php echo $med['stock_quantity']; ?></p>
<p>Price per unit: KES <?php echo $med['price']; ?></p>

<form action="../api/place_order.php" method="POST">
    <input type="hidden" name="medicine_id" value="<?php echo $med['medicine_id']; ?>">
    <input type="hidden" name="pharmacy_id" value="<?php echo $pharmacy_id; ?>">

    <label>Quantity</label>
    <input type="number" name="quantity" min="1" 
           max="<?php echo $med['stock_quantity']; ?>" required>

    <button type="submit">Place Order</button>
</form>

</body>
</html>