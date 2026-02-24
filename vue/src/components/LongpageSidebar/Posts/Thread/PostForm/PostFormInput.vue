<template>
  <textarea
    ref="contentInput"
    :value="modelValue"
    class="form-control"
    :placeholder="$t('post.form.bodyTextareaPlaceholder')"
    :aria-label="$t('post.form.bodyTextareaPlaceholder')"
    rows="3"
    @click.stop=""
    @input="$emit('update:modelValue', $event.target.value)"
    @keydown.enter.ctrl.exact.prevent="$emit('submit')"
    @keydown.esc.exact.prevent="$emit('submit')"
  />
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
import autosize from "autosize";

export default {
  name: "PostFormInput",
  props: {
    modelValue: { type: String, required: true },
  },
  emits: ["update:modelValue", "submit"],
  mounted() {
    this.$nextTick(() => {
      this.$refs.contentInput.focus();
      autosize(this.$refs.contentInput);
    });
  },
  beforeUnmount() {
    autosize.destroy(this.$refs.contentInput);
  },
};
</script>
