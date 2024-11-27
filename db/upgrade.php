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
 * Page module upgrade code
 *
 * This file keeps track of upgrades to
 * the resource module
 *
 * Sometimes, changes between versions involve
 * alterations to database structures and other
 * major things that may break installations.
 *
 * The upgrade function in this file will attempt
 * to perform all the necessary actions to upgrade
 * your older installation to the current version.
 *
 * If there's something it cannot do itself, it
 * will tell you what you need to do.
 *
 * The commands in here will all be database-neutral,
 * using the methods of database_manager class
 *
 * Please do not forget to use upgrade_set_timeout()
 * before any action that may take longer time to finish.
 *
 * @package mod_longpage
 * @copyright  Niels Seidel <niels.seidel@fernuni-hagen.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

function xmldb_longpage_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();
    
    $newversion = 2023091204;
    if ($oldversion < $newversion) {
        
        // longpage_posts
        $table = new xmldb_table('longpage_posts');
        // $name, $type=null, $precision=null, $unsigned=null, $notnull=null, $sequence=null, $default=null, $previous=null
        $field = new xmldb_field('islocked', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, 0, null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // longpage_reading_progress
        $table = new xmldb_table('longpage_reading_progress');
        // ($name, $type=null, $precision=null, $unsigned=null, $notnull=null, $sequence=null, $default=null, $previous=null)
        $field1 = new xmldb_field('section', XMLDB_TYPE_TEXT, '255', null, XMLDB_NOTNULL, null, 0, null, null);
        $field2 = new xmldb_field('sectionhash', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, 0, null, null);
        $field3 = new xmldb_field('course', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, 0, null, null);
        if (!$dbman->field_exists($table, $field1)) {
            $dbman->add_field($table, $field1);
        }
        if (!$dbman->field_exists($table, $field2)) {
            $dbman->add_field($table, $field2);
        }
        if (!$dbman->field_exists($table, $field3)) {
            $dbman->add_field($table, $field3);
        }
        $table = new xmldb_table('longpage');
        $field = new xmldb_field('showreadingcomprehension', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, 1, null);
 
         // Conditionally launch add field id.
         if (!$dbman->field_exists($table, $field)) {
             $dbman->add_field($table, $field);
         }
        upgrade_plugin_savepoint(true,  $newversion, 'mod', 'longpage');
    }

    $newversion = 2022092913;
    if ($oldversion < $newversion) {

        // Define field id to be added to longpage_reading_progress.
        $table = new xmldb_table('longpage_reading_progress');
        $field1 = new xmldb_field('scrollheight', XMLDB_TYPE_NUMBER, '10, 5', null, null, null, null, 'sectionhash');
        $field2 = new xmldb_field('sectioncount', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'scrollheight');

        // Conditionally launch add field id.
        if (!$dbman->field_exists($table, $field1)) {
            $dbman->add_field($table, $field1);
        }
        if (!$dbman->field_exists($table, $field2)) {
            $dbman->add_field($table, $field2);
        }

        // Longpage savepoint reached.
        upgrade_plugin_savepoint(true,  $newversion, 'mod', 'longpage');
    }

    $newversion = 2023051601;
    if ($oldversion < $newversion) {

        // Define field id to be added to longpage_reading_progress.
        $table = new xmldb_table('longpage');
        $field1 = new xmldb_field('showreadingprogress', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, 1, null);
        $field2 = new xmldb_field('showsearch', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, 1, null);
        $field3 = new xmldb_field('showtableofcontents', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, 1, null);
        $field4 = new xmldb_field('showposts', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, 1, null);
        $field5 = new xmldb_field('showhighlights', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, 1, null);
        $field6 = new xmldb_field('showbookmarks', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, 1, null);

        // Conditionally launch add field id.
        if (!$dbman->field_exists($table, $field1)) {
            $dbman->add_field($table, $field1);
        }
        if (!$dbman->field_exists($table, $field2)) {
            $dbman->add_field($table, $field2);
        }

        if (!$dbman->field_exists($table, $field3)) {
            $dbman->add_field($table, $field3);
        }

        if (!$dbman->field_exists($table, $field4)) {
            $dbman->add_field($table, $field4);
        }

        if (!$dbman->field_exists($table, $field5)) {
            $dbman->add_field($table, $field5);
        }

        if (!$dbman->field_exists($table, $field6)) {
            $dbman->add_field($table, $field6);
        }

        // Longpage savepoint reached.
        upgrade_plugin_savepoint(true,  $newversion, 'mod', 'longpage');
    }

    $newversion = 2024112700;
    if ($oldversion < $newversion) {

        // Define field id to be added to longpage.
        $table = new xmldb_table('longpage');
        $field1 = new xmldb_field('showeditquestionsnoai', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, 1, null);
        $field2 = new xmldb_field('showeditquestionsai', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, 1, null);

        // Conditionally launch add fields
        if (!$dbman->field_exists($table, $field1)) {
            $dbman->add_field($table, $field1);
        }
        if (!$dbman->field_exists($table, $field2)) {
            $dbman->add_field($table, $field2);
        }

        // Longpage savepoint reached
        upgrade_plugin_savepoint(true, $newversion, 'mod', 'longpage');
    }

    return true;
}