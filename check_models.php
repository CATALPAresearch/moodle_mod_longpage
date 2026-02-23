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
 * Check available models on Ollama server
 *
 * @package    mod_longpage
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

require_login();
require_capability('moodle/site:config', context_system::instance());

$PAGE->set_url('/mod/longpage/check_models.php');
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('checkavailablemodels', 'longpage'));
$PAGE->set_heading(get_string('checkavailablemodels', 'longpage'));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('checkavailablemodels', 'longpage'));

// Get configured URL
$url = get_config('longpage', 'aiurl');
$token = get_config('longpage', 'aitoken');

if (empty($url)) {
    echo $OUTPUT->notification(get_string('aiurl_notconfigured', 'longpage'), 'error');
    echo $OUTPUT->footer();
    exit;
}

// Parse URL to get base URL for tags endpoint
$parts = parse_url($url);
$baseurl = $parts['scheme'] . '://' . $parts['host'];
if (isset($parts['port'])) {
    $baseurl .= ':' . $parts['port'];
}
$tagsurl = $baseurl . '/api/tags';

echo html_writer::tag('p', get_string('checking_models_at', 'longpage', $tagsurl));

// Try to fetch available models
$ch = curl_init($tagsurl);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$headers = ['Content-Type: application/json'];
if (!empty($token)) {
    $headers[] = 'Authorization: Bearer ' . $token;
}
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);

if ($error) {
    echo $OUTPUT->notification(get_string('connection_error', 'longpage', $error), 'error');
} else if ($httpcode !== 200) {
    echo $OUTPUT->notification(get_string('http_error', 'longpage', $httpcode), 'error');
} else {
    $data = json_decode($response, true);
    
    if (isset($data['models']) && is_array($data['models']) && count($data['models']) > 0) {
        echo $OUTPUT->notification(get_string('models_found', 'longpage', count($data['models'])), 'success');
        
        $table = new html_table();
        $table->head = [
            get_string('model_name', 'longpage'),
            get_string('model_size', 'longpage'),
            get_string('model_modified', 'longpage')
        ];
        
        foreach ($data['models'] as $model) {
            $name = $model['name'] ?? '-';
            $size = isset($model['size']) ? display_size($model['size']) : '-';
            $modified = isset($model['modified_at']) ? userdate(strtotime($model['modified_at'])) : '-';
            
            $table->data[] = [$name, $size, $modified];
        }
        
        echo html_writer::table($table);
        
        echo html_writer::tag('p', get_string('copy_model_name', 'longpage'), ['class' => 'alert alert-info']);
        
    } else {
        echo $OUTPUT->notification(get_string('no_models_found', 'longpage'), 'warning');
        echo html_writer::tag('pre', htmlspecialchars($response));
    }
}

echo html_writer::tag('p', 
    html_writer::link(
        new moodle_url('/admin/settings.php', ['section' => 'modsettinglongpage']),
        get_string('back_to_settings', 'longpage'),
        ['class' => 'btn btn-secondary']
    )
);

echo $OUTPUT->footer();
