<template>
  <div>
    <div class="mb-2 row">
      <div class="input-group col-auto">
        <div class="input-group-prepend">
          <span class="input-group-text bg-transparent border-0 py-0 pl-0 pr-2">
            <i
              class="fa fa-search fa-fw fa-2x text-secondary"
              aria-hidden="true"
            />
          </span>
        </div>
        <input
          id="content-filter-input"
          type="text"
          class="form-control"
          :placeholder="$t('sidebar.tabs.posts.filter.byContent')"
          :aria-label="$t('sidebar.tabs.posts.filter.byContent')"
          @input="
            debouncedUpdateActiveFilter('body.posts.query', $event.target.value)
          "
        />
      </div>
    </div>
    <div class="mb-2 row align-items-center">
      <multi-select-input
        class="col-auto w-100"
        :placeholder="$t('sidebar.tabs.posts.filter.authors')"
        :options="creatorOptions"
        :model-value="activeFilter.body.posts.creator"
        :not-found-message="$t('sidebar.tabs.posts.filter.notFound')"
        @update:model-value="updateActiveFilter('body.posts.creator', $event)"
      />
    </div>
    <div class="mb-2 row align-items-center">
      <h6 class="d-inline m-0 col-auto">
        {{ $t("sidebar.tabs.posts.filter.readingStatus") }}
      </h6>
      <div class="col-auto">
        <multi-select-checkbox-group
          v-model="selectedReadingStateOptions"
          :options="readingStateOptions"
        />
      </div>
    </div>
    <div class="mb-2 row align-items-center">
      <h6 class="d-inline m-0 col-auto">
        {{ $t("sidebar.tabs.posts.filter.status") }}
      </h6>
      <div class="col-auto">
        <multi-select-checkbox-group
          v-model="selectedQuickResponseOptions"
          :options="quickResponseOptions"
        />
      </div>
    </div>
    <div v-if="likesCountSliderOptions" class="mb-2 row align-items-center">
      <h6 class="d-inline m-0 col-auto">
        {{ $t("sidebar.tabs.posts.filter.likedBy") }}
      </h6>
      <slider
        class="col"
        :slider-options="likesCountSliderOptions"
        :model-value="[
          activeFilter.body.posts.likesCount.min,
          activeFilter.body.posts.likesCount.max,
        ]"
        @update:model-value="
          updateActiveFilter('body.posts.likesCount', {
            min: $event[0],
            max: $event[1],
          })
        "
      />
    </div>
    <div v-if="datesCreatedSliderOptions" class="mb-2 row align-items-center">
      <h6 class="d-inline m-0 col-auto">
        {{ $t("sidebar.tabs.posts.filter.timeCreated") }}
      </h6>
      <slider
        class="col"
        :slider-options="datesCreatedSliderOptions"
        :model-value="[
          activeFilter.body.posts.timeCreated.min,
          activeFilter.body.posts.timeCreated.max,
        ]"
        @update:model-value="
          updateActiveFilter('body.posts.timeCreated', {
            min: $event[0],
            max: $event[1],
          })
        "
      />
    </div>
    <div v-if="datesModifiedSliderOptions" class="mb-2 row align-items-center">
      <h6 class="d-inline m-0 col-auto">
        {{ $t("sidebar.tabs.posts.filter.timeModified") }}
      </h6>
      <slider
        class="col"
        :slider-options="datesModifiedSliderOptions"
        :model-value="[
          activeFilter.body.posts.timeModified.min,
          activeFilter.body.posts.timeModified.max,
        ]"
        @update:model-value="
          updateActiveFilter('body.posts.timeModified', {
            min: $event[0],
            max: $event[1],
          })
        "
      />
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
import { ACT, GET } from "@/store/types";
import debounce from "lodash/debounce";
import isEmpty from "lodash/isEmpty";
import set from "lodash/set";
import { mapActions, mapGetters } from "vuex";
import { getDateTimeFormat } from "@/util/i18n/date-time-utils";
import MultiSelectCheckboxGroup from "@/components/UI/MultiSelectCheckboxGroup";
import MultiSelectInput from "@/components/UI/MultiSelectInput/index";
import Slider from "@/components/UI/Slider";

const Timestamp = Object.freeze({
  CREATED: "timeCreated",
  MODIFIED: "timeModified",
});

