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
 * Standalone page to accept Moodle's core_ai usage policy.
 *
 * core_ai only ships a modal (ai/templates/policymodal.mustache, triggered
 * from within a placement's own UI) — there is no core page with its own
 * URL to link/open in a new tab, which is what AI-based question generation
 * needs when it detects the policy hasn't been accepted yet (see
 * question_services::chat()). This page fills that gap: same core_ai
 * strings/content as the modal, same manager::user_policy_accepted() call.
 *
 * @package mod_longpage
 * @copyright  2026 Niels Seidel <niels.seidel@fernuni-hagen.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

require_login();

$cmid = optional_param('cmid', 0, PARAM_INT);
$context = $cmid ? context_module::instance($cmid) : context_system::instance();

$pageurl = new moodle_url('/mod/longpage/ai_policy.php', ['cmid' => $cmid]);
$PAGE->set_url($pageurl);
$PAGE->set_context($context);
$PAGE->set_title(get_string('aipolicypagetitle', 'longpage'));
$PAGE->set_heading(get_string('aipolicypagetitle', 'longpage'));
$PAGE->set_pagelayout('standard');

$usercontext = context_user::instance($USER->id);
require_capability('moodle/ai:acceptpolicy', $usercontext);

if (data_submitted() && confirm_sesskey() && optional_param('accept', 0, PARAM_BOOL)) {
    \core_ai\manager::user_policy_accepted($USER->id, $context->id);
    redirect($pageurl);
}

echo $OUTPUT->header();

if (\core_ai\manager::get_user_policy_status($USER->id)) {
    echo $OUTPUT->notification(get_string('aipolicyalreadyaccepted', 'longpage'), 'success');
    echo html_writer::div(get_string('aipolicyclosetab', 'longpage'));
} else {
    echo html_writer::tag('h3', get_string('aiusagepolicy', 'core_ai'));
    echo html_writer::div(get_string('userpolicy', 'core_ai'), 'ai-policy-display mb-4');

    echo html_writer::start_tag('form', ['method' => 'post', 'action' => $pageurl->out(false)]);
    echo html_writer::input_hidden_params($pageurl);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'accept', 'value' => 1]);
    echo html_writer::tag(
        'button',
        get_string('acceptai', 'core_ai'),
        ['type' => 'submit', 'class' => 'btn btn-primary']
    );
    echo html_writer::end_tag('form');
}

echo $OUTPUT->footer();
