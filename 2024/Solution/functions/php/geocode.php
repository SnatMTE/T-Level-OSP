<?php
/**
 * Simple server-side geocoding proxy using Nominatim (OpenStreetMap)
 * Returns JSON with at least lat, lon and display_name for the first result.
 */
session_start();
if (!defined('ROOT_DIR')) { define('ROOT_DIR', dirname(dirname(__DIR__))); }
require_once ROOT_DIR . '/functions/php/helpers.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_GET['q']) || trim($_GET['q']) === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Missing q parameter']);
    exit;
}
$q = trim($_GET['q']);
$url = 'https://nominatim.openstreetmap.org/search?format=json&limit=1&q=' . urlencode($q);
$options = [
    'http' => [
        'method' => 'GET',
        'header' => "User-Agent: RigetZooDev/1.0 (dev@rigetzoo.local)\r\nAccept: application/json\r\n"
    ]
];
$context = stream_context_create($options);
$resp = @file_get_contents($url, false, $context);
if ($resp === false) {
    http_response_code(502);
    echo json_encode(['error' => 'Geocode service unavailable']);
    exit;
}
$decoded = json_decode($resp, true);
if (empty($decoded) || !is_array($decoded)) {
    echo json_encode([]);
    exit;
}
// Return first result as-is (trim to necessary fields)
$r = $decoded[0];
$out = ['lat' => isset($r['lat']) ? $r['lat'] : null, 'lon' => isset($r['lon']) ? $r['lon'] : null, 'display_name' => isset($r['display_name']) ? $r['display_name'] : ''];
echo json_encode($out);
exit;
?>
<!-- 2025-12-03 14:00 - Add server-side geocode & routing proxy stubs - author: Snat -->

