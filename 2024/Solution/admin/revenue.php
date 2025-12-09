<?php
/**
 * Admin revenue page.
 *
 * Shows a chart of revenue and links to export and detailed reports.
 *
 * @package RigetZoo
 * @author Snat
 * @link https://snat.co.uk
 */
session_start();
if (!defined('ROOT_DIR')) { define('ROOT_DIR', dirname(__DIR__)); }
require_once ROOT_DIR . '/functions/php/auth.php';
require_once ROOT_DIR . '/db/Database.php';
require_once ROOT_DIR . '/functions/php/helpers.php';

if (!is_logged_in() || empty(current_user()['is_admin'])) {
    http_response_code(403);
    require_once ROOT_DIR . '/templates/header.php';
    echo '<h2>Forbidden</h2><p>You must be an admin to view this page.</p>';
    require_once ROOT_DIR . '/templates/footer.php';
    exit;
}

$pdo = Database::pdo();
// Group bookings by ISO week (year-week) using strftime; sum total_price for weekly revenue
$sql = "SELECT strftime('%Y-%W', created_at) AS yearweek, SUM(COALESCE(total_price,0)) AS revenue, COUNT(*) AS bookings, SUM(CASE WHEN type='tickets' THEN COALESCE(total_price,0) ELSE 0 END) AS tickets_revenue, SUM(CASE WHEN type='hotel' THEN COALESCE(total_price,0) ELSE 0 END) AS hotel_revenue FROM bookings WHERE status = 'active' GROUP BY yearweek ORDER BY yearweek DESC LIMIT 12";
$stmt = $pdo->query($sql);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Admin - Revenue';
require_once ROOT_DIR . '/templates/header.php';
?>
<h2>Weekly Revenue</h2>
<p>Showing recent weeks' revenue from active bookings.</p>
<table>
  <thead>
    <tr><th>Week</th><th>Total Revenue</th><th>Bookings</th><th>Tickets Revenue</th><th>Hotel Revenue</th></tr>
  </thead>
  <tbody>
  <?php foreach ($rows as $r): ?>
    <tr>
      <td><?php echo htmlspecialchars($r['yearweek']); ?></td>
      <td><?php echo htmlspecialchars(format_money($r['revenue'])); ?></td>
      <td><?php echo htmlspecialchars($r['bookings']); ?></td>
      <td><?php echo htmlspecialchars(format_money($r['tickets_revenue'])); ?></td>
      <td><?php echo htmlspecialchars(format_money($r['hotel_revenue'])); ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>

<h3>Charts</h3>
<p><small>Note: Revenue & bookings are grouped by the date the booking was created; tickets are grouped by the ticket date (the date of the visit/event).</small></p>
<div style="max-width:1100px;margin:12px auto;display:flex;gap:24px;flex-wrap:wrap">
  <div style="flex:1;min-width:300px">
    <canvas id="revenue-chart"></canvas>
  </div>
  <div style="flex:1;min-width:300px">
    <canvas id="tickets-chart"></canvas>
  </div>
  <div style="flex:1;min-width:300px">
    <canvas id="bookings-chart"></canvas>
  </div>
</div>

<!-- Chart.js via CDN (simple integration) -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  /**
   * Admin charts script.
   *
   * Small module that fetches revenue data from the server and renders 3
   * Chart.js visualizations (revenue, tickets, bookings).
   *
   * @module admin/revenueCharts
   * @author Snat
   * @link https://snat.co.uk
   */
  (function(){
    /**
     * Fetch revenue data from the server.
     *
     * @param {number} [days=30] Number of days to request
     * @returns {Promise<Object>} Promise resolving to JSON with arrays: labels, revenue, tickets, bookings
     */
    async function fetchData(days) {
      const res = await fetch('/admin/revenue_data.php?days=' + (days||30));
      return await res.json();
    }
    /**
     * Create a line chart using Chart.js
     * @param {CanvasRenderingContext2D} ctx Canvas 2D context
     * @param {Array<string>} labels X-axis labels
     * @param {Array<number>} data Numeric data points
     * @param {string} label Dataset label
     * @param {string} color CSS color for the line
     * @returns {Chart} Chart.js instance
     */
    function mkLine(ctx, labels, data, label, color) {
      return new Chart(ctx, {
        type: 'line',
        data: { labels: labels, datasets: [{ label: label, data: data, borderColor: color, backgroundColor: color, fill:false, tension:0.2 }] },
        options: { responsive:true, plugins:{ legend:{ display:true }}, scales:{ x:{ display:true }, y:{ beginAtZero:true } } }
      });
    }
    /**
     * Create a bar chart using Chart.js
     * @param {CanvasRenderingContext2D} ctx Canvas 2D context
     * @param {Array<string>} labels X-axis labels
     * @param {Array<number>} data Numeric data points
     * @param {string} label Dataset label
     * @param {string} color CSS color for the bars
     * @returns {Chart} Chart.js instance
     */
    function mkBar(ctx, labels, data, label, color) {
      return new Chart(ctx, {
        type: 'bar', data: { labels: labels, datasets: [{ label: label, data: data, backgroundColor: color }] },
        options: { responsive:true, plugins:{ legend:{ display:true }}, scales:{ x:{ display:true }, y:{ beginAtZero:true } } }
      });
    }
    fetchData(30).then(d => {
      const ctxRev = document.getElementById('revenue-chart').getContext('2d');
      const ctxTickets = document.getElementById('tickets-chart').getContext('2d');
      const ctxBookings = document.getElementById('bookings-chart').getContext('2d');
      // Data as arrays: d.labels, d.revenue, d.tickets, d.bookings
      mkLine(ctxRev, d.labels, d.revenue, 'Daily revenue (£)', 'rgba(32,168,216,1)');
      mkBar(ctxTickets, d.labels, d.tickets, 'Tickets sold', 'rgba(88,165,80,0.8)');
      mkBar(ctxBookings, d.labels, d.bookings, 'Bookings per day', 'rgba(255,150,60,0.8)');
    }).catch(console.error);
  })();
  </script>

<?php require_once ROOT_DIR . '/templates/footer.php'; ?>

<!-- 2025-12-03 13:00 - Integrate Chart.js for revenue charts (placeholder) - author: Snat -->

