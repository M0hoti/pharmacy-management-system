<?php
include "../config/db.php";

header("Content-Type: application/json");

$user_lat = floatval($_GET['lat'] ?? 0);
$user_lng = floatval($_GET['lng'] ?? 0);
$medicine_name = trim($_GET['medicine'] ?? '');

if ($medicine_name === "") {
    echo json_encode(["error" => "Medicine name is required."]);
    exit();
}

$sql = "
SELECT 
    p.pharmacy_id,
    p.name,
    p.address,
    p.phone,
    p.latitude,
    p.longitude,
    m.stock_quantity,
    m.price,
    (6371 * ACOS(
        COS(RADIANS(?)) *
        COS(RADIANS(p.latitude)) *
        COS(RADIANS(p.longitude) - RADIANS(?)) +
        SIN(RADIANS(?)) *
        SIN(RADIANS(p.latitude))
    )) AS distance_km
FROM pharmacies p
JOIN medicines m ON m.pharmacy_id = p.pharmacy_id
WHERE m.medicine_name LIKE ?
AND m.stock_quantity > 0
HAVING distance_km IS NOT NULL
ORDER BY distance_km ASC
LIMIT 10
";

$stmt = $conn->prepare($sql);
$search = "%" . $medicine_name . "%";
$stmt->bind_param("ddds", $user_lat, $user_lng, $user_lat, $search);
$stmt->execute();
$result = $stmt->get_result();

$pharmacies = [];

while ($row = $result->fetch_assoc()) {
    $pharmacies[] = [
        "pharmacy_id" => $row['pharmacy_id'],
        "name" => $row['name'],
        "address" => $row['address'],
        "phone" => $row['phone'],
        "medicine" => $medicine_name,
        "stock_quantity" => $row['stock_quantity'],
        "price" => $row['price'],
        "distance_km" => round($row['distance_km'], 2)
    ];
}

echo json_encode($pharmacies);
?>