<?php
session_start();
include "../config/db.php";

header("Content-Type: application/json");

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["error" => "Please log in first."]);
    exit();
}

$user_id = $_SESSION['user_id'];
$pharmacy_id = intval($_POST['pharmacy_id'] ?? 0);
$delivery_address = trim($_POST['delivery_address'] ?? '');
$customer_phone = trim($_POST['customer_phone'] ?? '');

// Expected format:
// medicines = JSON array: [{"medicine_id":1,"quantity":2},{"medicine_id":3,"quantity":1}]
$medicines_json = $_POST['medicines'] ?? '[]';
$medicines = json_decode($medicines_json, true);

if ($pharmacy_id <= 0 || empty($medicines) || $delivery_address === "" || $customer_phone === "") {
    echo json_encode(["error" => "All order details are required."]);
    exit();
}

$conn->begin_transaction();

try {
    // 1. Generate a unique order number
    $order_number = "PH-" . strtoupper(uniqid());

    // 2. Check stock and calculate total
    $total_amount = 0;
    $order_items = [];

    foreach ($medicines as $item) {
        $medicine_id = intval($item['medicine_id'] ?? 0);
        $quantity = intval($item['quantity'] ?? 0);

        if ($medicine_id <= 0 || $quantity <= 0) {
            throw new Exception("Invalid medicine or quantity.");
        }

        $stmt = $conn->prepare("
            SELECT medicine_name, price, stock_quantity
            FROM medicines
            WHERE medicine_id = ? AND pharmacy_id = ?
            FOR UPDATE
        ");
        $stmt->bind_param("ii", $medicine_id, $pharmacy_id);
        $stmt->execute();
        $medicine = $stmt->get_result()->fetch_assoc();

        if (!$medicine) {
            throw new Exception("Medicine not found in the selected pharmacy.");
        }

        if ($medicine['stock_quantity'] < $quantity) {
            throw new Exception(
                "Only {$medicine['stock_quantity']} units of {$medicine['medicine_name']} are available."
            );
        }

        $unit_price = $medicine['price'];
        $subtotal = $unit_price * $quantity;
        $total_amount += $subtotal;

        $order_items[] = [
            "medicine_id" => $medicine_id,
            "quantity" => $quantity,
            "unit_price" => $unit_price,
            "subtotal" => $subtotal
        ];
    }

    // 3. Create main order
    $stmt = $conn->prepare("
        INSERT INTO orders 
        (order_number, user_id, pharmacy_id, total_amount, delivery_address, customer_phone)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("siidss", $order_number, $user_id, $pharmacy_id, $total_amount, $delivery_address, $customer_phone);
    $stmt->execute();

    $order_id = $conn->insert_id;

    // 4. Save order items and reduce stock
    foreach ($order_items as $item) {
        $stmt = $conn->prepare("
            INSERT INTO order_items 
            (order_id, medicine_id, quantity, unit_price, subtotal)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            "iiidd",
            $order_id,
            $item['medicine_id'],
            $item['quantity'],
            $item['unit_price'],
            $item['subtotal']
        );
        $stmt->execute();

        $stmt = $conn->prepare("
            UPDATE medicines
            SET stock_quantity = stock_quantity - ?
            WHERE medicine_id = ? AND pharmacy_id = ?
        ");
        $stmt->bind_param("iii", $item['quantity'], $item['medicine_id'], $pharmacy_id);
        $stmt->execute();
    }

    $conn->commit();

    echo json_encode([
        "success" => true,
        "message" => "Order placed successfully.",
        "order_number" => $order_number,
        "total_amount" => $total_amount,
        "status" => "pending"
    ]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(["error" => $e->getMessage()]);
}
?>