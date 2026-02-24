<template>
  <div>
    <a
      class="font-italic longpage-highlight text-small"
      :class="[annotationTarget.styleClass, {'mr-1': isExpandable}]"
      @click.stop="$emit('highlight-clicked')"
    >
      {{ highlightedTextExcerpt }}
    </a>
    <a
      v-if="isExpandable"
      :key="isExpanded"
      role="button"
      class="badge badge-light"
      @click.stop="isExpanded = !isExpanded"
    >{{ isExpanded ? $t('expandable.less') : $t('expandable.more') }}</a>
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
import {AnnotationTarget} from '@/types/annotation-target';

const MIN_EXPANDABLE_TEXT_LENGTH = 120;
const UNEXPANDED_TEXT_LENGTH = 100;

export default {
  name: 'ExpandableHighlightExcerpt',
  props: {
    annotationTarget: {type: AnnotationTarget, required: true},
  },
  emits: ['highlight-clicked'],
  data() {
    return {
      isExpanded: false,
    };
  },
  computed: {
    isExpandable() {
      return this.annotationTarget.text && this.annotationTarget.text.length > MIN_EXPANDABLE_TEXT_LENGTH;
    },
    highlightedTextExcerpt() {
      if (!this.isExpandable || this.isExpanded) return this.annotationTarget.text;

      const beginning = this.annotationTarget.text.slice(0, UNEXPANDED_TEXT_LENGTH);
      const suffix = beginning.endsWith('.') ? '..' : '...';
      return `${beginning}${suffix}`;
    },
  },
};
</script>
