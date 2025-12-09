<?php
/**
 * Education tour booking page.
 *
 * Page to request or submit interest in the zoo's education tours.
 *
 * @package RigetZoo
 * @author Snat
 * @link https://snat.co.uk
 */
session_start();

if (!defined('ROOT_DIR')) {
    define('ROOT_DIR', __DIR__);
}

require_once ROOT_DIR . '/functions/php/helpers.php';
require_once ROOT_DIR . '/functions/php/auth.php';

$pageTitle = 'Education Tour Booking - Riget Zoo Adventures';
require_once ROOT_DIR . '/templates/header.php';
?>

<style>
  .tour-wrap{max-width:800px;margin:0 auto}
  .tour-form label{display:block;margin-bottom:10px}
  .tour-form input,.tour-form select,.tour-form textarea{width:100%;box-sizing:border-box;padding:6px}
</style>

<div class="tour-wrap">
  <h2>Book an Education Tour</h2>
  <p>Please complete the short suitability questionnaire below so we can make sure the tour is right for your group.</p>

  <?php
    $u = function_exists('current_user') ? current_user() : null;
    $prefill_contact = $u ? ($u['first_name'] . ' ' . $u['surname']) : '';
    $prefill_email = $u ? $u['email'] : '';
  ?>
  <form class="tour-form" method="post" action="/functions/php/education-tour-submit.php">
    <label>School / Group name
      <input type="text" name="school" required>
    </label>

    <?php if (!$u): ?>
      <label>Contact name
        <input type="text" name="contact" required>
      </label>
    <?php else: ?>
      <div><strong>Contact:</strong> <?php echo htmlspecialchars($prefill_contact, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <?php if (!$u): ?>
      <label>Contact email
        <input type="email" name="email" required>
      </label>
    <?php else: ?>
      <div><strong>Email:</strong> <?php echo htmlspecialchars($prefill_email, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <label>Contact phone
      <input type="text" name="phone">
    </label>

    <label>Proposed date
      <input type="date" name="date">
    </label>

    <label>Group size
      <input type="number" name="group_size" min="1" value="30" required>
    </label>

    <label>Age range
      <select name="age_range">
        <option value="3-5">3–5</option>
        <option value="6-10" selected>6–10</option>
        <option value="11-16">11–16</option>
        <option value="17+">17+</option>
      </select>
    </label>

    <label>Any mobility issues to declare?
      <select name="mobility">
        <option value="no" selected>No</option>
        <option value="yes">Yes — needs assistance</option>
      </select>
    </label>

    <label>Any severe allergies?
      <select name="allergies">
        <option value="no" selected>No</option>
        <option value="yes">Yes</option>
      </select>
      <small>We will contact you for details if you answer yes.</small>
    </label>

    <label>Any behavioural concerns or special support needs?
      <select name="behaviour">
        <option value="no" selected>No</option>
        <option value="yes">Yes</option>
      </select>
    </label>

    <label>Desired tour length
      <select name="length">
        <option value="short">Short (30 mins)</option>
        <option value="standard" selected>Standard (60 mins)</option>
        <option value="extended">Extended (90 mins)</option>
      </select>
    </label>

    <label>Additional notes
      <textarea name="notes" rows="4"></textarea>
    </label>

    <button type="submit">Check suitability & Request booking</button>
  </form>
</div>

<?php
require_once ROOT_DIR . '/templates/footer.php';

?>
