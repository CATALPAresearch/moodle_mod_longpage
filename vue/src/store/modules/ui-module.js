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
import { ACT, GET, MUTATE } from "../types";
import ajax from "core/ajax";
import { MoodleWSMethods } from "@/util/constants";

export default {
  state: {
    sidebarTabOpenedKey: undefined,
    scrollTop: 0,
  },
  getters: {
    [GET.SIDEBAR_TAB_OPENED_KEY]: ({ sidebarTabOpenedKey }) =>
      sidebarTabOpenedKey,
    [GET.SCROLL_TOP]: ({ scrollTop }) => scrollTop,
  },
  mutations: {
    [MUTATE.RESET_SCROLL_TOP](state, scrollTop) {
      state.scrollTop = scrollTop;
    },
    [MUTATE.RESET_SIDEBAR_TAB_OPENED_KEY](state, sidebarTabOpenedKey) {
      state.sidebarTabOpenedKey = sidebarTabOpenedKey;
    },
  },
  actions: {
    [ACT.UPDATE_READING_PROGRESS]({ commit, getters }, scrollTop) {
      return 0;
      const oldScrollTop = getters[GET.SCROLL_TOP];
      console.log("update");
      console.log(getters[GET.LONGPAGE_CONTEXT].courseId);
      commit(MUTATE.RESET_SCROLL_TOP, scrollTop);
      ajax.call([
        {
          methodname: MoodleWSMethods.UPDATE_READING_PROGRESS,
          args: {
            longpageid: getters[GET.LONGPAGE_CONTEXT].longpageid,
            scrolltop: scrollTop,
            courseid: getters[GET.LONGPAGE_CONTEXT].courseId,
            section: "",
            sectionhash: 0,
          },
          fail: (e) => {
            console.error(
              `"${MoodleWSMethods.UPDATE_READING_PROGRESS}" failed`,
              e,
            );
            commit(MUTATE.RESET_SCROLL_TOP, oldScrollTop);
          },
        },
      ]);
    },
  },
};
