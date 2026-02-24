<template>
  <div class="dropdown">
    <div class="input-group" data-toggle="dropdown">
      <input
        ref="multiSelectInput"
        type="text"
        class="form-control tagin"
        @change="valueAsString = $event.target.value"
        @queryinput="query = $event.detail.value"
      />
      <div class="input-group-append">
        <button
          type="button"
          class="btn btn-outline-secondary dropdown-toggle dropdown-toggle-split"
          aria-haspopup="true"
          aria-expanded="false"
        >
          <span class="sr-only">{{ $t("generic.toggleDropdown") }}</span>
        </button>
      </div>
    </div>
    <div class="dropdown-menu px-2 overflow-auto">
      <div
        v-if="Object.entries(optionsByCategory).length"
        role="listbox"
        :aria-label="placeholder"
      >
        <div
          v-for="(options, category) in optionsByCategory"
          :key="category"
          role="group"
          :aria-label="category"
        >
          <h6 class="dropdown-header pr-1" v-html="category" />
          <div
            v-for="option in options"
            :key="option.value"
            class="form-group pl-4 pr-1 mb-1"
          >
            <input
              :id="'multiselect-option-' + option.value"
              :checked="value.includes(option.value)"
              class="form-check-input"
              type="checkbox"
              :aria-label="option.text"
              @change="toggleOption(option.value)"
            />
            <i
              v-if="option.iconClasses"
              class="icon fa fa-fw mr-1"
              :class="option.iconClasses"
              aria-hidden="true"
            />
            <label
              class="form-check-label"
              :for="'multiselect-option-' + option.value"
              v-html="option.text"
            />
          </div>
        </div>
      </div>
      <p v-else>
        {{ notFoundMessageIntern }}
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
import compact from "lodash/compact";
import without from "lodash/without";
import Fuse from "fuse.js";
import { mapResultToHighlightedDoc } from "@/util/fuse";
import { tagin } from "@/lib/vendor/tagin";

const FUSE_OPTIONS = Object.freeze({
  includeMatches: true,
  keys: ["text", "category"],
  threshold: 0.4,
});

export default {
  name: "MultiSelectInput",
  props: {
    modelValue: { type: Array, default: () => [] },
    notFoundMessage: { type: String },
    options: { type: Array, default: () => [] },
    placeholder: { type: String },
  },
  emits: ["update:modelValue"],
  data() {
    return {
      mounted: false,
      fuse: null,
      query: "",
      valueSeperator: ",",
    };
  },
  computed: {
    defaultCategory() {
      return this.$i18n.t("generic.defaultSelectOptionsCategory");
    },
    filteredOptions() {
      if (!this.query) return this.options;

      return this.fuse.search(this.query).map(mapResultToHighlightedDoc);
    },
    notFoundMessageIntern() {
      return this.notFoundMessage || this.$i18n.t("generic.message.notFound");
    },
    optionsByCategory() {
      return this.filteredOptions.reduce((result, option) => {
        const category = option.category || this.defaultCategory;
        if (!result[category]) result[category] = [];
        result[category].push(option);
        return result;
      }, {});
    },
    value: {
      get() {
        return this.modelValue;
      },
      set(value) {
        this.$emit("update:modelValue", value);
      },
    },
    valueAsString: {
      get() {
        return this.value.join(this.valueSeperator);
      },
      set(value) {
        this.value = compact(value.split(this.valueSeperator)).map(Number);
      },
    },
  },
  watch: {
    options: {
      handler(newOptions) {
        if (this.$refs.multiSelectInput)
          this.$refs.multiSelectInput._selectOptions = newOptions;
        if (!newOptions || !newOptions.length) return;

        this.fuse = new Fuse(newOptions, FUSE_OPTIONS);
      },
      immediate: true,
    },
    valueAsString(newValueAsString) {
      this.$refs.multiSelectInput.value = newValueAsString;
      this.$refs.multiSelectInput.dispatchEvent(new CustomEvent("update-tags"));
    },
  },
  mounted() {
    this.mounted = true;
    this.$refs.multiSelectInput._selectOptions = this.options;
    this.$refs.multiSelectInput.value = this.valueAsString;
    tagin(this.$refs.multiSelectInput, {
      placeholder: this.placeholder,
      separator: this.valueSeperator,
    });
  },
  methods: {
    toggleOption(value) {
      if (this.value.includes(value)) this.value = without(this.value, value);
      else this.value = [...this.value, value];
      this.$refs.multiSelectInput.dispatchEvent(
        new CustomEvent("update-query", { detail: { value: "" } }),
      );
    },
  },
};
</script>

<style lang="scss">
@import "~tagin/dist/css/tagin.min.css";
</style>
