<?php
/**
 * Map directions page.
 * Shows an interactive map with route and textual directions.
 */
session_start();
if (!defined('ROOT_DIR')) { define('ROOT_DIR', __DIR__); }
require_once ROOT_DIR . '/functions/php/helpers.php';
if (!class_exists('Database')) { require_once ROOT_DIR . '/db/Database.php'; }
$zoo_lat = Database::getSetting('zoo_lat', '52.6548');
$zoo_lon = Database::getSetting('zoo_lon', '-0.4827');
$map_use_server_geocode = Database::getSetting('map_use_server_geocode', '0');
$map_use_server_routing = Database::getSetting('map_use_server_routing', '0');
$pageTitle = 'Map & Directions - Riget Zoo Adventures';
require_once ROOT_DIR . '/templates/header.php';
?>
<h2>Get Directions</h2>
<p>Enter your starting address to find directions to Riget Zoo Adventures.</p>

<style>
  #map { height: 420px; width: 100%; border-radius:8px; }
  .map-card .controls { display:flex; gap:8px; align-items:center; margin-bottom:8px; }
  .map-card .controls .input{flex:1}
  .map-msg{margin-bottom:8px}
</style>

<!-- Leaflet and routing libraries (CDN) -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.min.js"></script>

<div class="card map-card" style="margin-top:18px">
    <div class="map-msg" id="map-msg" aria-live="polite"></div>
    <div class="controls">
        <input id="directions-origin" class="input" type="text" value="Stamford College, Drift Rd, Stamford PE9 1XA, UK" aria-label="Your address">
        <button id="directions-button" class="btn">Get directions</button>
    </div>
    <div id="map"></div>
    <div id="directions-list" class="small-muted" aria-live="polite" style="margin-top:12px"></div>
</div>

