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

namespace mod_longpage\event;

/**
 * The course module scroll event.
 *
 * @property-read array $other {
 * }
 *
 * @package   mod_longpage
 * @category  event
 * @copyright
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_module_scroll extends \core\event\base {
    /**
     * Init method.
     *
     * @return void
     */
    protected function init() {
        $this->data['objecttable'] = 'longpage';
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        $other = $this->data["other"];
        $targetid = $other['targetID'];
        $scrolltop = $other['scrollTop'];

        return "The user with id '$this->userid' scrolled on the 'longpage activity' with course module id " .
                "'$this->contextinstanceid' in course '$this->courseid' to section '$targetid' " .
                "($scrolltop px from top).";
    }

    /**
     * Custom validation.
     *
     * @throws \coding_exception
     * @return void
     */
    protected function validate_data() {
        parent::validate_data();

        if (!isset($this->other['targetID'])) {
            throw new \coding_exception('The \'targetID\' value must be set in other.');
        }
    }

    /**
     * Return the mapping for object ID to database table.
     *
     * @return array
     */
    public static function get_objectid_mapping() {
        return ['db' => 'longpage', 'restore' => 'longpage'];
    }
}
