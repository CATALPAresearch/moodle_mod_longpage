<template>
  <div class="h-100">
    <div class="h-100 d-flex flex-column">
      <div class="p-3 bg-white">
        <h3 class="m-0">
          {{ $t('sidebar.tabs.tableOfContents.heading') }}
        </h3>
      </div>
      <hr class="my-0 mx-3">
      <div
        class="p-3 flex-shrink-1 flex-grow-1 overflow-y-auto overflow-x-hidden"
      >
        <nav
          class="nav flex-column nav-pills"
          aria-orientation="vertical"
        >
          <a
            v-for="(entry, index) in tocEntries"
            :key="entry.hId"
            class="nav-link"
            :class="{active: index === activeTOCEntryIndex}"
            :href="`#${entry.hId}`"
            data-toggle="pill"
            :style="`margin-left: ${entry.hLvl*2}rem`"
            @click="clickTOCEntry(entry.hEl)"
          >{{ entry.text }}</a>
        </nav>
      </div>
    </div>
  </div>
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
import {LONGPAGE_CONTENT_ID, LONGPAGE_MAIN_ID, SidebarEvents, SidebarTabKeys } from '@/config/constants';
import findLast from 'lodash/findLast';
import {toIdSelector} from '@/util/style';
import { scrollTextElementIntoView } from '@/util/misc';
import { EventBus } from "@/lib/event-bus";

export default {
    name: 'TableOfContents',
    props: {
      contentsContainerSelector: {type: String, default: toIdSelector(LONGPAGE_CONTENT_ID)},
      headingSelectors: {
        type: Array,
        default: () => [
          'h3',
          'h4',
        ]
      }
    },
    data() {
        return {
            hClass: 'in-toc',
            tocEntries: [],
            activeTOCEntryIndex: 0,
        };
    },
    computed: {
      allHeadingsSelector() {
        return this.headingSelectors.map(hSelector => [this.contentsContainerSelector, hSelector].join(' ')).join(', ');
      }
    },
    mounted() {
        this.initTOC();
        this.initHIntersectionObserver();
    },
    methods: {
        initHIntersectionObserver() {
          const root = document.getElementById(LONGPAGE_MAIN_ID);
          const intersectionObserver = new IntersectionObserver(entries => {
            const lastIntersectingEntry = findLast(entries, e => e.isIntersecting);
            if (lastIntersectingEntry) {
              this.activeTOCEntryIndex = this.tocEntries.findIndex(e => e.hId === lastIntersectingEntry.target.id);
              if (this.activeTOCEntryIndex === -1) {
                return;
              }
              $(`a.nav-link[href='#h-in-toc-${this.activeTOCEntryIndex}']`).get(0).scrollIntoView(false);
             return;
            }

            const lastEntry = entries[entries.length - 1];
            if (lastEntry.boundingClientRect.y > root.getBoundingClientRect().y) {
              this.activeTOCEntryIndex = this.tocEntries.findIndex(e => e.hId === lastEntry.target.id) - 1;
              if (this.activeTOCEntryIndex === -1) {
                return;
              }
              $(`a.nav-link[href='#h-in-toc-${this.activeTOCEntryIndex}']`).get(0).scrollIntoView(false);
            }
          }, {root});
        this.tocEntries.map(entry => entry.hEl).forEach(targetEl => intersectionObserver.observe(targetEl));
        },
        initTOC() {
            $(this.allHeadingsSelector).each((index, el) => {
              const hId = `h-in-toc-${index}`;
              const hLvl = this.headingSelectors.findIndex(selector => $(el).is(selector));
              this.tocEntries.push({hEl: el, hId, hLvl, text: $(el).text()});
              $(el).attr('id', hId);
              $(el).attr('class', this.hClass);
            });
        },
        scrollTextElementIntoView,
        clickTOCEntry(el) {
          scrollTextElementIntoView(el);
          if (screen.width <= 760) {
            EventBus.publish(SidebarEvents.TOGGLE_TABS, SidebarTabKeys.TOC);
          }
        }
    },
};
</script>

<style scoped lang="scss">
#sidebar-tab-table-of-contents .nav-link:hover {
  z-index: 1;
  color: #495057;
  text-decoration: none;
  background-color: #f8f9fa;
}

#sidebar-tab-table-of-contents .nav-link.active {
  background-color: #0f6cbf;
  color: #fff !important;
}
</style>
