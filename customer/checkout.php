<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$pharmacy_id = intval($_GET['pharmacy_id'] ?? 0);

$medicines = $conn->prepare("
    SELECT medicine_id, medicine_name, price, stock_quantity
    FROM medicines
    WHERE pharmacy_id = ? AND stock_quantity > 0
    ORDER BY medicine_name
");
$medicines->bind_param("i", $pharmacy_id);
$medicines->execute();
$medicine_result = $medicines->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Checkout</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<h2>Checkout</h2>

<form id="checkoutForm">
    <input type="hidden" id="pharmacy_id" value="<?php echo $pharmacy_id; ?>">

    <label>Delivery Address</label>
    <input type="text" id="delivery_address" required>

    <label>Phone Number</label>
    <input type="text" id="customer_phone" required>

    <h3>Select Medicines</h3>

    <?php while ($med = $medicine_result->fetch_assoc()): ?>
        <div class="medicine-row">
            <label>
                <?php echo htmlspecialchars($med['medicine_name']); ?>
                — KES <?php echo $med['price']; ?>
                (Available: <?php echo $med['stock_quantity']; ?>)
            </label>

            <input type="number"
                   class="medicine-quantity"
                   data-medicine-id="<?php echo $med['medicine_id']; ?>"
                   min="0"
                   max="<?php echo $med['stock_quantity']; ?>"
                   value="0">
        </div>
    <?php endwhile; ?>

    <button type="submit">Place Order</button>
</form>

<div id="orderResult"></div>

<script>
document.getElementById("checkoutForm").addEventListener("submit", async function (e) {
    e.preventDefault();

    const pharmacyId = document.getElementById("pharmacy_id").value;
    const deliveryAddress = document.getElementById("delivery_address").value.trim();
    const customerPhone = document.getElementById("customer_phone").value.trim();

    const medicines = [];

    document.querySelectorAll(".medicine-quantity").forEach(input => {
        const quantity = parseInt(input.value);

        if (quantity > 0) {
            medicines.push({
                medicine_id: parseInt(input.dataset.medicineId),
                quantity: quantity
            });
        }
    });

    if (medicines.length === 0) {
        alert("Select at least one medicine.");
        return;
    }

    const formData = new FormData();
    formData.append("pharmacy_id", pharmacyId);
    formData.append("delivery_address", deliveryAddress);
    formData.append("customer_phone", customerPhone);
    formData.append("medicines", JSON.stringify(medicines));

    const response = await fetch("../api/place_order.php", {
        method: "POST",
        body: formData
    });

    const result = await response.json();

    if (result.success) {
        document.getElementById("orderResult").innerHTML = `
            <p class="success">
                Order placed successfully.<br>
                Order Number: <strong>${result.order_number}</strong><br>
                Total: KES ${result.total_amount}<br>
                Status: ${result.status}
            </p>`;
    } else {
        document.getElementById("orderResult").innerHTML =
            `<p class="error">${result.error}</p>`;
    }
});
</script>

</body>
</html>