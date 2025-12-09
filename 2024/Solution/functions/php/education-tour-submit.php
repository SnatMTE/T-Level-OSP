<?php
/**
 * Education tour request submission handler.
 *
 * Performs suitability checks for education tours and returns a friendly
 * response indicating whether the request is preliminarily approved or
 * needs additional follow-up. This is not stored in the database yet by
 * default and is intended as a lead/contact workflow.
 *
 * @package RigetZoo
 * @author Snat
 * @link https://snat.co.uk
 */
session_start();

if (!defined('ROOT_DIR')) {
  // Use a PHP-version-compatible way to walk two directories up
  define('ROOT_DIR', dirname(dirname(__DIR__)));
}

require_once ROOT_DIR . '/functions/php/helpers.php';
// Respect BASE_URL for redirects
$redirectBase = (defined('BASE_URL') && BASE_URL) ? BASE_URL : '';

// Simple suitability checks for education tours
function sanitize($v){ return trim(htmlspecialchars((string)$v)); }

 $school = sanitize(isset($_POST['school']) ? $_POST['school'] : '');
 $contact = sanitize(isset($_POST['contact']) ? $_POST['contact'] : '');
 $email = sanitize(isset($_POST['email']) ? $_POST['email'] : '');
// override contact/email using session if logged in - do not trust client supplied data for authenticated users
$u = function_exists('current_user') ? current_user() : null;
if ($u) {
  $contact = trim((isset($u['first_name']) ? $u['first_name'] : '') . ' ' . (isset($u['surname']) ? $u['surname'] : ''));
  $email = isset($u['email']) ? $u['email'] : '';
}
$phone = sanitize(isset($_POST['phone']) ? $_POST['phone'] : '');
$date = sanitize(isset($_POST['date']) ? $_POST['date'] : '');
$group_size = intval(isset($_POST['group_size']) ? $_POST['group_size'] : 0);
$age_range = sanitize(isset($_POST['age_range']) ? $_POST['age_range'] : '');
$mobility = sanitize(isset($_POST['mobility']) ? $_POST['mobility'] : 'no');
$allergies = sanitize(isset($_POST['allergies']) ? $_POST['allergies'] : 'no');
$behaviour = sanitize(isset($_POST['behaviour']) ? $_POST['behaviour'] : 'no');
$length = sanitize(isset($_POST['length']) ? $_POST['length'] : 'standard');
$notes = sanitize(isset($_POST['notes']) ? $_POST['notes'] : '');

$issues = [];

// Rule: very large groups are unsuitable without breaking into smaller groups
if ($group_size <= 0) {
    $issues[] = 'Please specify a valid group size.';
} elseif ($group_size > 60) {
    $issues[] = 'Group size exceeds our recommended maximum (60). Consider splitting into smaller groups.';
}

// Mobility: if there are mobility issues we flag as requiring special arrangements
if ($mobility === 'yes') {
    $issues[] = 'Mobility needs noted — the standard tour may not be suitable without special arrangements. Please contact us to discuss accessibility requirements.';
}

// Allergies: we flag and request follow-up
if ($allergies === 'yes') {
    $issues[] = 'Severe allergies noted — we will contact you for details. Some animals or areas may require extra precautions.';
}

// Behaviour concerns: for larger groups this needs extra support
if ($behaviour === 'yes' && $group_size > 20) {
    $issues[] = 'Behavioural support needs reported. For groups over 20 we require additional supervising adults; please ensure adequate staffing.';
}

// Age-specific guidance
if ($age_range === '3-5') {
    $issues[] = 'For very young children (3–5) we recommend a shortened, hands-on session with extra adult supervision.';
}

// Additional validation for contact email/date
if (!$u) {
  if (empty($contact)) { $issues[] = 'Please provide a contact name'; }
  if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) { $issues[] = 'Please provide a valid contact email'; }
}
if (!empty($date) && !validate_date($date)) {
  $issues[] = 'Provided date is not in a valid YYYY-MM-DD format';
}

$suitable = empty($issues);

// Instead of normally rendering a separate outcome page, set a flash message and redirect
if ($suitable) {
  add_flash('success', 'Education tour request received — our education team will contact ' . htmlspecialchars($email));
  $status = 'pending';
} else {
  add_flash('error', 'Request requires follow-up: ' . implode(' | ', $issues));
  $status = 'followup';
}
// Persist request to DB
$meta = json_encode(['issues'=>$issues,'submitted_by' => $u ? $u['id'] : null]);
try {
  $pdo = Database::pdo();
  $stmt = $pdo->prepare('INSERT INTO education_requests (school,contact,email,phone,date,group_size,age_range,mobility,allergies,behaviour,length,notes,status,meta,created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
  $stmt->execute([$school,$contact,$email,$phone,$date,$group_size,$age_range,$mobility,$allergies,$behaviour,$length,$notes,$status,$meta,date('c')]);
} catch (Exception $e) {
  // log but continue
}
header('Location: ' . $redirectBase . '/education-tour.php');
exit;

?>