export default {
  name: "FilterForm",
  components: { MultiSelectCheckboxGroup, MultiSelectInput, Slider },
  data() {
    return {
      readingStateOptions: [
        {
          id: "posts-filter-checkbox-read",
          text: this.$i18n.t("post.state.unread"),
          iconClasses: ["fa-eye-slash"],
          value: "unread",
        },
        {
          id: "posts-filter-checkbox-unread",
          text: this.$i18n.t("post.state.read"),
          iconClasses: ["fa-eye"],
          value: "read",
        },
      ],
      quickResponseOptions: [
        {
          id: "posts-filter-checkbox-liked",
          text: this.$i18n.t("post.state.liked"),
          iconClasses: ["fa-thumbs-up"],
          value: "likedByUser",
        },
        {
          id: "posts-filter-checkbox-bookmarked",
          text: this.$i18n.t("post.state.bookmarked"),
          iconClasses: ["fa-star"],
          value: "bookmarkedByUser",
        },
        {
          id: "posts-filter-checkbox-subscribed-to-thread",
          text: this.$i18n.t("post.state.subscribedToThread"),
          iconClasses: ["fa-bell"],
          value: "subscribedToByUser",
        },
      ],
    };
  },
  computed: {
    ...mapGetters("annotation", [GET.ANNOTATION_FILTER]),
    ...mapGetters("post", [GET.POSTS]),
    ...mapGetters([GET.USER_ROLES, GET.USERS]),
    activeFilter: {
      get() {
        return this[GET.ANNOTATION_FILTER];
      },
      set(filter) {
        this[ACT.FILTER_ANNOTATIONS](filter);
      },
    },
    creatorOptions() {
      return this[GET.USERS]
        .flatMap((u) =>
          this[GET.USER_ROLES](u.id).map((r) => ({
            text: u.fullName,
            value: u.id,
            category: r.localName,
          })),
        )
        .sort(
          (oA, oB) =>
            oA.category.localeCompare(oB.category) ||
            oA.text.localeCompare(oB.text),
        );
    },
    likesCountSliderOptions() {
      const likesCounts = this[GET.POSTS].map(({ likesCount }) => likesCount);
      if (isEmpty(likesCounts)) return;

      const [min, max] = [Math.min(...likesCounts), Math.max(...likesCounts)];
      if (min === max) return;

      return { min, max, value: [min, max] };
    },
    datesCreatedSliderOptions() {
      const timestamps = this.mapPostsToTimestamps(this[GET.POSTS]);
      if (isEmpty(timestamps)) return;

      const [min, max] = [Math.min(...timestamps), Math.max(...timestamps)];
      if (min === max) return;

      return this.getDateSliderOptions(min, max);
    },
    datesModifiedSliderOptions() {
      const timestamps = this.mapPostsToTimestamps(
        this[GET.POSTS],
        Timestamp.MODIFIED,
      );
      if (isEmpty(timestamps)) return;

      const [min, max] = [Math.min(...timestamps), Math.max(...timestamps)];
      if (min === max) return;

      return this.getDateSliderOptions(min, max);
    },
    selectedQuickResponseOptions: {
      get() {
        const value = [];
        if (this.activeFilter.body.posts.likedByUser) value.push("likedByUser");
        if (this.activeFilter.body.posts.bookmarkedByUser)
          value.push("bookmarkedByUser");
        if (this.activeFilter.body.subscribedToByUser)
          value.push("subscribedToByUser");
        return value;
      },
      set(options) {
        this.updateActiveFilter(
          "body.posts.likedByUser",
          options.includes("likedByUser") || undefined,
        );
        this.updateActiveFilter(
          "body.posts.bookmarkedByUser",
          options.includes("bookmarkedByUser") || undefined,
        );
        this.updateActiveFilter(
          "body.subscribedToByUser",
          options.includes("subscribedToByUser") || undefined,
        );
      },
    },
    selectedReadingStateOptions: {
      get() {
        const readByUser = this.activeFilter.body.posts.readByUser;
        if (readByUser === undefined) return [];

        return readByUser ? ["read"] : ["unread"];
      },
      set(options) {
        this.updateActiveFilter(
          "body.posts.readByUser",
          options.includes("read") === options.includes("unread")
            ? undefined
            : options.includes("read"),
        );
      },
    },
  },
  methods: {
    ...mapActions("annotation", [ACT.FILTER_ANNOTATIONS]),
    mapPostsToTimestamps(posts, timestamp = Timestamp.CREATED) {
      return (
        posts.filter((p) => p.created).map((p) => p[timestamp].getTime()) || []
      );
    },
    debouncedUpdateActiveFilter: debounce(function (path, value) {
      // eslint-disable-next-line no-invalid-this
      this.updateActiveFilter(path, value);
    }, 500),
    getDateSliderOptions(timeMin, timeMax) {
      const format = (time) =>
        this.$i18n.d.call(this.$i18n, time, getDateTimeFormat(time));
      return {
        min: timeMin,
        max: timeMax,
        step: 60,
        value: [timeMin, timeMax],
        formatter: (value) =>
          Array.isArray(value) ? value.map((v) => format(v)) : format(value),
      };
    },
    updateActiveFilter(path, value) {
      const updatedActiveFilter = set({ ...this.activeFilter }, path, value);
      this.activeFilter = updatedActiveFilter;
    },
  },
};
</script>
~
