<template>
  <div
    v-for="option in options"
    :key="option.id"
    class="form-check form-check-inline mr-3"
  >
    <input
      :id="option.id"
      class="form-check-input"
      type="checkbox"
      :value="modelValue.includes(option.value)"
      @input="toggleOption(option.value)"
    >
    <label
      class="form-check-label"
      :for="option.id"
    >
      <i
        class="icon fa fa-fw m-0"
        :class="option.iconClasses"
      />
      {{ option.text }}
    </label>
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
  export default {
    name: 'MultiSelectCheckboxGroup',
    props: {
      modelValue: {type: Array, default: () => []},
      options: {type: Array, default: () => []},
    },
    emits: ['update:modelValue'],
    methods: {
      toggleOption(value) {
        this.$emit(
            'update:modelValue',
            this.modelValue.includes(value) ? this.modelValue.filter(mv => mv !== value) : [...this.modelValue, value],
        );
      }
    },
  };
</script>
