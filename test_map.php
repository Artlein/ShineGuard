<?php
require_once 'dbconnect.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Isolated Map Test</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <style>
        #test-map { width: 100%; height: 500px; border: 5px solid red; }
    </style>
</head>
<body>
    <h1>Isolated Map Test</h1>
    <div id="test-map"></div>
    <script>
        console.log("Initializing Test Map...");
        try {
            var map = L.map('test-map').setView([14.5765, 121.0355], 13);
            L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap'
            }).addTo(map);
            L.marker([14.5765, 121.0355]).addTo(map).bindPopup('Test Marker').openPopup();
            console.log("Test Map Initialized Successfully!");
        } catch (e) {
            console.error("Test Map FAILED:", e);
        }
    </script>
</body>
</html>
