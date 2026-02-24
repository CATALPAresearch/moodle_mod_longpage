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
 * AJAX handler for checking available models
 *
 * @package    mod_longpage
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../config.php');

require_login();
require_capability('moodle/site:config', context_system::instance());

$url = optional_param('url', '', PARAM_URL);
$token = optional_param('token', '', PARAM_TEXT);

if (empty($url)) {
    echo html_writer::div(
        get_string('configure_url_first', 'longpage'),
        'alert alert-info'
    );
    exit;
}

// Parse URL to get base URL for tags endpoint
$parts = parse_url($url);
if (!$parts || !isset($parts['scheme']) || !isset($parts['host'])) {
    echo html_writer::div(get_string('invalid_url', 'longpage'), 'alert alert-danger');
    exit;
}

$baseurl = $parts['scheme'] . '://' . $parts['host'];
if (isset($parts['port'])) {
    $baseurl .= ':' . $parts['port'];
}
$tagsurl = $baseurl . '/api/tags';

echo html_writer::tag(
    'p',
    get_string('checking_models_at', 'longpage', $tagsurl),
    ['class' => 'text-muted small']
);

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
    echo html_writer::div(
        get_string('connection_error', 'longpage', $error),
        'alert alert-danger'
    );
    exit;
}

if ($httpcode !== 200) {
    echo html_writer::div(
        get_string('http_error', 'longpage', $httpcode),
        'alert alert-danger'
    );
    exit;
}

$data = json_decode($response, true);

if (isset($data['models']) && is_array($data['models']) && count($data['models']) > 0) {
    echo html_writer::div(
        get_string('models_found', 'longpage', count($data['models'])),
        'alert alert-success'
    );

    $table = new html_table();
    $table->head = [
        get_string('model_name', 'longpage'),
        get_string('model_size', 'longpage'),
        get_string('model_modified', 'longpage'),
    ];
    $table->attributes['class'] = 'table table-sm table-striped';

    foreach ($data['models'] as $model) {
        $name = $model['name'] ?? '-';
        $size = isset($model['size']) ? display_size($model['size']) : '-';
        $modified = isset($model['modified_at']) ?
            userdate(strtotime($model['modified_at']), get_string('strftimedatetime', 'langconfig')) : '-';

        $table->data[] = [$name, $size, $modified];
    }

    echo html_writer::table($table);
    echo html_writer::div(
        get_string('copy_model_name', 'longpage'),
        'alert alert-info small'
    );
} else {
    echo html_writer::div(
        get_string('no_models_found', 'longpage'),
        'alert alert-warning'
    );
    if ($response) {
        echo html_writer::tag('pre', htmlspecialchars($response), ['class' => 'small']);
    }
}
