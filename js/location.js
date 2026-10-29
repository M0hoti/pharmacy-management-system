function findNearestPharmacy() {
    const medicine = document.getElementById("medicine").value.trim();

    if (!medicine) {
        alert("Please enter a medicine name.");
        return;
    }

    if (!navigator.geolocation) {
        alert("Your browser does not support location services.");
        return;
    }

    document.getElementById("result").innerHTML = "Finding nearby pharmacies...";

    navigator.geolocation.getCurrentPosition(
        function (position) {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;

            fetch(
                `../api/nearest_pharmacy.php?lat=${lat}&lng=${lng}&medicine=${encodeURIComponent(medicine)}`
            )
            .then(response => response.json())
            .then(data => displayPharmacies(data))
            .catch(() => {
                document.getElementById("result").innerHTML =
                    "Unable to load pharmacy results.";
            });
        },
        function () {
            document.getElementById("result").innerHTML =
                "Location permission denied or unavailable.";
        }
    );
}

function displayPharmacies(pharmacies) {
    const result = document.getElementById("result");

    if (!Array.isArray(pharmacies) || pharmacies.length === 0) {
        result.innerHTML = "<p>No pharmacy with this medicine was found nearby.</p>";
        return;
    }

    let html = "<h3>Nearest Pharmacies</h3>";

    pharmacies.forEach(pharmacy => {
        html += `
        <div class="pharmacy-card">
            <h4>${pharmacy.name}</h4>
            <p>${pharmacy.address}</p>
            <p>Distance: ${pharmacy.distance_km} km</p>
            <p>Phone: ${pharmacy.phone}</p>
            <p>Price: KES ${pharmacy.price}</p>
            <p>Available stock: ${pharmacy.stock_quantity}</p>

            <form action="order.php" method="GET">
                <input type="hidden" name="pharmacy_id" value="${pharmacy.pharmacy_id}">
                <input type="hidden" name="medicine" value="${pharmacy.medicine}">
                <button type="submit">Order Medicine</button>
            </form>
        </div>`;
    });

    result.innerHTML = html;
}