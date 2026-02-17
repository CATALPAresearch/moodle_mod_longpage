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
 * Author class.
 *
 * @package    mod_longpage
 * @copyright  2021 Adrian Stritzinger <adrian.stritzinger@studium.fernuni-hagen.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_longpage\local\constants;

defined('MOODLE_INTERNAL') || die();

/**
 * Author class.
 *
 * @copyright  2021 Adrian Stritzinger <adrian.stritzinger@studium.fernuni-hagen.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class selector {
    /** @var int Text quote selector type */
    const TYPE_TEXT_QUOTE_SELECTOR = 0;
    /** @var int Text position selector type */
    const TYPE_TEXT_POSITION_SELECTOR = 1;
    /** @var int Range selector type */
    const TYPE_RANGE_SELECTOR = 2;

    /** @var string Text quote selector table name */
    const TABLE_NAME_TEXT_QUOTE_SELECTOR = 'longpage_text_quote_selectrs';
    /** @var string Text position selector table name */
    const TABLE_NAME_TEXT_POSITION_SELECTOR = 'longpage_text_pos_selectors';
    /** @var string Range selector table name */
    const TABLE_NAME_RANGE_SELECTOR = 'longpage_range_selectors';

    /** @var array Mapping from type to table name */
    private static $typetotablenamemapping = [
        self::TYPE_TEXT_QUOTE_SELECTOR => self::TABLE_NAME_TEXT_QUOTE_SELECTOR,
        self::TYPE_TEXT_POSITION_SELECTOR => self::TABLE_NAME_TEXT_POSITION_SELECTOR,
        self::TYPE_RANGE_SELECTOR => self::TABLE_NAME_RANGE_SELECTOR,
    ];

    /** @var array Mapping from table name to type */
    private static $tablenametotypemapping;

    /**
     * Map table name to selector type.
     *
     * @param string $tablename The table name
     * @return int The selector type
     */
    public static function map_table_name_to_type($tablename) {
        if (!isset(self::$tablenametotypemapping)) {
            self::$tablenametotypemapping = array_flip(self::$typetotablenamemapping);
        }
        return self::$tablenametotypemapping[$tablename];
    }

    /**
     * Map selector type to table name.
     *
     * @param int $type The selector type
     * @return string The table name
     */
    public static function map_type_to_table_name($type) {
        return self::$typetotablenamemapping[$type];
    }
}
