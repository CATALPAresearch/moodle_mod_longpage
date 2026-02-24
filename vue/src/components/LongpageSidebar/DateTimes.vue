<template>
  <span>
    <span v-if="timeCreated && timeModified">
      {{ $t("timestamps.created") }}
      <date-time-text :date-time="timeCreated" />
      <span v-if="timeCreated.getTime() !== timeModified.getTime()">
        <span class="font-italic">
          ({{ $t("timestamps.modified") }}
          <date-time-text :date-time="timeModified" />)
        </span>
      </span>
    </span>
    <loading-indicator-dots
      v-else
      :text="
        timeCreated
          ? $t('generic.loadingIndicator.updating')
          : $t('generic.loadingIndicator.creating')
      "
    />
  </span>
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
import DateTimeText from "@/components/UI/DateTimeText";
import LoadingIndicatorDots from "@/components/UI/LoadingIndicatorDots";

export default {
  name: "DateTimes",
  components: {
    DateTimeText,
    LoadingIndicatorDots,
  },
  props: {
    timeCreated: { type: Date },
    timeModified: { type: Date },
  },
};
</script>
