<template>
  <div class="h-100 bg-light">
    <div class="h-100 d-flex flex-column">
      <div class="p-3 bg-white">
        <div class="row pr-3">
          <h3
            id="posts-sidebar-heading"
            class="col m-0"
          >
            {{ $t('sidebar.tabs.posts.heading') }}
          </h3>
          <div class="col-auto px-0">
            <a
              href="javascript:void(0)"
              @click="toggleFormShown('filter')"
            >
              <i
                :title="$t('sidebar.tabs.posts.filter.title')"
                :aria-label="$t('sidebar.tabs.posts.filter.title')"
                class="fa fa-filter fa-fw fa-2x"
              />
            </a>
          </div>
          <div class="col-auto px-0">
            <a
              href="javascript:void(0)"
              @click="toggleFormShown('sorting')"
            >
              <i
                :title="$t('sidebar.tabs.posts.sorting.title')"
                :aria-label="$t('sidebar.tabs.posts.sorting.title')"
                class="fa fa-sort-amount-desc fa-fw fa-2x"
              />
            </a>
          </div>
        </div>
        <hr
          v-show="formShown !== null"
          class="my-3"
        >
        <filter-form
          v-show="formShown === 'filter'"
        />
        <div v-show="formShown === 'sorting'">
          <select
            v-model="selectedThreadSortingOption"
            class="custom-select"
          >
            <option
              v-for="option in threadSortingOptions"
              :key="option"
              :value="option"
            >
              {{ $t(`sidebar.tabs.posts.sorting.option.${option}`) }}
            </option>
          </select>
        </div>
        <div
          v-if="selectedThreads.length"
          class="row mt-2"
        >
          <div class="col">
            <p class="mb-1">
              {{ $t('sidebar.tabs.posts.message.onlySelectedShown') }}
            </p>
            <button
              role="button"
              class="btn btn-sm btn-secondary"
              @click.stop="resetSelection"
            >
              {{ $t('sidebar.tabs.posts.message.showAllFiltered') }}
            </button>
          </div>
        </div>
        <div
          v-else-if="threadsFiltered && formShown !== 'filter'"
          class="row mt-2"
        >
          <div class="col">
            <p class="mb-1">
              {{ $t('sidebar.tabs.posts.message.filtered') }}
            </p>
            <button
              role="button"
              class="btn btn-sm btn-secondary"
              @click.stop="resetFilter"
            >
              {{ $t('sidebar.tabs.posts.message.showAll') }}
            </button>
          </div>
        </div>
      </div>
      <hr class="my-0 mx-3">
      <div
        v-if="threads.length"
        :id="THREAD_CONTAINER_ID"
        class="p-3 flex-shrink-1 flex-grow-1 overflow-y-auto overflow-x-hidden"
      >
        <thread
          v-for="thread in allThreadsOrSelection"
          :key="thread.id"
          :thread="thread"
          class="mb-3"
        />
      </div>
      <p
        v-else-if="threadsFiltered"
        class="p-3"
      >
        {{ $t('sidebar.tabs.posts.message.noneFilteredShown') }}
      </p>
      <p
        v-else
        class="p-3"
      >
        {{ $t('sidebar.tabs.posts.message.noneShown') }}
      </p>
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
import {ACT, GET, MUTATE} from '@/store/types';
import { AnnotationType, THREAD_CONTAINER_ID, SidebarEvents, SidebarTabKeys  } from '@/config/constants';
import {mapActions, mapGetters, mapMutations} from 'vuex';
import {EventBus} from '@/lib/event-bus';
import FilterForm from '@/components/LongpageSidebar/Posts/FilterForm/';
import Thread from './Thread';

export default {
  name: 'Posts',
  components: {
    FilterForm,
    Thread
  },
  data() {
    return {
      formShown: 'sorting',
      selectedThreads: [],
      THREAD_CONTAINER_ID,
    };
  },
  computed: {
    ...mapGetters([GET.FILTERED_THREADS, GET.SELECTED_THREAD_SORTING_OPTION, GET.THREADS, GET.THREAD_SORTING_OPTIONS]),
    ...mapGetters({threadsFiltered: GET.THREADS_FILTERED}),
    allThreadsOrSelection() {
      return this.selectedThreads.length ? this.selectedThreads : this.threads;
    },
    selectedThreadSortingOption: {
      get() {
        return this[GET.SELECTED_THREAD_SORTING_OPTION];
      },
      set(option) {
        this[MUTATE.SELECTED_THREAD_SORTING_OPTION](option);
      },
    },
    threadSortingOptions() {
      return Object.keys(this[GET.THREAD_SORTING_OPTIONS]);
    },
    threads() {
      return this.threadsFiltered ? this[GET.FILTERED_THREADS] : this[GET.THREADS];
    }
  },
  mounted() {
    EventBus.subscribe('annotations-selected', ({type, selection}) => {
      this.selectedThreads = type === AnnotationType.POST ?
          this.threads.filter(t => selection.findIndex(annotation => annotation.id === t.annotationId) >= 0) : [];
    });
    EventBus.subscribe("page-ready", () => {
      EventBus.publish(SidebarEvents.CHANGE_BADGES, { type: SidebarTabKeys.POSTS, count: this.threads.map(t => t.posts.filter(p => !p._readByUser).length).reduce((p, a) => p + a, 0), title: "Neue Anmerkungen" });
    });
    
  },
  emits: [SidebarEvents.CHANGE_BADGES],
  methods: {
    ...mapActions([ACT.FILTER_ANNOTATIONS]),
    ...mapMutations([MUTATE.SELECTED_THREAD_SORTING_OPTION]),
    resetFilter() {
      this[ACT.FILTER_ANNOTATIONS]();
    },
    resetSelection() {
      this.selectedThreads = [];
    },
    toggleFormShown(formKey = null) {
      this.formShown = this.formShown === formKey ? null : formKey;
    },
  },
};
</script>
