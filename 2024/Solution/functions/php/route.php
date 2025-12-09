<?php
/**
 * Simple server-side route proxy using OSRM public service.
 * Accepts 'start' and 'end' GET parameters as `lat,lon` (note: order lat,lon)
 * and returns the OSRM route JSON response.
 */
session_start();
if (!defined('ROOT_DIR')) { define('ROOT_DIR', dirname(dirname(__DIR__))); }
require_once ROOT_DIR . '/functions/php/helpers.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_GET['start']) || !isset($_GET['end'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing start or end parameter']);
    exit;
}
function parseLatLon($s) {
    $parts = explode(',', $s);
    if (count($parts) !== 2) return false;
    $lat = floatval(trim($parts[0]));
    $lon = floatval(trim($parts[1]));
    return [$lat, $lon];
}
$start = parseLatLon($_GET['start']);
$end = parseLatLon($_GET['end']);
if (!$start || !$end) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid start or end']);
    exit;
}
$startLat = $start[0]; $startLon = $start[1];
$endLat = $end[0]; $endLon = $end[1];
// Build OSRM route URL - OSRM expects lon,lat ordering
$osrm = "https://router.project-osrm.org/route/v1/driving/{$startLon},{$startLat};{$endLon},{$endLat}?overview=full&geometries=geojson&steps=true";
// cURL with fallback
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $osrm);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
// Some environments require a common UA header
curl_setopt($ch, CURLOPT_HTTPHEADER, ['User-Agent: RigetZooDev/1.0 (dev@rigetzoo.local)']);
$resp = curl_exec($ch);
$errno = curl_errno($ch);
$httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
if ($errno || $httpStatus !== 200 || $resp === false) {
    http_response_code(502);
    echo json_encode(['error' => 'Routing service unavailable']);
    exit;
}
// Forward JSON
echo $resp;
exit;
?>