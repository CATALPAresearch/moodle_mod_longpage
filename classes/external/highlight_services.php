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
 * Highlight related external API
 *
 * @package    mod_longpage
 * @category   external
 * @copyright  2020 Niels Seidel <niels.seidel@fernuni-hagen.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.0
 */

namespace mod_longpage\external;

defined('MOODLE_INTERNAL') || die;

global $CFG;
require_once("$CFG->libdir/externallib.php");

use invalid_parameter_exception;
use mod_longpage\local\constants\annotation_type;

/**
 * Highlight related external functions
 *
 * @package    mod_longpage
 * @category   external
 * @copyright  2020 Niels Seidel <niels.seidel@fernuni-hagen.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.0
 */
class highlight_services extends base_external {
    /**
     * Delete highlight.
     *
     * @param int $id Highlight ID
     */
    public static function delete_highlight($id) {
        global $DB;

        \mod_longpage_external::delete_annotation($id);
    }

    /**
     * Parameters for delete_highlight.
     *
     * @return external_function_parameters
     */
    public static function delete_highlight_parameters() {
        return new \external_function_parameters([
            'id' => new \external_value(PARAM_INT),
        ]);
    }

    /**
     * Return structure for delete_highlight.
     *
     * @return null
     */
    public static function delete_highlight_returns() {
        return null;
    }

    /**
     * Update highlight.
     *
     * @param int $id Highlight ID
     * @param string $styleclass Style class
     * @return array
     */
    public static function update_highlight($id, $styleclass) {
        global $DB;

        self::validate_parameters(\mod_longpage_external::create_annotation_parameters(), ['id' => $id, 'styleclass' => $styleclass]);
        $annotation = $DB->get_record('longpage_annotations', ['id' => $id]);
        $context = self::validate_cm_context($annotation->longpageid);
        require_capability('mod/longpage:addpost', $context);

        self::validate_highlight_can_be_deleted_and_updated($annotation);

        $transaction = $DB->start_delegated_transaction();
        $DB->update_record('longpage_annotation_targets', ['annotationid' => $id, 'styleclass' => $styleclass]);
        $DB->update_record('longpage_annotations', ['id' => $id, 'timemodified' => time()]);
        $transaction->allow_commit();

        return [
            'annotation' => \mod_longpage_external::get_annotations([
                'longpageid' => $annotation->longpageid,
                'annotationid' => $id,
            ])['annotations'][0],
        ];
    }

    /**
     * Parameters for update_highlight.
     *
     * @return external_function_parameters
     */
    public static function update_highlight_parameters() {
        return new \external_function_parameters([
            'id' => new \external_value(PARAM_INT),
            'styleclass' => new \external_value(PARAM_TEXT),
        ]);
    }

    /**
     * Return structure for update_highlight.
     *
     * @return external_single_structure
     */
    public static function update_highlight_returns() {
        return \mod_longpage_external::create_annotation_returns();
    }

    /**
     * Validate highlight can be deleted and updated.
     *
     * @param object $highlight Highlight object
     * @return void
     */
    private static function validate_highlight_can_be_deleted_and_updated($highlight): void {
        global $USER;

        if ($USER->id !== $highlight->creatorid) {
            throw new invalid_parameter_exception('Highlight cannot be updated by user other than its creator.');
        }
        if ($highlight->type !== annotation_type::HIGHLIGHT) {
            throw new invalid_parameter_exception('Annotation is no highlight. ' .
                'Only highlights can be updated by using this method.');
        }
    }
}
