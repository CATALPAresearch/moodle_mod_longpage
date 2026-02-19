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
 * Debug script to check TinyMCE editor configuration
 *
 * @package    mod_longpage
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_login();

if (!is_siteadmin()) {
    throw new moodle_exception('nopermissions');
}

// Check current editor preferences
$editornames = array_keys(editors_get_available());
$preferrededitor = get_user_preferences('htmleditor', '');

echo "<!DOCTYPE html>\n";
echo "<html><head><title>Longpage Editor Debug</title></head><body>\n";
echo "<h1>Longpage Editor Configuration Debug</h1>\n";

echo "<h2>Available Editors:</h2>\n";
echo "<ul>\n";
foreach ($editornames as $editorname) {
    echo "<li>" . s($editorname) . "</li>\n";
}
echo "</ul>\n";

echo "<h2>User Preferred Editor:</h2>\n";
echo "<p>" . s($preferrededitor ?: 'Default (system choice)') . "</p>\n";

echo "<h2>System Theme:</h2>\n";
echo "<p>" . s($CFG->theme) . "</p>\n";

echo "<h2>JavaScript Cache Info:</h2>\n";
echo "<p>Cache revision: " . s($CFG->jsrev ?? 'Not set') . "</p>\n";

echo "<h2>Moodle Version:</h2>\n";
echo "<p>" . s($CFG->version . " (" . $CFG->release . ")") . "</p>\n";

echo "<h2>TinyMCE Specific Checks:</h2>\n";
echo "<ul>\n";
if (in_array('tiny', $editornames)) {
    echo "<li>✅ TinyMCE (tiny) editor available</li>\n";
} else {
    echo "<li>❌ TinyMCE (tiny) editor not available</li>\n";    
}

if (in_array('atto', $editornames)) {
    echo "<li>✅ Atto editor available</li>\n";
} else {
    echo "<li>❌ Atto editor not available</li>\n";    
}

// Check if embedquestion plugins are installed
$plugins_installed = [];
if (file_exists($CFG->dirroot . '/filter/embedquestion/filter.php')) {
    $plugins_installed[] = 'filter_embedquestion';
}
if (file_exists($CFG->dirroot . '/atto/plugins/embedquestion/version.php')) {
    $plugins_installed[] = 'atto_embedquestion';
}
if (file_exists($CFG->dirroot . '/report/embedquestion/index.php')) {
    $plugins_installed[] = 'report_embedquestion';
}

echo "<li>Embedquestion plugins found: " . s(empty($plugins_installed) ? 'None' : implode(', ', $plugins_installed)) . "</li>\n";

// Check document mode
echo "<li>Current page DOCTYPE: " . (strpos($_SERVER['HTTP_USER_AGENT'], 'Trident') !== false ? 'IE compatibility check needed' : 'Modern browser') . "</li>\n";

echo "</ul>\n";

echo "<h2>Longpage Module Specific:</h2>\n";
echo "<ul>\n";
echo "<li>editor_fix.js exists: " . (file_exists(__DIR__ . '/editor_fix.js') ? '✅ Yes' : '❌ No') . "</li>\n";
echo "<li>mod_form.php exists: " . (file_exists(__DIR__ . '/mod_form.php') ? '✅ Yes' : '❌ No') . "</li>\n";

// Check if we're in editing context
$is_edit_context = (
    strpos($_SERVER['REQUEST_URI'], 'modedit.php') !== false ||
    strpos($_SERVER['REQUEST_URI'], 'debug_editor.php') !== false ||
    strpos($_SERVER['HTTP_REFERER'] ?? '', 'modedit.php') !== false
);
echo "<li>Current context: " . ($is_edit_context ? '⚠️ Editing context detected' : '✅ Normal viewing context') . "</li>\n";
echo "</ul>\n";

echo "<h2>Recommendations:</h2>\n";
echo "<ol>\n";
echo "<li>✅ Clear Moodle cache: Site administration → Development → Purge all caches</li>\n";
echo "<li>✅ Clear browser cache and hard refresh (Ctrl+Shift+R / Cmd+Shift+R)</li>\n";
if (!in_array('tiny', $editornames)) {
    echo "<li>❌ Install TinyMCE editor or check if it's enabled</li>\n";    
}
if (in_array('atto_embedquestion', $plugins_installed)) {
    echo "<li>⚠️ Consider temporarily disabling atto_embedquestion plugin for testing</li>\n";
}
echo "<li>✅ Check browser console for specific JavaScript errors in module edit form</li>\n";
echo "<li>✅ Test with different editor preference: User menu → Preferences → Editor preferences</li>\n";
echo "</ol>\n";

echo "<h2>Testing Links:</h2>\n";
echo "<ul>\n";
echo "<li><a href=\"" . s($CFG->wwwroot) . "/admin/settings.php?section=manageeditors\">Manage text editors</a></li>\n";
echo "<li><a href=\"" . s($CFG->wwwroot) . "/user/preferences.php\">User preferences</a></li>\n";
echo "<li><a href=\"" . s($CFG->wwwroot) . "/admin/purgecaches.php\">Purge all caches</a></li>\n";
echo "</ul>\n";

echo "<hr><p><small>Debug info: " . date('Y-m-d H:i:s') . " | User: " . format_string($USER->username) . "</small></p>\n";

echo "</body></html>\n";