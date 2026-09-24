
<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_login();

$vehicles = $pdo->query("SELECT * FROM vehicle_types WHERE is_active = 1 ORDER BY base_fare ASC")->fetchAll();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pickup = trim($_POST['pickup'] ?? '');
    $dropoff = trim($_POST['dropoff'] ?? '');
    $distance = (float)($_POST['distance'] ?? 0);
    $vehicle_type_id = (int)($_POST['vehicle_type_id'] ?? 0);

    if ($pickup === '' || $dropoff === '') {
        $errors[] = 'Select both a pickup point and a destination.';
    }

    if ($distance <= 0) {
        $errors[] = 'Could not calculate route distance.';
    }

    $vehicle = null;

    if (!$errors) {
        $stmt = $pdo->prepare(
            'SELECT * FROM vehicle_types WHERE id = ? AND is_active = 1'
        );
        $stmt->execute([$vehicle_type_id]);
        $vehicle = $stmt->fetch();

        if (!$vehicle) {
            $errors[] = 'Choose a vehicle type.';
        }
    }

    if (!$errors) {
        $fare = $vehicle['base_fare'] + ($vehicle['rate_per_km'] * $distance);

        $stmt = $pdo->prepare(
            'INSERT INTO bookings
            (user_id, vehicle_type_id, pickup_location, dropoff_location, distance_km, estimated_fare)
            VALUES (?, ?, ?, ?, ?, ?)'
        );

        $stmt->execute([
            $_SESSION['user_id'],
            $vehicle['id'],
            $pickup,
            $dropoff,
            $distance,
            $fare
        ]);

        flash_set('success', 'Ride requested! We\'re finding you a driver.');
        header('Location: ' . BASE_URL . '/my-rides.php');
        exit;
    }
}

$pageTitle = 'Book a ride';
require __DIR__ . '/includes/header.php';
?>

<!-- Leaflet CSS -->
<link
  rel="stylesheet"
  href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
/>

<div class="container"
     style="max-width:900px; padding-top:48px; padding-bottom:64px;">

  <h1>Where are you headed?</h1>

  <p style="margin-bottom:26px;">
    Select your pickup and destination on the map.
    The distance and fare will be calculated automatically.
  </p>

  <div class="booking-card">

    <?php foreach ($errors as $err): ?>
      <div class="alert alert-error"><?= e($err) ?></div>
    <?php endforeach; ?>

    <form method="post" id="booking-form">

      <!-- Pickup -->
      <div class="route-point">
        <label for="pickup">Pickup location</label>

        <input
          type="text"
          id="pickup"
          name="pickup"
          placeholder="Search pickup location..."
          value="<?= e($_POST['pickup'] ?? '') ?>"
          autocomplete="off"
          required
        />

        <div id="pickup-results" class="location-results"></div>
      </div>

      <!-- Destination -->
      <div class="route-point drop" style="margin-top:18px;">
        <label for="dropoff">Destination</label>

        <input
          type="text"
          id="dropoff"
          name="dropoff"
          placeholder="Search destination..."
          value="<?= e($_POST['dropoff'] ?? '') ?>"
          autocomplete="off"
          required
        />

        <div id="dropoff-results" class="location-results"></div>
      </div>

      <!-- Map -->
      <div style="margin-top:20px;">
        <div id="map"></div>
      </div>

      <!-- Hidden coordinates -->
      <input type="hidden" id="pickup_lat" name="pickup_lat">
      <input type="hidden" id="pickup_lng" name="pickup_lng">

      <input type="hidden" id="dropoff_lat" name="dropoff_lat">
      <input type="hidden" id="dropoff_lng" name="dropoff_lng">

      <!-- Distance -->
      <div class="field" style="margin-top:20px;">
        <label for="distance">Distance</label>

        <div
          style="
            padding:13px 15px;
            background:var(--paper-raised);
            border:1px solid var(--border);
            border-radius:8px;
            font-weight:600;
          "
        >
          <span id="distance-display">Select both locations</span>
        </div>

        <input
          type="hidden"
          id="distance"
          name="distance"
          value="<?= e($_POST['distance'] ?? '') ?>"
        >
      </div>

      <!-- Vehicle -->
      <label style="display:block; margin-top:20px;">
        Vehicle
      </label>

      <div class="vehicle-grid" id="vehicle-grid">

        <?php foreach ($vehicles as $i => $v): ?>

          <label
            class="vehicle-option"
            data-base="<?= e($v['base_fare']) ?>"
            data-rate="<?= e($v['rate_per_km']) ?>"
          >

            <input
              type="radio"
              name="vehicle_type_id"
              value="<?= e($v['id']) ?>"
              <?= $i === 0 ? 'checked' : '' ?>
            >

            <span class="v-name">
              <?= e($v['name']) ?>
            </span>

            <span class="v-rate">
              from LKR <?= number_format($v['base_fare'], 0) ?>
            </span>

          </label>

        <?php endforeach; ?>

      </div>

      <!-- Fare -->
      <div class="fare-row">
        <span>Estimated fare</span>
        <span class="amount" id="fare-amount">
          LKR 0
        </span>
      </div>

      <button
        type="submit"
        id="submit-btn"
        class="btn btn-primary btn-block"
        style="margin-top:20px;"
        disabled
      >
        Request ride
      </button>

    </form>
  </div>
