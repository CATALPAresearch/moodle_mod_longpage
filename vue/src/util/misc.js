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
 * @package    mod_longpage
 * @copyright  2021 Adrian Stritzinger <Adrian.Stritzinger@studium.fernuni-hagen.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import camelCase from "lodash/camelCase";
import flow from "lodash/flow";
import upperFirst from "lodash/upperFirst";
import { LONGPAGE_MAIN_ID, SCROLL_INTO_VIEW_OPTIONS } from "@/util/constants";
import deepRenameKeys from "deep-rename-keys";
import scrollIntoView from "scroll-into-view-if-needed";

export const deepLowerCaseKeys = (object) =>
  deepRenameKeys(object, (key) => key.toLowerCase());

export { snakeCase } from "lodash/snakeCase";

export const capitalize = flow(camelCase, upperFirst);

export const createDiv = (attributes, style = {}) =>
  createElement("div", attributes, style);

export const createElement = (tag, attributes, style = {}) => {
  const element = document.createElement(tag);
  Object.entries(attributes).forEach(([attrKey, attrValue]) => {
    element.setAttribute(attrKey, attrValue);
  });
  Object.entries(style).forEach(([attrKey, attrValue]) => {
    element.style[attrKey] = attrValue;
  });
  return element;
};

export const scrollTextElementIntoView = (el) => {
  scrollIntoView(el, {
    ...SCROLL_INTO_VIEW_OPTIONS,
    boundary: document.getElementById(LONGPAGE_MAIN_ID),
  });
};
