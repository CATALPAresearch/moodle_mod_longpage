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
import { toPx } from "@/util/dom/style";
import {
  LONGPAGE_APP_CONTAINER_ID,
  MOODLE_NAVBAR_HEIGHT_IN_PX,
} from "@/util/constants";

if (
  "IntersectionObserver" in window &&
  "IntersectionObserverEntry" in window &&
  "intersectionRatio" in window.IntersectionObserverEntry.prototype
) {
  const rootMarginTop = toPx(-MOODLE_NAVBAR_HEIGHT_IN_PX);
  const observer = new IntersectionObserver(
    (entries) => {
      if (entries[0].boundingClientRect.y === 50)
        document.body.classList.add("snapped-to-app-container");
      else document.body.classList.remove("snapped-to-app-container");
    },
    { rootMargin: `${rootMarginTop} 0px 0px 0px`, threshold: 1 },
  );
  observer.observe(document.getElementById(LONGPAGE_APP_CONTAINER_ID));
}
