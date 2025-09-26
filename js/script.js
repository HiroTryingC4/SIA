// Initialize the map
var map = L.map('map').setView([14.6760, 121.0437], 13); // Center on Quezon City

// Add OpenStreetMap tiles
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors'
}).addTo(map);

// Custom icons for categories
var icons = {
    tourist: L.icon({
        iconUrl: 'icons/museum.png',
        iconSize: [32, 32]
    }),
    cafe: L.icon({
        iconUrl: 'icons/cafe.png',
        iconSize: [32, 32]
    }),
    park: L.icon({
        iconUrl: 'icons/park.png',
        iconSize: [32, 32]
    }),
    shopping: L.icon({
        iconUrl: 'icons/shopping.png',
        iconSize: [32, 32]
    }),
    default: L.icon({
        iconUrl: 'icons/default.png',
        iconSize: [32, 32]
    })
};

// Fetch locations from backend
fetch("fetch_locations.php")
  .then(response => response.json())
  .then(data => {
    data.forEach(place => {
        var icon = icons[place.category] || icons.default;

        L.marker([place.latitude, place.longitude], { icon: icon })
          .addTo(map)
          .bindPopup(`<b>${place.name}</b><br>${place.description}`);
    });
  })
  .catch(err => console.error("Error fetching locations:", err));
