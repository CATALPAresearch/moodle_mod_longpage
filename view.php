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
 * Page module version information
 *
 * @package mod_longpage
 * @copyright  2009 Petr Skoda (http://skodak.org)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once($CFG->dirroot . '/mod/longpage/lib.php');
require_once($CFG->dirroot . '/mod/longpage/locallib.php');
require_once($CFG->libdir . '/completionlib.php');
require_once("$CFG->libdir/formslib.php");

$id      = optional_param('id', 0, PARAM_INT); // Course Module ID.
$p       = optional_param('p', 0, PARAM_INT);  // Page instance ID.
$inpopup = optional_param('inpopup', 0, PARAM_BOOL);

if ($p) {
    if (!$page = $DB->get_record('longpage', ['id' => $p])) {
        throw new moodle_exception('invalidaccessparameter');
    }
    $cm = get_coursemodule_from_instance('longpage', $page->id, $page->course, false, MUST_EXIST);
} else {
    if (!$cm = get_coursemodule_from_id('longpage', $id)) {
        throw new moodle_exception('invalidcoursemodule');
    }
    $page = $DB->get_record('longpage', ['id' => $cm->instance], '*', MUST_EXIST);
}

$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);

require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/longpage:view', $context);

$scrolltop = $DB->get_field_sql(
    "SELECT scrolltop FROM {longpage_reading_progress} " .
    "WHERE userid = :userid AND longpageid = :longpageid " .
    "ORDER BY timemodified DESC LIMIT 1",
    ['userid' => $USER->id, 'longpageid' => $page->id]
);

// Completion and trigger events.
longpage_view($page, $course, $cm, $context);

$PAGE->set_url('/mod/longpage/view.php', ['id' => $cm->id]);
$PAGE->requires->css('/mod/longpage/styles.css', true);

// Longpage always uses standard display mode (no popup).
$PAGE->set_title($course->shortname . ': ' . $page->name);
$PAGE->set_heading($course->fullname);

$PAGE->set_secondary_navigation(false);
$PAGE->activityheader->disable();
set_user_preference('drawer-open-index', false);
set_user_preference('drawer-open-block', false);
set_user_preference('drawer-open-nav', false);

echo $OUTPUT->header();

/**
 * Get formatted page content.
 *
 * @param object $page Page object.
 * @param context $context Context object.
 * @return string Formatted content.
 */
function get_formatted_page_content($page, $context) {
    $content = file_rewrite_pluginfile_urls(
        $page->content,
        'pluginfile.php',
        $context->id,
        'mod_longpage',
        'content',
        $page->revision
    );
    $formatoptions = new stdClass();
    $formatoptions->noclean = true;
    $formatoptions->context = $context;
    return format_text($content, $page->contentformat, $formatoptions);
}

if (mod_longpage\blocking::tool_policy_accepted() == true) {
    $content = get_formatted_page_content($page, $context);

    // Always display heading for longpage.
    echo '<h2 style="display:inline;">' . format_string($page->name) . '</h2>';
    // Intro box hidden per user request
    // echo $OUTPUT->box(
    //     format_module_intro('longpage', $page, $cm->id),
    //     'generalbox',
    //     'intro'
    // );
    // Dropdown for other longpages hidden per user request
    // echo '<div class="dropdown" style="display: inline;">' .
    //     '<button class="btn btm-sm dropdown-toggle" type="button" ' .
    //     'id="dropdownPages" data-toggle="dropdown" ' .
    //     'aria-haspopup="true" aria-expanded="false" ' .
    //     'style="padding: 0; background: none;"></button>' .
    //     '<div class="dropdown-menu dropdown-menu-right" ' .
    //     'aria-labelledby="dropdownPages" style="width: 300px;">';
    // $pages = get_all_instances_in_courses("longpage", [$course->id => $course]);
    // foreach ($pages as $p) {
    //     $pcm = get_coursemodule_from_instance('longpage', $p->id, $course->id);
    //     $cxt = context_module::instance($cm->id);
    //     if (!has_capability('mod/longpage:view', $cxt)) {
    //         continue;
    //     }
    //     if ($pcm->id != $cm->id) {
    //         echo '<a class="dropdown-item" href="/mod/longpage/view.php?id=' . $pcm->id . '" target="_blank">' . $p->name . '</a>';
    //     }
    // }
    // echo    '</div>
    //     </div>';
    
    // Hidden form needed for embedding questions.
    $embedform = new MoodleQuickForm(
        'embedform',
        'POST',
        '',
        '',
        ['style' => 'width: 0; height: 0; overflow: hidden']
    );
    $embedform->addElement('textarea', 'embedform', 'embedform');
    $embedform->display();

    // Get tags.
    $tags = \core_tag_tag::get_item_tags('core', 'course_modules', $id);
    $tagstr = [];
    foreach ($tags as $tag) {
        $tagstr[] = $tag->rawname;
    }

    echo '<div id="longpage-app-container" class="border-top border-bottom" data-moodle-release="'.$CFG->release.'">';
    echo '<div class="row no-gutters vh-50">';
    echo '<div class="spinner-border m-auto " role="status"><span class="sr-only">'.get_string('loading').'</span></div>';
    echo '</div></div>';
    echo '<div id="longpage-tmp" style="display:none;" lang="de">';
    echo $page->content;
    echo '</div>';

    $PAGE->requires->js_call_amd(
        'mod_longpage/app-lazy',
        'init',
        [
            $course->id,
            $page->id,
            format_string($page->name),
            $USER->id,
            $content,
            $scrolltop,
            !empty($page->showreadingprogress),
            !empty($page->showreadingtime),
            !empty($page->showreadingcomprehension),
            !empty($page->showsearch),
            !empty($page->showtableofcontents),
            !empty($page->showposts),
            !empty($page->showhighlights),
            !empty($page->showbookmarks),
            !empty($page->showeditquestionsnoai),
            !empty($page->showeditquestionsai),
            $tagstr,
            is_siteadmin()
        ]
    );
} else {
    echo "Umleitung";
    $url = new moodle_url('/mod/longpage/blocking-redirect.php');
    redirect($url);
}


echo $OUTPUT->footer();