</div>

<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>

const pickupInput = document.getElementById('pickup');
const dropoffInput = document.getElementById('dropoff');

const pickupResults = document.getElementById('pickup-results');
const dropoffResults = document.getElementById('dropoff-results');

const pickupLat = document.getElementById('pickup_lat');
const pickupLng = document.getElementById('pickup_lng');

const dropoffLat = document.getElementById('dropoff_lat');
const dropoffLng = document.getElementById('dropoff_lng');

const distanceInput = document.getElementById('distance');
const distanceDisplay = document.getElementById('distance-display');

const fareAmount = document.getElementById('fare-amount');
const submitBtn = document.getElementById('submit-btn');

const options = document.querySelectorAll('.vehicle-option');


// --------------------------------------------------
// MAP
// --------------------------------------------------

const map = L.map('map').setView([7.8731, 80.7718], 8);

L.tileLayer(
    'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
    {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors'
    }
).addTo(map);


let pickupMarker = null;
let dropoffMarker = null;
let routeLine = null;


// --------------------------------------------------
// LOCATION SEARCH
// --------------------------------------------------

let pickupTimer;
let dropoffTimer;


async function searchLocation(query, resultsBox, type) {

    if (query.length < 3) {
        resultsBox.innerHTML = '';
        return;
    }

    try {

        const url =
            'https://nominatim.openstreetmap.org/search?' +
            new URLSearchParams({
                q: query,
                format: 'json',
                limit: 5,
                countrycodes: 'lk'
            });

        const response = await fetch(url, {
            headers: {
                'Accept': 'application/json'
            }
        });

        const places = await response.json();

        resultsBox.innerHTML = '';

        places.forEach(place => {

            const item = document.createElement('div');

            item.className = 'location-result';

            item.textContent = place.display_name;

            item.addEventListener('click', () => {

                const lat = parseFloat(place.lat);
                const lng = parseFloat(place.lon);

                if (type === 'pickup') {

                    pickupInput.value = place.display_name;

                    pickupLat.value = lat;
                    pickupLng.value = lng;

                    setPickupMarker(lat, lng);

                } else {

                    dropoffInput.value = place.display_name;

                    dropoffLat.value = lat;
                    dropoffLng.value = lng;

                    setDropoffMarker(lat, lng);
                }

                resultsBox.innerHTML = '';

                calculateRoute();
            });

            resultsBox.appendChild(item);
        });

    } catch (error) {

        console.error('Location search error:', error);

    }
}


// Pickup search
pickupInput.addEventListener('input', () => {

    clearTimeout(pickupTimer);

    pickupTimer = setTimeout(() => {

        searchLocation(
            pickupInput.value,
            pickupResults,
            'pickup'
        );

    }, 500);

});


// Destination search
dropoffInput.addEventListener('input', () => {

    clearTimeout(dropoffTimer);

    dropoffTimer = setTimeout(() => {

        searchLocation(
            dropoffInput.value,
            dropoffResults,
            'dropoff'
        );

    }, 500);

});


// --------------------------------------------------
// MARKERS
// --------------------------------------------------

function setPickupMarker(lat, lng) {

    if (pickupMarker) {
        map.removeLayer(pickupMarker);
    }

    pickupMarker = L.marker([lat, lng])
        .addTo(map)
        .bindPopup('Pickup location')
        .openPopup();

    map.setView([lat, lng], 14);
}


