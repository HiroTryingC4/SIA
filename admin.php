<?php
include 'db_connect.php';

// ✅ Add Place
if(isset($_POST['submit'])) {
    $name = $_POST['name'];
    $street = $_POST['street'];
    $category = $_POST['category'];
    $description = $_POST['description'];
    $latitude = $_POST['latitude'];
    $longitude = $_POST['longitude'];

    $photoName = null;
    if(!empty($_FILES['photo']['name'])) {
        $uploadDir = "uploads/";
        if(!is_dir($uploadDir)) mkdir($uploadDir);
        $photoName = time()."_".basename($_FILES['photo']['name']);
        move_uploaded_file($_FILES['photo']['tmp_name'], $uploadDir.$photoName);
    }

    $sql = "INSERT INTO locations (name, street, category, description, latitude, longitude, photo)
            VALUES ('$name','$street','$category','$description','$latitude','$longitude','$photoName')";
    if($conn->query($sql)===TRUE) {
        echo "<script>alert('✅ New location added!'); window.location='admin.php';</script>";
        exit;
    } else {
        echo "<script>alert('❌ Error: ".$conn->error."');</script>";
    }
}

// ✅ Delete Place
if(isset($_POST['delete_id'])) {
    $id = intval($_POST['delete_id']);
    $photo_sql = "SELECT photo FROM locations WHERE id=$id";
    $photo_res = $conn->query($photo_sql);
    if($photo_res && $photo_res->num_rows>0){
        $row = $photo_res->fetch_assoc();
        if($row['photo'] && file_exists("uploads/".$row['photo'])) unlink("uploads/".$row['photo']);
    }
    $conn->query("DELETE FROM locations WHERE id=$id");
    echo "<script>alert('🗑 Place removed!'); window.location='admin.php';</script>";
    exit;
}

