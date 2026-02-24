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
 * Page module admin settings and defaults
 *
 * @package mod_longpage
 * @copyright  2026 Niels Seidel <niels.seidel@fernuni-hagen.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * Custom admin setting to display available Ollama models
 */
if (!class_exists('admin_setting_longpage_models')) {
    class admin_setting_longpage_models extends admin_setting {
    
    public function __construct() {
        parent::__construct(
            'longpage/availablemodels',
            get_string('availablemodels', 'longpage'),
            get_string('availablemodels_desc', 'longpage'),
            ''
        );
    }
    
    public function get_setting() {
        return true;
    }
    
    public function write_setting($data) {
        return '';
    }
    
    public function output_html($data, $query = '') {
        global $OUTPUT;
        
        $url = get_config('longpage', 'aiurl');
        $token = get_config('longpage', 'aitoken');
        
        $html = '<div id="longpage-models-container">';
        
        if (empty($url)) {
            $html .= html_writer::div(
                get_string('configure_url_first', 'longpage'),
                'alert alert-info'
            );
        } else {
            $html .= $this->fetch_and_display_models($url, $token);
        }
        
        $html .= '</div>';
        
        // Add refresh button
        $html .= html_writer::tag('button',
            '<i class="fa fa-refresh"></i> ' . get_string('refreshmodels', 'longpage'),
            [
                'class' => 'btn btn-secondary mt-2',
                'id' => 'longpage-refresh-models',
                'type' => 'button'
            ]
        );
        
        // Add JavaScript for refresh functionality
        $html .= '
        <script>
        document.getElementById("longpage-refresh-models").addEventListener("click", function() {
            var container = document.getElementById("longpage-models-container");
            var button = this;
            button.disabled = true;
            button.innerHTML = \'<i class="fa fa-spinner fa-spin"></i> ' . 
                get_string('loading', 'longpage') . '\';
            
            // Get current URL value from the form
            var urlInput = document.querySelector(\'input[name="s_longpage_aiurl"]\');
            var tokenInput = document.querySelector(\'input[name="s_longpage_aitoken"]\');
            var url = urlInput ? urlInput.value : "";
            var token = tokenInput ? tokenInput.value : "";
            
            if (!url) {
                container.innerHTML = \'<div class="alert alert-info">' . 
                    get_string('configure_url_first', 'longpage') . '</div>\';
                button.disabled = false;
                button.innerHTML = \'<i class="fa fa-refresh"></i> ' . 
                    get_string('refreshmodels', 'longpage') . '\';
                return;
            }
            
            // Make AJAX request
            var xhr = new XMLHttpRequest();
            xhr.open("POST", M.cfg.wwwroot + "/mod/longpage/ajax_check_models.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.onload = function() {
                if (xhr.status === 200) {
                    container.innerHTML = xhr.responseText;
                } else {
                    container.innerHTML = \'<div class="alert alert-danger">Error: \' + xhr.status + \'</div>\';
                }
                button.disabled = false;
                button.innerHTML = \'<i class="fa fa-refresh"></i> ' . 
                    get_string('refreshmodels', 'longpage') . '\';
            };
            xhr.onerror = function() {
                container.innerHTML = \'<div class="alert alert-danger">Connection error</div>\';
                button.disabled = false;
                button.innerHTML = \'<i class="fa fa-refresh"></i> ' . 
                    get_string('refreshmodels', 'longpage') . '\';
            };
            xhr.send("url=" + encodeURIComponent(url) + "&token=" + encodeURIComponent(token));
        });
        </script>';
        
        return format_admin_setting($this, $this->visiblename, $html, $this->description, true, '', '', $query);
    }
    
    private function fetch_and_display_models($url, $token) {
        // Parse URL to get base URL for tags endpoint
        $parts = parse_url($url);
        if (!$parts || !isset($parts['scheme']) || !isset($parts['host'])) {
            return html_writer::div(get_string('invalid_url', 'longpage'), 'alert alert-danger');
        }
        
        $baseurl = $parts['scheme'] . '://' . $parts['host'];
        if (isset($parts['port'])) {
            $baseurl .= ':' . $parts['port'];
        }
        $tagsurl = $baseurl . '/api/tags';
        
        $html = html_writer::tag('p', 
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
            return $html . html_writer::div(
                get_string('connection_error', 'longpage', $error),
                'alert alert-danger'
            );
        } else if ($httpcode !== 200) {
            return $html . html_writer::div(
                get_string('http_error', 'longpage', $httpcode),
                'alert alert-danger'
            );
        }
        
        $data = json_decode($response, true);
        
        if (isset($data['models']) && is_array($data['models']) && count($data['models']) > 0) {
            $html .= html_writer::div(
                get_string('models_found', 'longpage', count($data['models'])),
                'alert alert-success'
            );
            
            $table = new html_table();
            $table->head = [
                get_string('model_name', 'longpage'),
                get_string('model_size', 'longpage'),
                get_string('model_modified', 'longpage')
            ];
            $table->attributes['class'] = 'table table-sm table-striped';
            
            foreach ($data['models'] as $model) {
                $name = $model['name'] ?? '-';
                $size = isset($model['size']) ? display_size($model['size']) : '-';
                $modified = isset($model['modified_at']) ? 
                    userdate(strtotime($model['modified_at']), get_string('strftimedatetime', 'langconfig')) : '-';
                
                $table->data[] = [$name, $size, $modified];
            }
            
            $html .= html_writer::table($table);
            $html .= html_writer::div(
                get_string('copy_model_name', 'longpage'),
                'alert alert-info small'
            );
        } else {
            $html .= html_writer::div(
                get_string('no_models_found', 'longpage'),
                'alert alert-warning'
            );
        }
        
        return $html;
    }
}
}

if ($ADMIN->fulltree) {
    require_once("$CFG->libdir/resourcelib.php");

    // Modedit defaults.
    $settings->add(new admin_setting_heading(
        'pagemodeditdefaults',
        get_string('modeditdefaults', 'admin'),
        get_string('condifmodeditdefaults', 'admin')
    ));

    $settings->add(new admin_setting_configcheckbox(
        'longpage/printheading',
        get_string('printheading', 'longpage'),
        get_string('printheadingexplain', 'longpage'),
        1
    ));
    $settings->add(new admin_setting_configcheckbox(
        'longpage/printintro',
        get_string('printintro', 'longpage'),
        get_string('printintroexplain', 'longpage'),
        0
    ));

    // Activate functionalities.
    $settings->add(new admin_setting_heading(
        'longpage/activatefunctionalities',
        get_string('activatefunctionalities', 'longpage'),
        get_string('activatefunctionalitiesexplain', 'longpage')
    ));
    $settings->add(new admin_setting_configcheckbox(
        'longpage/showreadingprogress',
        get_string('showreadingprogress', 'longpage'),
        get_string('showreadingprogress', 'longpage'),
        1
    ));
    $settings->add(new admin_setting_configcheckbox(
        'longpage/showreadingtime',
        get_string('showreadingtime', 'longpage'),
        get_string('showreadingtime', 'longpage'),
        1
    ));
    $settings->add(new admin_setting_configcheckbox(
        'longpage/showreadingcomprehension',
        get_string('showreadingcomprehension', 'longpage'),
        get_string('showreadingcomprehension', 'longpage'),
        1
    ));
    $settings->add(new admin_setting_configcheckbox(
        'longpage/showsearch',
        get_string('showsearch', 'longpage'),
        get_string('showsearch', 'longpage'),
        1
    ));
    $settings->add(new admin_setting_configcheckbox(
        'longpage/showtableofcontents',
        get_string('showtableofcontents', 'longpage'),
        get_string('showtableofcontents', 'longpage'),
        1
    ));
    $settings->add(new admin_setting_configcheckbox(
        'longpage/showposts',
        get_string('showposts', 'longpage'),
        get_string('showposts', 'longpage'),
        1
    ));
    $settings->add(new admin_setting_configcheckbox(
        'longpage/showhighlights',
        get_string('showhighlights', 'longpage'),
        get_string('showhighlights', 'longpage'),
        1
    ));
    $settings->add(new admin_setting_configcheckbox(
        'longpage/showbookmarks',
        get_string('showbookmarks', 'longpage'),
        get_string('showbookmarks', 'longpage'),
        1
    ));
    $settings->add(new admin_setting_configcheckbox(
        'longpage/showeditquestionsnoai',
        get_string('showeditquestionsnoai', 'longpage'),
        get_string('showeditquestionsnoai_desc', 'longpage'),
        1
    ));

    // AI Question Generation settings.
    $settings->add(new admin_setting_heading(
        'longpage/aiquestiongeneration',
        get_string('aiquestiongeneration', 'longpage'),
        get_string('aiquestiongenerationexplain', 'longpage')
    ));

    $settings->add(new admin_setting_configcheckbox(
        'longpage/enableai',
        get_string('enableai', 'longpage'),
        get_string('enableai_desc', 'longpage'),
        0
    ));

    $settings->add(new admin_setting_configtext(
        'longpage/aiurl',
        get_string('aiurl', 'longpage'),
        get_string('aiurl_desc', 'longpage'),
        'http://catalpa-llm.fernuni-hagen.de:11434/api/chat',
        PARAM_URL
    ));

    $settings->add(new admin_setting_configtext(
        'longpage/aiurlbackup',
        get_string('aiurlbackup', 'longpage'),
        get_string('aiurlbackup_desc', 'longpage'),
        'http://catalpa-llm.fernuni-hagen.de:11434/api/chat',
        PARAM_URL
    ));

    // Display available models inline
    $settings->add(new admin_setting_longpage_models());

    $settings->add(new admin_setting_configtext(
        'longpage/aimodel',
        get_string('aimodel', 'longpage'),
        get_string('aimodel_desc', 'longpage'),
        'llama3.1:latest',
        PARAM_TEXT
    ));

    $settings->add(new admin_setting_configtext(
        'longpage/aitoken',
        get_string('aitoken', 'longpage'),
        get_string('aitoken_desc', 'longpage'),
        '',
        PARAM_TEXT
    ));

    $settings->add(new admin_setting_configtext(
        'longpage/aitimeout',
        get_string('aitimeout', 'longpage'),
        get_string('aitimeout_desc', 'longpage'),
        180,
        PARAM_INT,
        5
    ));
}
