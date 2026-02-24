<template>
  <div class="position-absolute" :style="style">
    <div
      v-for="(annotations, type, index) in annotationsByType"
      :key="type"
      class="position-absolute mx-1"
      :style="`left: ${index * 2}em`"
    >
      <a
        role="button"
        class="icon-layer text-secondary fa-1-5x"
        @click.stop="publishSelection(Number(type))"
      >
        <i
          :title="$t(`content.annotationIndicator.${type}`)"
          :aria-label="$t(`content.annotationIndicator.${type}`)"
          class="icon fa fa-fw m-0"
          :class="annotationTypeIcon[type]"
        />
        <span
          v-if="annotations.length > 1"
          class="icon-layer-text font-weight-boldest text-white"
          >{{ annotations.length }}</span
        >
      </a>
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
import { AnnotationType, AnnotationTypeIcon } from "@/util/constants";
import isEmpty from "lodash/isEmpty";
import pickBy from "lodash/pickBy";
import transform from "lodash/transform";
import { EventBus } from "@/lib/core/event-bus";
import { toPx } from "@/util/dom/style";

export default {
  name: "AnnotationIndicator",
  props: {
    annotations: { type: Array, default: () => [] },
    top: { type: Number },
  },
  data() {
    return {
      iconWidth: "1em",
    };
  },
  computed: {
    annotationsByType() {
      return pickBy(
        transform(
          AnnotationType,
          (result, type) => {
            result[type] = this.annotations.filter((a) => a.type === type);
          },
          {},
        ),
        (v) => !isEmpty(v),
      );
    },
    annotationTypeIcon() {
      return AnnotationTypeIcon;
    },
    style() {
      return {
        top: toPx(this.top),
      };
    },
  },
  methods: {
    publishSelection(annotationType) {
      EventBus.publish("annotations-selected", {
        type: annotationType,
        selection: this.annotationsByType[annotationType],
      });
    },
  },
};
</script>

<style lang="scss" scoped>
.icon-layer {
  line-height: 1.5;
  box-sizing: border-box;
  display: inline-block;
  height: 1em;
  text-align: center;
  vertical-align: -0.125em;
  width: 1em;
}

.icon {
  box-sizing: border-box;
  display: inline-block;
  height: 1em;
  font-size: inherit;
  vertical-align: -0.125em;
  width: 1em;
  overflow: visible;
  bottom: 0;
  left: 0;
  margin: auto;
  position: absolute;
  right: 0;
  top: 0;
  transform-origin: center center;
}

.icon-layer-text {
  background: grey;
  display: inline-block;
  position: absolute;
  border-radius: 1em;
  box-sizing: border-box;
  height: 1.5em;
  line-height: 1;
  max-width: 5em;
  min-width: 1.5em;
  padding: 0.25em;
  text-overflow: ellipsis;
  right: 0;
  top: -3px;
  transform: scale(0.35);
  transform-origin: top right;
}
</style>