// ✅ Load Locations
$loc_sql = "SELECT * FROM locations ORDER BY created_at DESC";
$loc_result = $conn->query($loc_sql);
$locations = [];
if ($loc_result && $loc_result->num_rows > 0) {
    while ($row = $loc_result->fetch_assoc()) {
        $locations[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Panel - Travel Map</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />

    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; background: #f9f9f9; }
        header { background: #2c3e50; color: white; padding: 15px; text-align: center; }
        h1 { margin: 0; font-size: 22px; }

        .container { display: flex; }
        .sidebar {
            width: 30%;
            padding: 20px;
            background: #fff;
            border-right: 1px solid #ddd;
            overflow-y: auto;
            height: 100vh;
        }
        .map-container { width: 70%; height: 100vh; }

        .form-box { margin-top: 20px; padding: 15px; border: 1px solid #ccc; border-radius: 8px; background: #f4f4f4; }
        .form-box input, .form-box select, .form-box textarea {
            width: 100%; padding: 8px; margin: 6px 0; border: 1px solid #ccc; border-radius: 4px;
        }
        .form-box button {
            padding: 10px 15px;
            background: #3498db; color: #fff; border: none; border-radius: 5px; cursor: pointer;
        }
        .form-box button:hover { background: #2980b9; }

        #map { width: 100%; height: 100%; }

        /* Place Info Box */
        #placeInfo {
            margin-top: 15px;
            padding: 15px;
            background: #fdfdfd;
            border: 1px solid #ddd;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        #placeInfo h4 { margin-top: 0; color: #2c3e50; }
        #placeInfo p { margin: 6px 0; font-size: 14px; color: #555; }
        #placeInfo img { max-width: 100%; margin: 8px 0; border-radius: 8px; border: 1px solid #ccc; }
        #placeInfo .category-label {
            display: inline-block; padding: 4px 8px; font-size: 12px;
            background: #3498db; color: white; border-radius: 6px; margin-bottom: 10px;
        }
        #placeInfo form { margin-top: 10px; }
        #placeInfo button {
            background: #e74c3c; border: none; padding: 8px 12px; color: white;
            border-radius: 6px; cursor: pointer;
        }
        #placeInfo button:hover { background: #c0392b; }

        .locations-section { padding: 20px; }
        .places { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-top: 15px; }
        .card {
            background: white; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            overflow: hidden; transition: transform 0.2s;
        }
        .card:hover { transform: scale(1.03); }
        .card img { width: 100%; height: 150px; object-fit: cover; }
        .card-content { padding: 15px; }
        .card-content h3 { margin: 0 0 10px; }
        .card-content p { font-size: 14px; color: #555; }
        .delete-btn {
            margin-top: 10px; padding: 8px 12px; background: #e74c3c;
            color: #fff; border: none; border-radius: 5px; cursor: pointer; font-size: 14px;
        }
        .delete-btn:hover { background: #c0392b; }

        .sorting-bar { margin-bottom: 15px; }
        .sorting-bar select { padding: 8px; border-radius: 5px; border: 1px solid #ccc; }
        .showing-label { margin-top: 8px; font-weight: bold; color: #333; }
    </style>
</head>
<body>
<header>
    <h1>🛠 Admin Panel - Travel Map</h1>
</header>

<div class="container">
    <!-- Sidebar -->
    <div class="sidebar">
        <h3>Map Controls</h3>
        <button onclick="resetMap()">Reset Map</button><br><br>

        <label>Filter by Category:</label>
        <select id="categoryFilter" onchange="filterByCategory()">
            <option value="all">All</option>
            <option value="landmark">Landmarks & Parks</option>
            <option value="food">Foods & Tambay Spots</option>
            <option value="arts">Arts & Culture</option>
            <option value="mall">Malls & Entertainment</option>
        </select><br><br>

        <!-- Search Place -->
        <input type="text" id="searchBox" placeholder="Search for a place...">
        <button onclick="searchPlace()">Search Place</button><br><br>

        <!-- Search Street -->
        <input type="text" id="searchStreetBox" placeholder="Search by street...">
        <button onclick="searchStreet()">Search Street</button>

        <h3>Place Info</h3>
        <div id="placeInfo">ℹ️ Click a marker or check a place to see details here.</div>

        <!-- Add Location Form -->
        <div class="form-box">
            <h3>Add New Place</h3>
            <form action="admin.php" method="POST" enctype="multipart/form-data">
                <input type="text" name="name" placeholder="Place Name" required>
                <input type="text" name="street" id="street" placeholder="Street / Address" required>
                <select name="category" required>
                    <option value="landmark">Landmarks & Parks</option>
                    <option value="food">Foods & Tambay Spots</option>
                    <option value="arts">Arts & Culture</option>
                    <option value="mall">Malls & Entertainment</option>
                </select>
                <textarea name="description" placeholder="Description"></textarea>
                <input type="text" name="latitude" id="lat" placeholder="Latitude" required readonly>
                <input type="text" name="longitude" id="lng" placeholder="Longitude" required readonly>
                <input type="file" name="photo">
                <button type="submit" name="submit">Save Location</button>
            </form>
        </div>
    </div>

    <!-- Map -->
    <div class="map-container">
        <div id="map"></div>
    </div>
</div>

<!-- List of Locations -->
<div class="locations-section">
    <h3>📌 List of Locations</h3>   
    <div class="sorting-bar">
        <label for="sortOptions">Sort/Filter:</label>
        <select id="sortOptions" onchange="updateSorting()">
            <option value="most_recent">Most Recent</option>
            <option value="recent">Recent</option>
            <option value="landmark">Landmarks & Parks</option>
            <option value="food">Foods & Tambay Spots</option>
            <option value="arts">Arts & Culture</option>
            <option value="mall">Malls & Entertainment</option>
        </select>
        <div class="showing-label" id="showingLabel">Showing: Most Recent Places</div>
    </div>
    <div class="places" id="placesList"></div>
</div>

<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script src="https://unpkg.com/@turf/turf/turf.min.js"></script> 

<script>
let map = L.map('map').setView([14.6760, 121.0437], 12);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors'
}).addTo(map);

let qcArea = null; 
let qcBoundary = null; 

fetch("https://nominatim.openstreetmap.org/search.php?q=Quezon+City&polygon_geojson=1&format=json")
  .then(res => res.json())
  .then(data => {
    let geo = data[0].geojson;

    qcBoundary = L.geoJSON(geo, { style: { color: "red", weight: 3, fill: false } }).addTo(map);
    map.fitBounds(qcBoundary.getBounds());
    qcArea = turf.polygon(geo.coordinates);

    // Map click
    map.on("click", function (e) {
        let point = turf.point([e.latlng.lng, e.latlng.lat]);
        if (turf.booleanPointInPolygon(point, qcArea)) {
            document.getElementById("lat").value = e.latlng.lat.toFixed(6);
            document.getElementById("lng").value = e.latlng.lng.toFixed(6);
            fetch(`https://nominatim.openstreetmap.org/reverse?lat=${e.latlng.lat}&lon=${e.latlng.lng}&format=json`)
            .then(res => res.json())
            .then(data => {
                let road = data.address.road || data.address.pedestrian || data.address.footway || "";
                document.getElementById("street").value = road;
            });
        } else {
            alert("❌ You can only add locations inside Quezon City.");
        }
    });
});

let markers = [];
let allLocations = <?php echo json_encode($locations); ?>;

function showPlaceInfo(loc) {
    document.getElementById("placeInfo").innerHTML = `
        <h4>${loc.name}</h4>
        <span class="category-label">${loc.category}</span>
        <p><b>Street:</b> ${loc.street || "N/A"}</p>
        <p>${loc.description || "No description available."}</p>
        ${loc.photo ? "<img src='uploads/${loc.photo}'>" : ""}
        <p><b>Lat:</b> ${loc.latitude}, <b>Lng:</b> ${loc.longitude}</p>
        <form method="POST" onsubmit="return confirm('Delete this place?')">
            <input type="hidden" name="delete_id" value="${loc.id}">
            <button type="submit">🗑 Delete</button>
        </form>
    `;
    map.setView([loc.latitude, loc.longitude], 16);
}

function loadMarkers(category = "all") {
    markers.forEach(m => map.removeLayer(m));
    markers = [];
    allLocations.forEach(loc => {
        if (category !== "all" && loc.category !== category) return;
        let marker = L.marker([loc.latitude, loc.longitude]).addTo(map);
        marker.on("click", () => showPlaceInfo(loc));
        markers.push(marker);
    });
}

function renderPlaces(list) {
    let container = document.getElementById("placesList");
    container.innerHTML = "";
    if (list.length === 0) { container.innerHTML = "<p>No locations available.</p>"; return; }
    list.forEach(loc => {
        container.innerHTML += `
        <div class="card">
            ${loc.photo ? `<img src="uploads/${loc.photo}" alt="${loc.name}">` : `<img src="https://via.placeholder.com/300x150?text=No+Image">`}
            <div class="card-content">
                <h3>${loc.name}</h3>
                <p><b>Street:</b> ${loc.street || "N/A"}</p>
                <p><b>Category:</b> ${loc.category}</p>
                <p>${loc.description ? loc.description.substring(0,80)+"..." : "No description."}</p>
                <p><b>Lat:</b> ${loc.latitude}, <b>Lng:</b> ${loc.longitude}</p>
                <button onclick='showPlaceInfo(${JSON.stringify(loc)})'> View on Map</button>
                <form method="POST" onsubmit="return confirm('Delete this location?');">
                    <input type="hidden" name="delete_id" value="${loc.id}">
                    <button type="submit" class="delete-btn">🗑 Delete</button>
                </form>
            </div>
        </div>`;
    });
}

function updateSorting() {
    let option = document.getElementById("sortOptions").value;
    let showingText = "";
    let sorted = [...allLocations];
    switch(option) {
        case "recent": sorted.sort((a,b) => new Date(a.created_at) - new Date(b.created_at)); showingText="Showing: Recent Places"; break;
        case "most_recent": sorted.sort((a,b) => new Date(b.created_at) - new Date(a.created_at)); showingText="Showing: Most Recent Places"; break;
        case "landmark": sorted = sorted.filter(l => l.category==="landmark"); showingText="Showing: Landmarks & Parks"; break;
        case "food": sorted = sorted.filter(l => l.category==="food"); showingText="Showing: Foods & Tambay Spots"; break;
        case "arts": sorted = sorted.filter(l => l.category==="arts"); showingText="Showing: Arts & Culture"; break;
        case "mall": sorted = sorted.filter(l => l.category==="mall"); showingText="Showing: Malls & Entertainment"; break;
    }
    document.getElementById("showingLabel").innerText = showingText;
    renderPlaces(sorted);
}

function resetMap() { if (qcBoundary) map.fitBounds(qcBoundary.getBounds()); loadMarkers("all"); }
function filterByCategory() { let cat = document.getElementById("categoryFilter").value; loadMarkers(cat); }

function searchPlace() {
    let search = document.getElementById("searchBox").value.toLowerCase();
    let found = allLocations.find(loc => loc.name.toLowerCase().includes(search));
    if (found) showPlaceInfo(found);
    else alert("❌ No place found with that name.");
}

function searchStreet() {
    let street = document.getElementById("searchStreetBox").value.trim();
    if (!street) return alert("⚠ Please enter a street name.");

    fetch(`https://nominatim.openstreetmap.org/search?street=${encodeURIComponent(street)}&city=Quezon+City&country=Philippines&format=json&limit=1`)
    .then(res => res.json())
    .then(results => {
        if (results.length === 0) {
            alert("❌ Street not found in Quezon City.");
            return;
        }
        let loc = results[0];
        let lat = parseFloat(loc.lat);
        let lon = parseFloat(loc.lon);
        map.setView([lat, lon], 17);
        L.marker([lat, lon]).addTo(map).bindPopup(`${street}, Quezon City`).openPopup();
    })
    .catch(err => {
        console.error(err);
        alert("⚠ Error searching street.");
    });
}

loadMarkers();
updateSorting();
</script>
</body>
</html>
