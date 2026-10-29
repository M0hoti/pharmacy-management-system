<!DOCTYPE html>
<html>
<head>
    <title>Find Medicine</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<h2>Find Medicine Near You</h2>

<label for="medicine">Enter medicine name</label>
<input type="text" id="medicine" placeholder="e.g. Paracetamol">

<button onclick="findNearestPharmacy()">Find Nearest Pharmacy</button>

<div id="result"></div>

<script src="../js/location.js"></script>
</body>
</html>