function setDropoffMarker(lat, lng) {

    if (dropoffMarker) {
        map.removeLayer(dropoffMarker);
    }

    dropoffMarker = L.marker([lat, lng])
        .addTo(map)
        .bindPopup('Destination');

    map.setView([lat, lng], 14);
}


// --------------------------------------------------
// ROUTE
// --------------------------------------------------

async function calculateRoute() {

    const pLat = parseFloat(pickupLat.value);
    const pLng = parseFloat(pickupLng.value);

    const dLat = parseFloat(dropoffLat.value);
    const dLng = parseFloat(dropoffLng.value);

    if (
        !Number.isFinite(pLat) ||
        !Number.isFinite(pLng) ||
        !Number.isFinite(dLat) ||
        !Number.isFinite(dLng)
    ) {
        return;
    }

    try {

        const url =
            `https://router.project-osrm.org/route/v1/driving/` +
            `${pLng},${pLat};${dLng},${dLat}` +
            `?overview=full&geometries=geojson`;

        const response = await fetch(url);

        const data = await response.json();

        if (
            data.code !== 'Ok' ||
            !data.routes ||
            !data.routes.length
        ) {
            distanceDisplay.textContent =
                'Could not calculate route';

            return;
        }

        const route = data.routes[0];

        // OSRM returns meters
        const km = route.distance / 1000;

        distanceInput.value = km.toFixed(2);

        distanceDisplay.textContent =
            km.toFixed(2) + ' km';


        // Remove previous route
        if (routeLine) {
            map.removeLayer(routeLine);
        }


        // Draw route
        routeLine = L.geoJSON(route.geometry, {
            style: {
                color: '#F2A83B',
                weight: 5,
                opacity: 0.85
            }
        }).addTo(map);


        // Fit map to route
        map.fitBounds(routeLine.getBounds(), {
            padding: [40, 40]
        });


        updateFare();

        submitBtn.disabled = false;

    } catch (error) {

        console.error('Route error:', error);

        distanceDisplay.textContent =
            'Could not calculate route';
    }
}


// --------------------------------------------------
// FARE
// --------------------------------------------------

function selectedOption() {

    const input =
        document.querySelector(
            '.vehicle-option input:checked'
        );

    if (!input) {
        return null;
    }

    return input.closest('.vehicle-option');
}


function updateFare() {

    const opt = selectedOption();

    if (!opt) {
        return;
    }

    options.forEach(o => {
        o.classList.toggle(
            'selected',
            o === opt
        );
    });

    const base =
        parseFloat(opt.dataset.base) || 0;

    const rate =
        parseFloat(opt.dataset.rate) || 0;

    const km =
        parseFloat(distanceInput.value) || 0;

    const fare =
        base + (rate * km);

    fareAmount.textContent =
        'LKR ' +
        fare.toLocaleString(undefined, {
            maximumFractionDigits: 0
        });
}


options.forEach(option => {

    option.addEventListener('click', () => {

        option.querySelector('input').checked = true;

        updateFare();

    });

});


updateFare();


// --------------------------------------------------
// FORM VALIDATION
// --------------------------------------------------

document
    .getElementById('booking-form')
    .addEventListener('submit', function(e) {

        if (
            !pickupLat.value ||
            !pickupLng.value ||
            !dropoffLat.value ||
            !dropoffLng.value ||
            !distanceInput.value ||
            parseFloat(distanceInput.value) <= 0
        ) {

            e.preventDefault();

            alert(
                'Please select both pickup and destination from the search results.'
            );

            return false;
        }

    });

</script>

<style>

#map {
    width: 100%;
    height: 420px;
    border-radius: 12px;
    border: 1px solid var(--border);
    overflow: hidden;
}

.location-results {
    position: relative;
    z-index: 1000;
}

.location-result {
    padding: 12px 14px;
    background: white;
    border: 1px solid #ddd;
    border-top: none;
    cursor: pointer;
    font-size: 14px;
}

.location-result:hover {
    background: #f5f5f5;
}

.route-point {
    position: relative;
}

#pickup,
#dropoff {
    width: 100%;
}

@media (max-width: 700px) {

    #map {
        height: 330px;
    }

}

</style>

<?php require __DIR__ . '/includes/footer.php'; ?>

