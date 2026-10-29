<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $medicine_id = intval($_POST['medicine_id']);
    $change_type = $_POST['change_type'];
    $quantity = intval($_POST['quantity']);

    if ($quantity <= 0) {
        $message = "Quantity must be greater than zero.";
    } else {
        if ($change_type == "add") {
            $sql = "UPDATE medicines 
                    SET stock_quantity = stock_quantity + ? 
                    WHERE medicine_id = ?";
        } else {
            $sql = "UPDATE medicines 
                    SET stock_quantity = GREATEST(stock_quantity - ?, 0) 
                    WHERE medicine_id = ?";
        }

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $quantity, $medicine_id);

        if ($stmt->execute()) {
            $message = "Stock updated successfully.";
        } else {
            $message = "Error updating stock.";
        }
    }
}

$medicines = $conn->query("
    SELECT m.medicine_id, m.medicine_name, m.stock_quantity, m.reorder_level, p.name AS pharmacy_name
    FROM medicines m
    LEFT JOIN pharmacies p ON m.pharmacy_id = p.pharmacy_id
    ORDER BY m.medicine_name
");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Update Stock</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<h2>Update Medicine Stock</h2>

<?php if ($message): ?>
    <p class="message"><?php echo $message; ?></p>
<?php endif; ?>

<form method="POST">
    <label>Select Medicine</label>
    <select name="medicine_id" required>
        <option value="">-- Select medicine --</option>
        <?php while ($row = $medicines->fetch_assoc()): ?>
            <option value="<?php echo $row['medicine_id']; ?>">
                <?php echo htmlspecialchars($row['medicine_name']); ?>
                (Stock: <?php echo $row['stock_quantity']; ?>)
            </option>
        <?php endwhile; ?>
    </select>

    <label>Action</label>
    <select name="change_type" required>
        <option value="add">Add stock</option>
        <option value="reduce">Reduce stock</option>
    </select>

    <label>Quantity</label>
    <input type="number" name="quantity" min="1" required>

    <button type="submit">Update Stock</button>
</form>

<h3>Current Stock</h3>
<table border="1">
    <tr>
        <th>Medicine</th>
        <th>Pharmacy</th>
        <th>Stock</th>
        <th>Status</th>
    </tr>

    <?php
    $medicines->data_seek(0);
    while ($row = $medicines->fetch_assoc()):
        $status = $row['stock_quantity'] <= 0 ? "Out of stock"
                : ($row['stock_quantity'] <= $row['reorder_level'] ? "Low stock" : "Available");
    ?>
    <tr>
        <td><?php echo htmlspecialchars($row['medicine_name']); ?></td>
        <td><?php echo htmlspecialchars($row['pharmacy_name'] ?? 'Not assigned'); ?></td>
        <td><?php echo $row['stock_quantity']; ?></td>
        <td><?php echo $status; ?></td>
    </tr>
    <?php endwhile; ?>
</table>

</body>
</html>