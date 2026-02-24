<template>
  <a :href="profileLink">
    <img
      :src="avatar.src"
      class="userpicture avatar"
      height="35"
      width="35"
      :alt="avatar.alt"
      :title="avatar.alt"
    >
  </a>
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
  name: 'UserAvatar',
    props: {
      user: {type: Object},
    },
    computed: {
      avatar() {
        return this.user ? {
          src: this.user.profileImage,
          alt: this.user.imageAlt || this.$i18n.tc('avatar.alt', 2, {fullName: this.user.fullName})
        } : {
          alt: this.$i18n.tc('avatar.alt', 1),
          src: M.util.image_url('user-secret-avatar', 'mod_longpage'),
        };
      },
      profileLink() {
        return this.user ? this.user.profileLink : '#';
      }
    }
  };
</script>
