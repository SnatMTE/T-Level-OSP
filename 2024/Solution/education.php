<?php
/**
 * Education program listing page.
 *
 * Showcases education programs and tours available for schools and groups.
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

$pageTitle = 'Education - Riget Zoo Adventures';
require_once ROOT_DIR . '/templates/header.php';
?>

<style>
  .education-wrap{display:flex;gap:20px}
  .education-content{flex:2}
  .education-form{flex:1;border:1px solid #ddd;padding:12px;background:#fafafa}
  .education-form label{display:block;margin-bottom:8px}
  .education-form input, .education-form textarea, .education-form select{width:100%;box-sizing:border-box;padding:6px;margin-top:4px}
</style>

<h2>Education for Schools</h2>
<p><a href="<?php echo BASE_URL ?: '/'; ?>/education-tour.php">Book an Education Tour</a></p>
<div class="education-wrap">
  <div class="education-content">
    <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris.</p>
    <h3>Program Overview</h3>
    <p>Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia curae; Integer lacinia sollicitudin massa, a tempor massa. Phasellus euismod, lorem at convallis cursus, urna justo fermentum risus, non volutpat est purus vitae sapien.</p>
    <h3>What We Offer</h3>
    <ul>
      <li>Guided tours and talks</li>
      <li>Hands-on workshops</li>
      <li>Curriculum-linked activities</li>
    </ul>
    <p>Curabitur non nulla sit amet nisl tempus convallis quis ac lectus. Donec sollicitudin molestie malesuada. Pellentesque in ipsum id orci porta dapibus.</p>
  </div>

  <aside class="education-form">
    <h3>Contact us for schools</h3>
    <form method="post" action="<?php echo BASE_URL ?: '/'; ?>/functions/php/education-submit.php">
      <label>School name
        <input type="text" name="school" placeholder="School name">
      </label>
      <label>Contact name
        <input type="text" name="contact" placeholder="Contact person">
      </label>
      <label>Email
        <input type="email" name="email" placeholder="you@example.com">
      </label>
      <label>Phone
        <input type="text" name="phone" placeholder="Phone number">
      </label>
      <label>Number of students
        <input type="number" name="students" min="1" value="30">
      </label>
      <label>Requirements / Message
        <textarea name="message" rows="4" placeholder="Tell us what you need"></textarea>
      </label>
      <button type="submit">Request info</button>
    </form>
    <hr style="margin:12px 0"> 
    <p><a href="<?php echo BASE_URL ?: '/'; ?>/education-tour.php">Or book a guided education tour (suitability questionnaire)</a></p>
  </aside>
</div>

<?php
// Education games & resources
?>
<link rel="stylesheet" href="<?php echo BASE_URL ?: '/'; ?>/assets/css/education.css">
<div class="edu-grid" style="margin-top:20px">
  <div>
    <div class="edu-card">
      <h3>Interactive Games</h3>
      <p>Try the short quiz and a matching game to reinforce learning.</p>
      <div id="edu-quiz" class="edu-card" style="margin-bottom:12px">
        <div class="quiz-play">
          <div class="quiz-question"></div>
          <div class="quiz-answers"></div>
          <div style="margin-top:8px"><button class="quiz-next btn">Next</button></div>
        </div>
        <div class="quiz-end"><strong>Result:</strong> <span class="quiz-result"></span></div>
      </div>
      <div id="edu-match" class="edu-card">
        <h4>Match animals to habitats</h4>
        <div class="match-board">
          <div class="match-zone" data-key="lion"><strong>Savanna</strong></div>
          <div class="match-zone" data-key="flamingo"><strong>Wetlands</strong></div>
          <div class="match-zone" data-key="penguin"><strong>Coast/Polar</strong></div>
        </div>
        <div style="margin-top:8px">
          <div class="match-item" data-key="lion">Lion</div>
          <div class="match-item" data-key="flamingo">Flamingo</div>
          <div class="match-item" data-key="penguin">Penguin</div>
        </div>
        <div style="margin-top:8px"><button class="match-check btn">Check Matches</button></div>
      </div>
    </div>
    <div class="edu-card" style="margin-top:12px">
      <h3>Downloadable Materials</h3>
      <ul class="resources-list">
        <li><a href="<?php echo BASE_URL ?: '/'; ?>/education/resources/lesson-plan-primary.html">Lesson Plan — Primary</a></li>
        <li><a href="<?php echo BASE_URL ?: '/'; ?>/education/resources/worksheet_1.html">Worksheet — Habitats</a></li>
      </ul>
    </div>
  </div>
  <aside>
    <div class="edu-card">
      <h3>Teacher Notes</h3>
      <p>Split students into small groups for the matching game and use the quiz as a quick evaluation.</p>
    </div>
  </aside>
</div>
<script src="<?php echo BASE_URL ?: '/'; ?>/assets/js/education.js"></script>
<?php
require_once ROOT_DIR . '/templates/footer.php';
