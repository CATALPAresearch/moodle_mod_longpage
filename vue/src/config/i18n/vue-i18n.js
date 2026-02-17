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
import { LANGUAGE } from "@/config/constants";
import { createI18n } from "vue-i18n";
import datetimeFormats from "./date-time-formats.json";

// Custom message resolver that converts dots to underscores
// Vue: $t('sidebar.tabs.posts.heading') -> PHP: $string['sidebar_tabs_posts_heading']
const messageResolver = (obj, path) => {
  const key = path.replace(/\./g, '_');
  return obj[key];
};

// Create i18n instance with empty messages - will be loaded from Moodle backend
export const i18n = createI18n({
  legacy: true, // Use Options API mode for backward compatibility
  locale: LANGUAGE,
  fallbackLocale: "en",
  datetimeFormats,
  messages: {}, // Empty - populated from store after loadComponentStrings
  silentTranslationWarn: true,
  globalInjection: true, // Enable $t in templates
  messageResolver, // Custom resolver for dot-to-underscore conversion
});
