<?php
/**
 * Contact page for Riget Zoo Adventures.
 *
 * Renders a contact form and possibly additional contact details for the
 * site administration team.
 *
 * @package RigetZoo
 * @author Snat
 * @link https://snat.co.uk
 */
session_start();
if (!defined('ROOT_DIR')) { define('ROOT_DIR', __DIR__); }
require_once ROOT_DIR . '/functions/php/helpers.php';
require_once ROOT_DIR . '/functions/php/auth.php';
// Optionally fetch reCAPTCHA site key from settings
if (!class_exists('Database')) { require_once ROOT_DIR . '/db/Database.php'; }
$recaptcha_site_key = Database::getSetting('recaptcha_site_key', '');
// Get zoo coords and map flags
$zoo_lat = Database::getSetting('zoo_lat', '52.6548');
$zoo_lon = Database::getSetting('zoo_lon', '-0.4827');
$map_use_server_geocode = Database::getSetting('map_use_server_geocode', '0');
$map_use_server_routing = Database::getSetting('map_use_server_routing', '0');

$pageTitle = 'Contact - Riget Zoo Adventures';
require_once ROOT_DIR . '/templates/header.php';
?>
<h2 id="contact-heading">Contact Us</h2>
<p>Have a question or feedback? Use the form below and we'll get back to you.</p>
<?php
  $u = function_exists('current_user') ? current_user() : null;
  $prefill_name = $u ? ($u['first_name'] . ' ' . $u['surname']) : '';
  $prefill_email = $u ? $u['email'] : '';
?>
<form method="post" action="/functions/php/contact-submit.php" class="card" aria-labelledby="contact-heading">
    <div class="form-row" style="margin-bottom:12px">
        <?php if (!$u): ?>
            <div style="flex:1;min-width:200px">
                <label for="name">Name</label>
                <input id="name" class="input" type="text" name="name" required>
            </div>
            <div style="flex:1;min-width:200px">
                <label for="email">Email</label>
                <input id="email" class="input" type="email" name="email" required>
            </div>
        <?php else: ?>
            <div style="width:100%" class="small-muted">
                <div><strong>From:</strong> <?php echo htmlspecialchars($prefill_name); ?> &lt;<?php echo htmlspecialchars($prefill_email); ?>&gt;</div>
            </div>
        <?php endif; ?>
    </div>
    <div class="form-row" style="margin-bottom:12px">
        <div style="width:100%">
            <label for="subject">Subject</label>
            <input id="subject" class="input" type="text" name="subject" required>
        </div>
    </div>
    <div class="form-row" style="margin-bottom:12px">
        <div style="width:100%">
            <label for="message">Message</label>
            <textarea id="message" class="input" name="message" rows="6" required></textarea>
        </div>
    </div>
    <!-- honeypot for bots -->
    <input type="text" name="phone" style="display:none">
    <!-- reCAPTCHA widget (Google reCAPTCHA v2) - requires site key in settings 'recaptcha_site_key' -->
    <?php if (!empty($recaptcha_site_key)): ?>
        <div style="margin: 8px 0;">
            <div class="g-recaptcha" data-sitekey="<?php echo htmlspecialchars($recaptcha_site_key); ?>"></div>
        </div>
        <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <?php endif; ?>
    <div style="display:flex;justify-content:flex-end;">
        <button type="submit" class="btn">Send Message</button>
    </div>
</form>

<div style="margin-top:16px">
    <a class="btn" href="<?php echo BASE_URL ?: '/'; ?>/map.php">Open map & directions</a>
  <span class="small-muted" style="margin-left:12px">Or use the embedded map on the map page to get turn-by-turn directions.</span>
</div>

