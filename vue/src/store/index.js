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
import AnnotationModule from "./modules/annotation-module";
import PostModule from "./modules/post-module";
import UIModule from "./modules/ui-module";
import UserModule from "./modules/user-module";
import QuestionBankModule from "./modules/questionBank";
import { createStore } from "vuex";
import { GET } from "./types";
import moodleAjax from "core/ajax";
import moodleStorage from "core/localstorage";

export const initStore = (longpageContext) =>
  createStore({
    modules: {
      AnnotationModule,
      PostModule,
      UIModule,
      UserModule,
      questionBank: QuestionBankModule,
    },
    state: {
      longpageContext,
      strings: {},
    },
    getters: {
      [GET.LONGPAGE_CONTEXT]: ({ longpageContext }) => longpageContext,
    },
    mutations: {
      setStrings(state, strings) {
        state.strings = strings;
      },
    },
    actions: {
      /**
       * Loads ALL language strings from PHP backend.
       * Calls core_get_component_strings to fetch all mod_longpage strings.
       *
       * @param context Vuex action context
       * @param lang Language code (e.g., 'en', 'de')
       * @returns {Promise<void>}
       */
      async loadComponentStrings(context, lang) {
        const request = {
          methodname: "core_get_component_strings",
          args: {
            component: "mod_longpage",
            lang,
          },
        };
        const loadedStrings = await moodleAjax.call([request])[0];

        // Transform Moodle keys: convert colons to underscores
        // PHP: string['privacy:metadata'] -> Vue: 'privacy_metadata'
        const flatStrings = {};
        loadedStrings.forEach((s) => {
          const key = s.stringid.replace(/:/g, "_");
          flatStrings[key] = s.string;
        });

        context.commit("setStrings", flatStrings);
      },
    },
  });