<script>
window.RZ_MAP = window.RZ_MAP || {};
window.RZ_MAP.zooLat = '<?php echo htmlspecialchars($zoo_lat, ENT_QUOTES); ?>';
window.RZ_MAP.zooLon = '<?php echo htmlspecialchars($zoo_lon, ENT_QUOTES); ?>';
window.RZ_MAP.useServerGeocode = <?php echo ($map_use_server_geocode === '1') ? 'true' : 'false'; ?>;
window.RZ_MAP.useServerRouting = <?php echo ($map_use_server_routing === '1') ? 'true' : 'false'; ?>;
(function(){
    const zooLat = parseFloat(window.RZ_MAP?.zooLat || 52.6548);
    const zooLon = parseFloat(window.RZ_MAP?.zooLon || -0.4827);
    const map = L.map('map').setView([zooLat, zooLon], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '© OpenStreetMap contributors' }).addTo(map);
    const zooMarker = L.marker([zooLat, zooLon]).addTo(map).bindPopup('Riget Zoo Adventures');
    let routeControl = null;
    async function geocodeAddress(q) {
        try {
            if (window.RZ_MAP && window.RZ_MAP.useServerGeocode) {
               const resp = await fetch('/functions/php/geocode.php?q=' + encodeURIComponent(q));
               if (resp.ok) {
                  const j = await resp.json();
                  if (j && j.lat && j.lon) return { lat: parseFloat(j.lat), lon: parseFloat(j.lon), display_name: j.display_name || q };
               }
            }
            const res = await fetch('https://nominatim.openstreetmap.org/search?format=json&limit=1&q=' + encodeURIComponent(q));
            const data = await res.json();
            if (!Array.isArray(data) || data.length === 0) return null;
            return { lat: parseFloat(data[0].lat), lon: parseFloat(data[0].lon), display_name: data[0].display_name };
        } catch (err) { console.error('Geocode error', err); return null; }
    }
    function setMessage(msg, type) { const el = document.getElementById('map-msg'); el.textContent = msg; el.className = 'small-muted'; }
    document.getElementById('directions-button').addEventListener('click', async function(e){
        e.preventDefault();
        const originVal = document.getElementById('directions-origin').value.trim(); if (!originVal) { setMessage('Please enter your address to get directions.', 'error'); return; }
        setMessage('Looking up address...');
        const origin = await geocodeAddress(originVal);
        if (!origin) { setMessage('Could not find the address — please refine and try again.', 'error'); return; }
        setMessage('Found: ' + origin.display_name + ' — generating route...');
        const originLatLng = L.latLng(origin.lat, origin.lon);
        const destLatLng = L.latLng(zooLat, zooLon);
        if (routeControl) { map.removeControl(routeControl); routeControl = null; }
        // Choose a router - we keep OSRM in LRM, but prefer server-side endpoint when configured
        const router = L.Routing.osrmv1({ serviceUrl: 'https://router.project-osrm.org/route/v1' });
        routeControl = L.Routing.control({ waypoints: [ originLatLng, destLatLng ], lineOptions: { styles: [{ color: 'blue', opacity: 0.6, weight: 5 }] }, router: router, showAlternatives: false, fitSelectedRoutes: true, routeWhileDragging: false, createMarker: function(i, wp, nWps) { if (i === 0) return L.marker(wp.latLng).bindPopup('From: ' + origin.display_name); if (i === nWps-1) return L.marker(wp.latLng).bindPopup('Riget Zoo Adventures'); return null; } }).addTo(map);
        routeControl.on('routesfound', function(e){ const routes = e.routes; if (routes && routes.length) { setMessage('Route ready — distance: ' + (routes[0].summary.totalDistance/1000).toFixed(1) + ' km, time: ' + Math.round(routes[0].summary.totalTime/60) + ' mins'); try { fetchDirectionsText(origin, zooLat, zooLon, routes[0]); } catch (ex){} } });
        routeControl.on('routingerror', function(err){ console.error(err); setMessage('Unable to calculate route at this time', 'error'); });
    });
    async function fetchDirectionsText(origin, destLat, destLon, lrmRoute) {
      const originLatLng = L.latLng(origin.lat, origin.lon);
      const endpoint = window.RZ_MAP && window.RZ_MAP.useServerRouting ? '/functions/php/route.php?start=' + originLatLng.lat + ',' + originLatLng.lng + '&end=' + destLat + ',' + destLon : null;
      let steps = null;
      if (endpoint) {
        try { const resp = await fetch(endpoint); if (resp.ok) { const j = await resp.json(); if (j && j.routes && j.routes[0] && j.routes[0].legs && j.routes[0].legs[0] && j.routes[0].legs[0].steps) steps = j.routes[0].legs[0].steps; } } catch (err) { console.warn('Server route fetch failed', err); }
      }
      if (!steps && lrmRoute && lrmRoute.instructions && lrmRoute.instructions.length) {
        steps = lrmRoute.instructions.map(i => ({ 'maneuver': { 'type': i.type || 'turn', 'modifier': i.modifier || '' }, 'name': i.text || '', 'distance': i.distance || 0, 'duration': i.time || 0, 'instruction': i.text }));
      }
      renderDirectionsList(steps);
    }
    function renderDirectionsList(steps) {
      const el = document.getElementById('directions-list'); if (!el) return; el.innerHTML = ''; if (!steps || steps.length === 0) { el.innerHTML = '<div class="small-muted">No textual directions available.</div>'; return; } const ol = document.createElement('ol'); for (const s of steps) { const li = document.createElement('li'); li.textContent = buildInstructionText(s); ol.appendChild(li); } el.appendChild(ol);
    }
    function buildInstructionText(step) { if (!step) return ''; const name = step.name || ''; const dist = step.distance || 0; const mins = Math.round((step.duration || 0) / 60); if (step.instruction) return step.instruction + (dist ? ' (' + (dist > 1000 ? (dist/1000).toFixed(1) + ' km' : Math.round(dist) + ' m') + ')' : ''); const maneuver = (step.maneuver && (step.maneuver.modifier || step.maneuver.type)) ? (step.maneuver.modifier || step.maneuver.type) : ''; let text = ''; if (maneuver) text = maneuver.charAt(0).toUpperCase() + maneuver.slice(1) + (name ? ' onto ' + name : ''); else if (name) text = 'Continue on ' + name; if (dist) text += ' (' + (dist > 1000 ? (dist/1000).toFixed(1) + ' km' : Math.round(dist) + ' m') + ')'; if (!text) text = 'Proceed ' + (mins ? mins + ' mins' : ''); return text; }
})();
</script>

<?php require_once ROOT_DIR . '/templates/footer.php'; ?>

<!-- 2025-12-03 13:30 - Add Leaflet map and map page (UI) - author: Snat -->

