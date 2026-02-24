<template>
  <div class="row">
    <div class="col-auto pr-0">
      {{
        sliderOptions.formatter
          ? sliderOptions.formatter(sliderOptions.min)
          : sliderOptions.min
      }}
    </div>
    <div class="col">
      <input :id="inputId" ref="input" :style="{ width: '100%' }" type="text" />
    </div>
    <div class="col-auto pl-0">
      {{
        sliderOptions.formatter
          ? sliderOptions.formatter(sliderOptions.max)
          : sliderOptions.max
      }}
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
import Slider from "bootstrap-slider";
import debounce from "lodash/debounce";
import uniqueId from "lodash/uniqueId";
import { toIdSelector } from "@/util/dom/style";

export default {
  name: "Slider",
  props: {
    modelValue: { type: Array, required: true },
    sliderOptions: { type: Object, required: true },
  },
  emits: ["update:modelValue"],
  data() {
    return {
      emitDebounced: () => {},
      inputId: uniqueId("slider-input-"),
      sliderId: uniqueId("slider-"),
      slider: null,
      initSliderPromise: null,
    };
  },
  watch: {
    sliderOptions: {
      async handler(options, oldOptions) {
        await this.initSliderPromise;
        Object.entries(options).forEach(([key, value]) => {
          this.slider.setAttribute(key, value);
        });
        this.slider.setValue([
          this.modelValue[0] === undefined ||
          this.modelValue[0] === oldOptions.min
            ? options.min
            : this.modelValue[0],
          this.modelValue[1] === undefined ||
          this.modelValue[1] === oldOptions.max
            ? options.max
            : this.modelValue[1],
        ]);
        this.slider.refresh({ useCurrentValue: true });
        this.slider.on("change", ({ newValue }) => {
          this.emitDebounced(newValue);
        });
      },
      deep: true,
    },
  },
  async mounted() {
    this.initSlider();
    this.emitDebounced = debounce((newValue) => {
      this.$emit("update:modelValue", newValue);
    }, 500);
  },
  methods: {
    initSlider() {
      this.initSliderPromise = new Promise((resolve) => {
        const mutationObserver = new MutationObserver(() => {
          if (this.$refs.input && this.$refs.input.id === this.inputId) {
            mutationObserver.disconnect();
            this.slider = new Slider(toIdSelector(this.inputId), {
              id: this.sliderId,
              range: true,
              ...(this.sliderOptions || {}),
            });
            this.slider.on("change", ({ newValue }) => {
              this.emitDebounced(newValue);
            });
            resolve();
          }
        });
        mutationObserver.observe(document, {
          attributes: true,
          childList: true,
          characterData: false,
          subtree: true,
          attributeFilter: ["id"],
        });
      });
    },
  },
};
</script>

<style lang="scss">
@import "~bootstrap-slider/dist/css/bootstrap-slider.min.css";

.slider-selection {
  background: #036fa5;
}

.slider.slider-horizontal {
  width: 30%;
}
</style>
