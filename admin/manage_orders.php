<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'pharmacist'])) {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $order_id = intval($_POST['order_id']);
    $action = $_POST['action'];

    $conn->begin_transaction();

    try {
        $order = $conn->prepare("
            SELECT order_id, status
            FROM orders
            WHERE order_id = ?
            FOR UPDATE
        ");
        $order->bind_param("i", $order_id);
        $order->execute();
        $order_data = $order->get_result()->fetch_assoc();

        if (!$order_data) {
            throw new Exception("Order not found.");
        }

        if ($action === "approve") {
            $status = "approved";
        } elseif ($action === "reject" || $action === "cancel") {
            $status = $action === "reject" ? "rejected" : "cancelled";
        } elseif ($action === "deliver") {
            $status = "delivered";
        } else {
            throw new Exception("Invalid action.");
        }

        // Return stock if order is rejected or cancelled
        if ($status === "rejected" || $status === "cancelled") {
            if (in_array($order_data['status'], ['pending', 'approved'])) {
                $items = $conn->prepare("
                    SELECT medicine_id, quantity
                    FROM order_items
                    WHERE order_id = ?
                ");
                $items->bind_param("i", $order_id);
                $items->execute();
                $result = $items->get_result();

                while ($item = $result->fetch_assoc()) {
                    $restore = $conn->prepare("
                        UPDATE medicines
                        SET stock_quantity = stock_quantity + ?
                        WHERE medicine_id = ?
                    ");
                    $restore->bind_param("ii", $item['quantity'], $item['medicine_id']);
                    $restore->execute();
                }
            }
        }

        $update = $conn->prepare("
            UPDATE orders
            SET status = ?
            WHERE order_id = ?
        ");
        $update->bind_param("si", $status, $order_id);
        $update->execute();

        $conn->commit();

        $message = "Order updated successfully.";

    } catch (Exception $e) {
        $conn->rollback();
        $message = $e->getMessage();
    }
}

$orders = $conn->query("
    SELECT 
        o.order_id,
        o.order_number,
        o.total_amount,
        o.status,
        o.order_date,
        u.full_name,
        p.name AS pharmacy_name
    FROM orders o
    JOIN users u ON o.user_id = u.user_id
    JOIN pharmacies p ON o.pharmacy_id = p.pharmacy_id
    ORDER BY o.order_date DESC
");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Orders</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<h2>Manage Orders</h2>

<?php if (!empty($message)): ?>
    <p><?php echo htmlspecialchars($message); ?></p>
<?php endif; ?>

<table border="1">
    <tr>
        <th>Order No.</th>
        <th>Customer</th>
        <th>Pharmacy</th>
        <th>Total</th>
        <th>Status</th>
        <th>Action</th>
    </tr>

    <?php while ($order = $orders->fetch_assoc()): ?>
    <tr>
        <td><?php echo htmlspecialchars($order['order_number']); ?></td>
        <td><?php echo htmlspecialchars($order['full_name']); ?></td>
        <td><?php echo htmlspecialchars($order['pharmacy_name']); ?></td>
        <td>KES <?php echo $order['total_amount']; ?></td>
        <td><?php echo htmlspecialchars($order['status']); ?></td>
        <td>
            <form method="POST">
                <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">

                <?php if ($order['status'] === 'pending'): ?>
                    <button name="action" value="approve">Approve</button>
                    <button name="action" value="reject">Reject</button>
                <?php elseif ($order['status'] === 'approved'): ?>
                    <button name="action" value="deliver">Mark Delivered</button>
                    <button name="action" value="cancel">Cancel</button>
                <?php else: ?>
                    No action
                <?php endif; ?>
            </form>
        </td>
    </tr>
    <?php endwhile; ?>
</table>

</body>
</html>