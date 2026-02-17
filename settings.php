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
 * @copyright  2009 Petr Skoda (http://skodak.org)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    require_once("$CFG->libdir/resourcelib.php");

    $displayoptions = resourcelib_get_displayoptions([RESOURCELIB_DISPLAY_OPEN, RESOURCELIB_DISPLAY_POPUP]);
    $defaultdisplayoptions = [RESOURCELIB_DISPLAY_OPEN];

    // General settings.
    $settings->add(new admin_setting_configmultiselect(
        'longpage/displayoptions',
        get_string('displayoptions', 'longpage'),
        get_string('configdisplayoptions', 'longpage'),
        $defaultdisplayoptions,
        $displayoptions
    ));

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
    $settings->add(new admin_setting_configselect(
        'longpage/display',
        get_string('displayselect', 'longpage'),
        get_string('displayselectexplain', 'longpage'),
        RESOURCELIB_DISPLAY_OPEN,
        $displayoptions
    ));
    $settings->add(new admin_setting_configtext(
        'longpage/popupwidth',
        get_string('popupwidth', 'longpage'),
        get_string('popupwidthexplain', 'longpage'),
        620,
        PARAM_INT,
        7
    ));
    $settings->add(new admin_setting_configtext(
        'longpage/popupheight',
        get_string('popupheight', 'longpage'),
        get_string('popupheightexplain', 'longpage'),
        450,
        PARAM_INT,
        7
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
    $settings->add(new admin_setting_configcheckbox(
        'longpage/showeditquestionsai',
        get_string('showeditquestionsai', 'longpage'),
        get_string('showeditquestionsai_desc', 'longpage'),
        1
    ));
}
