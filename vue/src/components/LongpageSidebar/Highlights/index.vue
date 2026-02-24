<template>
  <highlights-tab :highlights="highlights" :type="type" />
</template>

<script>
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
import "@/lib/annotation/highlight-selection-listening";
import { AnnotationType } from "@/util/constants";
import { GET } from "@/store/types";
import HighlightsTab from "../HighlightsTab";
import { mapGetters } from "vuex";

export default {
  name: "Highlights",
  components: { HighlightsTab },
  computed: {
    ...mapGetters("annotation", { highlights: GET.HIGHLIGHTS }),
    type() {
      return AnnotationType.HIGHLIGHT;
    },
  },
};
</script>
