<?php
include "../config/db.php";

$medicine_name = trim($_GET['medicine'] ?? '');

if ($medicine_name === "") {
    echo json_encode(["error" => "Please enter a medicine name."]);
    exit();
}

$sql = "SELECT pharmacy_id, name, address, phone, latitude, longitude 
        FROM pharmacies";
$result = $conn->query($sql);

$pharmacies = [];

while ($row = $result->fetch_assoc()) {
    $pharmacies[] = $row;
}

echo json_encode($pharmacies);
?>