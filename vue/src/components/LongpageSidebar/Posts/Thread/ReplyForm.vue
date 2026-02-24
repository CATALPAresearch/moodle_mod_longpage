<template>
  <div class="row no-gutters align-items-center reply-form">
    <div class="col col-auto p-0">
      <user-avatar :user="user" />
    </div>
    <div class="col p-0">
      <input
        class="form-control"
        :placeholder="$t('responseForm.placeholder')"
        @focus="createPost"
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
import { mapActions, mapGetters } from "vuex";
import { Thread } from "@/types/thread";
import UserAvatar from "@/components/UI/UserAvatar";

export default {
  name: "ReplyForm",
  components: {
    UserAvatar,
  },
  props: {
    thread: { type: Thread, required: true },
  },
  computed: {
    ...mapGetters([GET.USER]),
    user() {
      return this[GET.USER]();
    },
  },
  methods: {
    ...mapActions([ACT.CREATE_POST]),
    createPost() {
      this[ACT.CREATE_POST]({ threadId: this.thread.id });
    },
  },
};
</script>
