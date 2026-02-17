<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * selfassess multiple redirect class
 *
 * @package    mod_longpage
 * @subpackage selfassess
 * @copyright  2020 FernUniversität Hagen
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
$limit = new DateTime("2020-10-31 23:59:59");
$link = $CFG->wwwroot . '/course/format/ladtopics/policy.php';

$context = context_system::instance();
global $USER, $PAGE, $DB, $CFG;
$PAGE->set_context($context);
$PAGE->set_url($CFG->wwwroot . '/mod/longpage/blocking-redirect.php');

$PAGE->set_pagelayout('course');
$PAGE->set_title("Kein Zugang");

echo $OUTPUT->header();

echo '<div class="alert alert-danger w-75" role="alert">';
echo '<h4>Kein Zugang</h4><br/>Wir können Ihnen zu dieser Ressource leider ' .
     'keinen Zugang gewähren, da Sie der Untersuchung Ihres Lernverhaltens ' .
     'nicht zugestimmt haben.';

$now = new DateTime();
if ($now < $limit) {
    echo '<br/><br/><button class="btn btn-primary" ' .
         'onclick="javascript:window.location.href=\'' . $link . '\'">Nachträglich ' .
         'der Teilnahme am Forschungsprojekt zustimmen</button>';
}

echo '</div>';
echo $OUTPUT->footer();
