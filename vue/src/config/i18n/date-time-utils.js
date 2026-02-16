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
 * @package    mod_page
 * @copyright  2021 Adrian Stritzinger <Adrian.Stritzinger@studium.fernuni-hagen.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
const NOW = new Date();

const StartDateTime = Object.freeze({
    TODAY: new Date(NOW.getFullYear(), NOW.getMonth(), NOW.getDate()),
    THIS_YEAR: new Date(NOW.getFullYear(), 0),
    THIS_DECADE: new Date(Math.floor(NOW.getFullYear() / 10) * 10, 0),
});

export const getDateTimeFormat = (dateTime) => {
    if (dateTime < StartDateTime.THIS_DECADE) return 'dateTime';

    if (dateTime < StartDateTime.THIS_YEAR) return 'dateTimeWithoutCentury';

    if (dateTime < StartDateTime.TODAY) return 'dateTimeWithoutYear';

    return 'time';
};

export const getDateFormat = (dateTime) => {
    if (dateTime < StartDateTime.THIS_DECADE) return 'date';

    if (dateTime < StartDateTime.THIS_YEAR) return 'dateWithoutCentury';

    return 'dateWithoutYear';
};