<!-- The map UI has been moved to /map.php -->
<script>
// Provide map configuration to the client
window.RZ_MAP = window.RZ_MAP || {};
window.RZ_MAP.zooLat = '<?php echo htmlspecialchars($zoo_lat, ENT_QUOTES); ?>';
window.RZ_MAP.zooLon = '<?php echo htmlspecialchars($zoo_lon, ENT_QUOTES); ?>';
window.RZ_MAP.useServerGeocode = <?php echo ($map_use_server_geocode === '1') ? 'true' : 'false'; ?>;
window.RZ_MAP.useServerRouting = <?php echo ($map_use_server_routing === '1') ? 'true' : 'false'; ?>;
    (function(){
        // Destination coordinates for Riget Zoo Adventures are provided from server settings
        const zooLat = parseFloat(window.RZ_MAP?.zooLat || 52.6548);
        const zooLon = parseFloat(window.RZ_MAP?.zooLon || -0.4827);
        // Map init
        const map = L.map('map').setView([zooLat, zooLon], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);
        const zooMarker = L.marker([zooLat, zooLon]).addTo(map).bindPopup('Riget Zoo Adventures');

        // Keep a routing control instance reference
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
            } catch (err) {
                console.error('Geocode error', err);
                return null;
            }
        }

        function setMessage(msg, type) {
            const el = document.getElementById('map-msg');
            el.textContent = msg;
            el.className = type === 'error' ? 'small-muted' : 'small-muted';
        }

        document.getElementById('directions-button').addEventListener('click', async function(e){
            e.preventDefault();
            const originVal = document.getElementById('directions-origin').value.trim();
            if (!originVal) { setMessage('Please enter your address to get directions.', 'error'); return; }
            setMessage('Looking up address...');
            const origin = await geocodeAddress(originVal);
            if (!origin) { setMessage('Could not find the address — please refine and try again.', 'error'); return; }
            setMessage('Found: ' + origin.display_name + ' — generating route...');
            const originLatLng = L.latLng(origin.lat, origin.lon);
            const destLatLng = L.latLng(zooLat, zooLon);
            // Remove existing control
            if (routeControl) { map.removeControl(routeControl); routeControl = null; }
            routeControl = L.Routing.control({
                waypoints: [ originLatLng, destLatLng ],
                lineOptions: { styles: [{ color: 'blue', opacity: 0.6, weight: 5 }] },
                router: L.Routing.osrmv1({ serviceUrl: 'https://router.project-osrm.org/route/v1' }),
                geocoder: L.Control.Geocoder ? L.Control.Geocoder.nominatim() : null,
                showAlternatives: false,
                fitSelectedRoutes: true,
                routeWhileDragging: false,
                createMarker: function(i, wp, nWps) {
                    if (i === 0) return L.marker(wp.latLng).bindPopup('From: ' + origin.display_name);
                    if (i === nWps-1) return L.marker(wp.latLng).bindPopup('Riget Zoo Adventures');
                    return null;
                }
            }).addTo(map);
            routeControl.on('routesfound', function(e){
                const routes = e.routes;
                if (routes && routes.length) {
                    setMessage('Route ready — distance: ' + (routes[0].summary.totalDistance/1000).toFixed(1) + ' km, time: ' + Math.round(routes[0].summary.totalTime/60) + ' mins');
                    // Render textual turn-by-turn directions
                    try {
                        fetchDirectionsText(origin, zooLat, zooLon, routes[0]);
                    } catch (e) { console.error('directions text error', e); }
                }
            });
            routeControl.on('routingerror', function(err){ console.error(err); setMessage('Unable to calculate route at this time', 'error'); });
        });

        // Build textual directions list using server side route JSON if available (or else attempt to parse LRM route)
        async function fetchDirectionsText(origin, destLat, destLon, lrmRoute) {
            const originLatLng = L.latLng(origin.lat, origin.lon);
            const endpoint = window.RZ_MAP && window.RZ_MAP.useServerRouting ? '/functions/php/route.php?start=' + originLatLng.lat + ',' + originLatLng.lng + '&end=' + destLat + ',' + destLon : null;
            let steps = null;
            if (endpoint) {
                try {
                    const resp = await fetch(endpoint);
                    if (resp.ok) {
                        const j = await resp.json();
                        if (j && j.routes && j.routes[0] && j.routes[0].legs && j.routes[0].legs[0] && j.routes[0].legs[0].steps) {
                            steps = j.routes[0].legs[0].steps;
                        }
                    }
                } catch (err) { console.warn('Server route fetch failed', err); }
            }
            // If server-side route failed to return steps, attempt to extract from the LRM route
            if (!steps && lrmRoute && lrmRoute.instructions && lrmRoute.instructions.length) {
                // LRM instructions may be an array of objects with text & distance
                steps = lrmRoute.instructions.map(i => ({ 'maneuver': { 'type': i.type || 'turn', 'modifier': i.modifier || '' }, 'name': i.text || '', 'distance': i.distance || 0, 'duration': i.time || 0, 'instruction': i.text }));
            }
            renderDirectionsList(steps);
        }

        function renderDirectionsList(steps) {
            const el = document.getElementById('directions-list');
            if (!el) return;
            el.innerHTML = '';
            if (!steps || steps.length === 0) { el.innerHTML = '<div class="small-muted">No textual directions available.</div>'; return; }
            const ol = document.createElement('ol');
            for (const s of steps) {
                const li = document.createElement('li');
                const inst = buildInstructionText(s);
                li.textContent = inst;
                ol.appendChild(li);
            }
            el.appendChild(ol);
        }

        function buildInstructionText(step) {
            if (!step) return '';
            // Step from OSRM or synthesized from LRM
            const name = step.name || '';
            const dist = step.distance || 0;
            const mins = Math.round((step.duration || 0) / 60);
            if (step.instruction) return step.instruction + (dist ? ' (' + (dist > 1000 ? (dist/1000).toFixed(1) + ' km' : Math.round(dist) + ' m') + ')' : '');
            const maneuver = (step.maneuver && (step.maneuver.modifier || step.maneuver.type)) ? (step.maneuver.modifier || step.maneuver.type) : '';
            let text = '';
            if (maneuver) text = maneuver.charAt(0).toUpperCase() + maneuver.slice(1) + (name ? ' onto ' + name : '');
            else if (name) text = 'Continue on ' + name;
            if (dist) text += ' (' + (dist > 1000 ? (dist/1000).toFixed(1) + ' km' : Math.round(dist) + ' m') + ')';
            if (!text) text = 'Proceed ' + (mins ? mins + ' mins' : '');
            return text;
        }
    })();
    </script>


<?php require_once ROOT_DIR . '/templates/footer.php'; ?>

<!-- 2025-12-03 09:30 - Add contact form UI (placeholder only) - author: Snat -->

