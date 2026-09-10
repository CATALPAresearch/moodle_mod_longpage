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

    // The LLM server URL/model/API key/sampling parameters used to live here
    // as plugin-specific settings talking directly to an Ollama server via
    // raw curl. Since v4.0 this goes through Moodle's core_ai AI Provider
    // subsystem instead (see aiprovider_longpage), configured under
    // Site administration > General > AI providers, so it stays interchangeable
    // (KI:connect, Ollama, or any other configured provider) instead of being
    // hard-wired to one server here.
    $settings->add(new admin_setting_description(
        'longpage/aiprovidernote',
        '',
        get_string('aiprovidernote', 'longpage')
    ));
}
