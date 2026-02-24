<template>
  <div
    id="annotation-toolbar-popover"
    ref="annotationToolbarPopover"
    :style="style"
  >
    <div
      v-show="arrowDirection === ArrowDirection.UP"
      ref="arrowUp"
      class="arrow-up mx-auto"
    />
    <div
      id="annotation-toolbar"
      class="d-flex align-items-center p-1"
      :class="{
        'shadow-down': arrowDirection === ArrowDirection.DOWN,
        'shadow-up': arrowDirection === ArrowDirection.UP,
      }"
    >
      <template v-if="context.showhighlights">
        <div
          v-for="(option, index) in highlightingOptions"
          :key="index"
          class="annotation-toolbar-item dot"
          :class="[option]"
          :title="$t('content.annotationToolbar.createHighlight')"
          :aria-label="$t('content.annotationToolbar.createHighlight')"
          role="button"
          tabindex="0"
          @click.prevent="$emit('highlight-clicked', option)"
          @keydown.enter.prevent="$emit('highlight-clicked', option)"
          @keydown.space.prevent="$emit('highlight-clicked', option)"
        >
          A
        </div>
      </template>
      <div
        v-if="context.showbookmarks"
        class="annotation-toolbar-item dot"
        role="button"
        tabindex="0"
        @click.prevent="$emit('bookmark-clicked')"
        @keydown.enter.prevent="$emit('bookmark-clicked')"
        @keydown.space.prevent="$emit('bookmark-clicked')"
      >
        <i
          :title="$t('content.annotationToolbar.createBookmark')"
          :aria-label="$t('content.annotationToolbar.createBookmark')"
          class="fa fa-bookmark-o fa-fw"
          aria-hidden="true"
        />
      </div>
      <div
        v-if="context.showposts"
        class="annotation-toolbar-item dot"
        role="button"
        tabindex="0"
        @click.prevent="$emit('post-clicked')"
        @keydown.enter.prevent="$emit('post-clicked')"
        @keydown.space.prevent="$emit('post-clicked')"
      >
        <i
          :title="$t('content.annotationToolbar.createPost')"
          :aria-label="$t('content.annotationToolbar.createPost')"
          class="fa fa-comment-o fa-fw"
          aria-hidden="true"
        />
      </div>
    </div>
    <div
      v-show="arrowDirection === ArrowDirection.DOWN"
      ref="arrowDown"
      class="arrow-down mx-auto"
    />
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
import { ArrowDirection } from "@/util/constants";
import { toNumber, toPx } from "@/util/dom/style";
import { mapGetters } from "vuex";
import { GET } from "@/store/types";

export default {
  name: "AnnotationToolbar",
  props: {
    arrowDirection: { type: Number, default: ArrowDirection.UP },
    highlightingOptions: { type: Array, default: () => [] },
    showDelete: { type: Boolean, default: false },
    left: { type: Number, required: true },
    top: { type: Number, required: true },
    visible: { type: Boolean, default: false },
    zIndex: { type: Number, default: 999999 },
  },
  emits: ["bookmark-clicked", "highlight-clicked", "post-clicked"],
  data() {
    return {
      ArrowDirection,
    };
  },
  computed: {
    style() {
      return {
        left: toPx(this.left),
        top: toPx(this.top),
        visibility: this.visible ? "visible" : "hidden",
        zIndex: this.zIndex,
      };
    },
    ...mapGetters({ context: GET.LONGPAGE_CONTEXT }),
  },
  methods: {
    arrowHeight() {
      const ref =
        this.arrowDirection === ArrowDirection.UP ? "arrowUp" : "arrowDown";
      return toNumber(
        window
          .getComputedStyle(this.$refs[ref])
          .getPropertyValue("border-top-width"),
      );
    },
    boundingClientRect() {
      return this.$refs.annotationToolbarPopover.getBoundingClientRect();
    },
    height() {
      return this.boundingClientRect().height;
    },
    width() {
      return this.boundingClientRect().width;
    },
  },
};
</script>

<style lang="scss">
@import "../../../styles/main.scss";
//@import "~hover.css";

#annotation-toolbar-popover {
  display: inline-block;
  position: fixed;
  text-align: center;
  font-size: 1rem;

  #annotation-toolbar {
    background-color: white;
    border-radius: 20px;
    display: inline-block;
  }

  .shadow-down {
    box-shadow: 0 2px 4px 2px rgba(39, 43, 49, 0.2) !important;
  }

  .shadow-up {
    box-shadow: 0 -2px 4px 2px rgba(39, 43, 49, 0.2) !important;
  }
}

.hvr-grow {
  display: inline-block;
  vertical-align: middle;
  -webkit-transform: perspective(1px) translateZ(0);
  transform: perspective(1px) translateZ(0);
  box-shadow: 0 0 1px rgba(0, 0, 0, 0);
  -webkit-transition-duration: 0.3s;
  transition-duration: 0.3s;
  -webkit-transition-property: transform;
  transition-property: transform;
}
.hvr-grow:hover,
.hvr-grow:focus,
.hvr-grow:active {
  -webkit-transform: scale(1.1);
  transform: scale(1.1);
}

.annotation-toolbar-item {
  @extend .hvr-grow !optional;
  margin: 0.125rem;
  cursor: pointer;
}

.dot {
  height: 20px;
  width: 20px;
  border-radius: 50%;
  display: inline-block;
  line-height: 20px;
}

$arrow-color: white;

@mixin arrow {
  width: 0;
  height: 0;
  border-left: 5px solid transparent;
  border-right: 5px solid transparent;
  border-top: 5px solid $arrow-color;
}

.arrow-down {
  @include arrow;
  transform: rotate(0deg);
  -webkit-transform: rotate(0deg);
}

.arrow-up {
  @include arrow;
  transform: rotate(180deg);
  -webkit-transform: rotate(180deg);
}
</style>
