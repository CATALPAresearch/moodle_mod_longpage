<template>
  <sidebar-tab
    :title="
      areHighlights
        ? $t('sidebar.tabs.highlights.heading')
        : $t('sidebar.tabs.bookmarks.heading')
    "
  >
    <template #append-header>
      <div
        v-show="selectedHighlights === allHighlightsOrSelection"
        class="mt-2"
      >
        <p class="mb-1">
          {{
            $t(
              `sidebar.tabs.${areHighlights ? "highlights" : "bookmarks"}.message.onlySelectedShown`,
            )
          }}
        </p>
        <a
          role="button"
          class="btn btn-sm btn-secondary"
          @click.stop="resetSelection"
        >
          {{
            $t(
              `sidebar.tabs.${areHighlights ? "highlights" : "bookmarks"}.message.showAll`,
            )
          }}
        </a>
      </div>
      <div v-if="this.$store.state.UserModule.userCanMod">
        <label for="userid">{{
          $t("sidebar.tabs.highlights.userIdLabel") || "Nutzer-ID:"
        }}</label>
        <input
          id="userid"
          type="text"
          v-model="userid"
          size="5"
          style="margin: 5px"
          :aria-label="$t('sidebar.tabs.highlights.userIdLabel') || 'Nutzer-ID'"
        /><input
          type="submit"
          :value="$t('generic.show') || 'Anzeigen'"
          @click="fetchAnnotations()"
        />
      </div>
    </template>
    <template #body>
      <div v-if="allHighlightsOrSelection.length">
        <div
          v-for="(highlight, index) in allHighlightsOrSelection"
          :key="highlight.id"
          class="mb-1"
        >
          <expandable-highlight-excerpt
            :annotation-target="highlight.target"
            class="mx-2 cursor-pointer"
            @highlight-clicked="scrollTextElementIntoView(highlight.id)"
          />
          <div
            class="text-muted text-small mt-1 mx-2 row justify-content-between"
          >
            <div class="col-auto p-0">
              <date-times
                :time-created="highlight.timeCreated"
                :time-modified="highlight.timeModified"
              />
            </div>
            <div v-if="highlight.created" class="col-auto p-0">
              <a
                v-for="action in actions"
                :key="action.text"
                href="javascript:void(0)"
                class="text-dark"
                role="button"
                @click="action.handler(highlight)"
              >
                <i
                  class="icon fa fa-fw text-dark"
                  :class="action.iconClasses"
                  :aria-label="action.text"
                  :title="action.text"
                />
              </a>
            </div>
          </div>
          <hr
            v-if="index !== allHighlightsOrSelection.length - 1"
            class="mt-1 mb-0"
          />
        </div>
      </div>
      <p v-else class="p-3">
        {{
          areHighlights
            ? $t("sidebar.tabs.highlights.message.noneCreated")
            : $t("sidebar.tabs.bookmarks.message.noneCreated")
        }}
      </p>
    </template>
  </sidebar-tab>
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
import { ACT } from "@/store/types";
import { AnnotationType } from "@/util/constants";
import DateTimes from "@/components/LongpageSidebar/DateTimes";
import ExpandableHighlightExcerpt from "@/components/UI/ExpandableHighlightExcerpt";
import { EventBus } from "@/lib/core/event-bus";
import { getHighlightByAnnotationId } from "@/util/dom/annotation";
import { mapActions } from "vuex";
import SidebarTab from "@/components/LongpageSidebar/SidebarTab";
import { scrollTextElementIntoView } from "@/util/misc";

import { HighlightingConfig } from "@/util/constants";

export default {
  name: "HighlightsTab",
  components: { DateTimes, ExpandableHighlightExcerpt, SidebarTab },
  props: {
    type: { type: Number, default: AnnotationType.HIGHLIGHT },
    highlights: { type: Array, default: () => [] },
  },
  data() {
    return {
      selectedHighlights: [],
      userid: null,
    };
  },
  computed: {
    actions() {
      const text = this.areHighlights
        ? this.$i18n.t("highlight.action.delete")
        : this.$i18n.t("bookmark.action.delete");
      return [
        {
          iconClasses: ["fa-trash"],
          handler: this[ACT.DELETE_ANNOTATION],
          text,
        },
      ];
    },
    allHighlightsOrSelection() {
      return this.selectedHighlights.length &&
        this.selectedHighlights.some((shl) =>
          Boolean(this.highlights.find((hl) => hl.id === shl.id)),
        )
        ? this.selectedHighlights
        : this.highlights;
    },
    areHighlights() {
      return this.type === AnnotationType.HIGHLIGHT;
    },
  },
  methods: {
    ...mapActions("annotation", [ACT.DELETE_ANNOTATION]),
    resetSelection() {
      this.selectedHighlights = [];
    },
    scrollTextElementIntoView(id) {
      scrollTextElementIntoView(getHighlightByAnnotationId(id));
    },
    fetchAnnotations() {
      this.$store.dispatch("annotation/" + ACT.FETCH_ANNOTATIONS, this.userid);
    },
  },
  mounted() {
    EventBus.subscribe("annotations-selected", ({ type, selection }) => {
      this.selectedHighlights = this.type === type ? selection : [];
    });
  },
};
</script>
