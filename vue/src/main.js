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
 * @copyright  2026 Niels Seidel <niels.seidel@fernuni-hagen.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Set webpack publicPath dynamically for lazy chunk loading
// eslint-disable-next-line camelcase, no-undef
__webpack_public_path__ = M.cfg.wwwroot + "/mod/longpage/amd/build/";

import "./components/LongpageContent/Footnote";
import "./lib/behaviors/page-ready-listening";
import "./lib/behaviors/hashchange-listening";
import "./lib/behaviors/scroll-snapping";
import App from "./App.vue";
import { createApp } from "vue";
import { initStore } from "@/store";
import { LONGPAGE_APP_CONTAINER_ID } from "@/util/constants";
import { toIdSelector } from "@/util/dom/style";
import { i18n } from "@/util/i18n";

export const init = async (
  courseId,
  longpageid,
  pageName,
  userId,
  content,
  scrollTop,
  showreadingprogress,
  showreadingtime,
  showreadingcomprehension,
  showsearch,
  showtableofcontents,
  showposts,
  showhighlights,
  showbookmarks,
  showeditquestionsnoai,
  showeditquestionsai,
  tags,
  isAdmin,
  initialData = null,
) => {
  try {
    const store = initStore({
      courseId: Number(courseId),
      longpageid: Number(longpageid),
      pageName,
      userId: Number(userId),
      showreadingprogress: Boolean(showreadingprogress),
      showreadingtime: Boolean(showreadingtime),
      showreadingcomprehension: Boolean(showreadingcomprehension),
      showsearch: Boolean(showsearch),
      showtableofcontents: Boolean(showtableofcontents),
      showposts: Boolean(showposts),
      showhighlights: Boolean(showhighlights),
      showbookmarks: Boolean(showbookmarks),
      showeditquestionsnoai: Boolean(showeditquestionsnoai),
      showeditquestionsai: Boolean(showeditquestionsai),
      showEditQuestionsNoAI: Boolean(showeditquestionsnoai),
      showEditQuestionsAI: Boolean(showeditquestionsai),
      tags: Array.isArray(tags) ? tags : [],
      isAdmin: Boolean(isAdmin),
    });

    // Use pre-loaded data if available, otherwise fall back to AJAX
    let lang;
    if (initialData && initialData.i18nStrings) {
      // Use inline data from PHP - no AJAX needed
      lang = initialData.lang || "de";
      store.commit("setStrings", initialData.i18nStrings);
      store.commit("SET_USER_ROLES", initialData.userRoles || []);
      store.commit("SET_USER_CAN_MOD_ANNOTATION", {
        canmodannotations: initialData.canModAnnotations,
      });
    } else {
      // Fallback: Load strings from PHP backend via AJAX
      lang = document
        .getElementsByTagName("html")[0]
        .getAttribute("lang")
        .replace(/-/g, "_");
      await store.dispatch("loadComponentStrings", lang);
    }
    i18n.global.setLocaleMessage(lang, store.state.strings);

    const tmpElement = document.getElementById("longpage-tmp");
    content = tmpElement ? tmpElement.innerHTML : "";

    const app = createApp(App, { content, scrollTop: Number(scrollTop) });
    app.use(store);
    app.use(i18n);
    app.mount(toIdSelector(LONGPAGE_APP_CONTAINER_ID));
  } catch (e) {
    /* eslint-disable no-console */
    console.error("Longpage initialization error:", e);
  }
};

// Export as default for AMD module compatibility
export default { init };
