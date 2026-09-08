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

/**
 * Longpage module upgrade function.
 *
 * @param int $oldversion The old version of the module.
 * @return bool Always returns true.
 */
function xmldb_longpage_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    $newversion = 2023091204;
    if ($oldversion < $newversion) {
        // Longpage_posts table.
        $table = new xmldb_table('longpage_posts');
        // Define field parameters for islocked column.
        $field = new xmldb_field('islocked', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, 0, null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Longpage_reading_progress table.
        $table = new xmldb_table('longpage_reading_progress');
        // Name, type, precision, unsigned, notnull, sequence, default, previous.
        $field1 = new xmldb_field(
            'section',
            XMLDB_TYPE_TEXT,
            '255',
            null,
            XMLDB_NOTNULL,
            null,
            0,
            null,
            null
        );
        $field2 = new xmldb_field(
            'sectionhash',
            XMLDB_TYPE_INTEGER,
            '10',
            XMLDB_UNSIGNED,
            XMLDB_NOTNULL,
            null,
            0,
            null,
            null
        );
        $field3 = new xmldb_field(
            'course',
            XMLDB_TYPE_INTEGER,
            '10',
            XMLDB_UNSIGNED,
            XMLDB_NOTNULL,
            null,
            0,
            null,
            null
        );
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
        $field = new xmldb_field(
            'showreadingcomprehension',
            XMLDB_TYPE_INTEGER,
            '1',
            null,
            XMLDB_NOTNULL,
            null,
            1,
            null
        );

        // Conditionally launch add field id.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, $newversion, 'mod', 'longpage');
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
        upgrade_plugin_savepoint(true, $newversion, 'mod', 'longpage');
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
        upgrade_plugin_savepoint(true, $newversion, 'mod', 'longpage');
    }

    $newversion = 2024112700;
    if ($oldversion < $newversion) {
        // Define field id to be added to longpage.
        $table = new xmldb_table('longpage');
        $field1 = new xmldb_field('showeditquestionsnoai', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, 1, null);
        $field2 = new xmldb_field('showeditquestionsai', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, 1, null);

        // Conditionally launch add fields.
        if (!$dbman->field_exists($table, $field1)) {
            $dbman->add_field($table, $field1);
        }
        if (!$dbman->field_exists($table, $field2)) {
            $dbman->add_field($table, $field2);
        }

        // Longpage savepoint reached.
        upgrade_plugin_savepoint(true, $newversion, 'mod', 'longpage');
    }

    $newversion = 2026022301;
    if ($oldversion < $newversion) {
        // Define field showreadingtime to be added to longpage.
        $table = new xmldb_table('longpage');
        $field = new xmldb_field('showreadingtime', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, 1, 'showreadingprogress');

        // Conditionally launch add field.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Longpage savepoint reached.
        upgrade_plugin_savepoint(true, $newversion, 'mod', 'longpage');
    }

    $newversion = 2026052501;
    if ($oldversion < $newversion) {
        // Create longpage_adaptive_user table.
        $table = new xmldb_table('longpage_adaptive_user');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('longpageid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('comprehension_points', XMLDB_TYPE_FLOAT, '10', null, null, null, null);
            $table->add_field('comprehension_factor', XMLDB_TYPE_FLOAT, '10', null, null, null, null);
            $table->add_field('streak', XMLDB_TYPE_INTEGER, '1', null, null, null, null);
            $table->add_field('calibrated', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
            $table->add_field('was_calibrated', XMLDB_TYPE_INTEGER, '1', null, null, null, null);
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_key('user', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);
            $dbman->create_table($table);
        }

        // Create longpage_adaptive_question table.
        $table = new xmldb_table('longpage_adaptive_question');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('questionid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('difficulty', XMLDB_TYPE_FLOAT, '10', null, null, null, null);
            $table->add_field('calibrated', XMLDB_TYPE_INTEGER, '1', null, null, null, null);
            $table->add_field('was_calibrated', XMLDB_TYPE_INTEGER, '1', null, null, null, null);
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_key('question', XMLDB_KEY_FOREIGN, ['questionid'], 'question', ['id']);
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, $newversion, 'mod', 'longpage');
    }

    $newversion = 2026090800;
    if ($oldversion < $newversion) {
        // Create longpage_reading_behavior_events table.
        $table = new xmldb_table('longpage_reading_behavior_events');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('longpageid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('sessionid', XMLDB_TYPE_CHAR, '64', null, XMLDB_NOTNULL, null, null);
            $table->add_field('targetid', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
            $table->add_field('targettag', XMLDB_TYPE_CHAR, '16', null, XMLDB_NOTNULL, null, null);
            $table->add_field('wordcount', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0);
            $table->add_field('dwellseconds', XMLDB_TYPE_FLOAT, '10, 3', null, XMLDB_NOTNULL, null, null);
            $table->add_field('peakratio', XMLDB_TYPE_FLOAT, '5, 4', null, XMLDB_NOTNULL, null, null);
            $table->add_field('minreadingtime', XMLDB_TYPE_FLOAT, '10, 3', null, XMLDB_NOTNULL, null, null);
            $table->add_field('avgreadingtime', XMLDB_TYPE_FLOAT, '10, 3', null, XMLDB_NOTNULL, null, null);
            $table->add_field('maxreadingtime', XMLDB_TYPE_FLOAT, '10, 3', null, XMLDB_NOTNULL, null, null);
            $table->add_field('datapointlabel', XMLDB_TYPE_CHAR, '16', null, XMLDB_NOTNULL, null, null);
            $table->add_field('language', XMLDB_TYPE_CHAR, '8', null, XMLDB_NOTNULL, null, null);
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_key('page', XMLDB_KEY_FOREIGN, ['longpageid'], 'longpage', ['id']);
            $table->add_key('user', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);
            $table->add_index('sessionid', XMLDB_INDEX_NOTUNIQUE, ['sessionid']);
            $table->add_index('userpagesession', XMLDB_INDEX_NOTUNIQUE, ['userid', 'longpageid', 'sessionid']);
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, $newversion, 'mod', 'longpage');
    }

    $newversion = 2026090801;
    if ($oldversion < $newversion) {
        // Create longpage_intersection_events table (raw telemetry).
        $table = new xmldb_table('longpage_intersection_events');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('longpageid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('sessionid', XMLDB_TYPE_CHAR, '64', null, XMLDB_NOTNULL, null, null);
            $table->add_field('targetid', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
            $table->add_field('targettag', XMLDB_TYPE_CHAR, '16', null, XMLDB_NOTNULL, null, null);
            $table->add_field('intersectionratio', XMLDB_TYPE_FLOAT, '5, 4', null, XMLDB_NOTNULL, null, null);
            $table->add_field('boundingtop', XMLDB_TYPE_FLOAT, '10, 2', null, XMLDB_NOTNULL, null, null);
            $table->add_field('boundingbottom', XMLDB_TYPE_FLOAT, '10, 2', null, XMLDB_NOTNULL, null, null);
            $table->add_field('boundingheight', XMLDB_TYPE_FLOAT, '10, 2', null, XMLDB_NOTNULL, null, null);
            $table->add_field('boundingwidth', XMLDB_TYPE_FLOAT, '10, 2', null, XMLDB_NOTNULL, null, null);
            $table->add_field('viewportheight', XMLDB_TYPE_FLOAT, '10, 2', null, XMLDB_NOTNULL, null, null);
            $table->add_field('scrolltop', XMLDB_TYPE_FLOAT, '10, 2', null, XMLDB_NOTNULL, null, null);
            $table->add_field('wordcount', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0);
            $table->add_field('clienttimestamp', XMLDB_TYPE_FLOAT, '20, 3', null, XMLDB_NOTNULL, null, null);
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_key('page', XMLDB_KEY_FOREIGN, ['longpageid'], 'longpage', ['id']);
            $table->add_key('user', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);
            $table->add_index('sessionid', XMLDB_INDEX_NOTUNIQUE, ['sessionid']);
            $table->add_index('usersessiontarget', XMLDB_INDEX_NOTUNIQUE, ['userid', 'sessionid', 'targetid']);
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, $newversion, 'mod', 'longpage');
    }

    $newversion = 2026090802;
    if ($oldversion < $newversion) {
        // Add coverageratio to longpage_reading_behavior_events (how much
        // of a long element's own height was actually scrolled through,
        // vs. just peakRatio which is geometrically capped for elements
        // taller than the viewport).
        $table = new xmldb_table('longpage_reading_behavior_events');
        $field = new xmldb_field(
            'coverageratio',
            XMLDB_TYPE_FLOAT,
            '5, 4',
            null,
            XMLDB_NOTNULL,
            null,
            0,
            'peakratio'
        );
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, $newversion, 'mod', 'longpage');
    }

    $newversion = 2026090803;
    if ($oldversion < $newversion) {
        // Rename longpage_reading_progress -> longpage_reading_positions.
        // The old name had become ambiguous once longpage_reading_behavior_
        // events was introduced ("progress" vs "behavior" reads as the same
        // concept but isn't) — this table is really a log of scroll
        // positions, used both to resume the last position (view.php) and
        // to compute the collective per-section read count
        // (ReadingPositionIndicator.vue). rename_table() preserves all
        // existing rows.
        $table = new xmldb_table('longpage_reading_progress');
        if ($dbman->table_exists($table) && !$dbman->table_exists(new xmldb_table('longpage_reading_positions'))) {
            $dbman->rename_table($table, 'longpage_reading_positions');
        }

        upgrade_plugin_savepoint(true, $newversion, 'mod', 'longpage');
    }

    return true;
}
