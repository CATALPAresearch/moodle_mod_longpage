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
import ajax from "core/ajax";
import { MoodleWSMethods } from "@/util/constants";
import { GET, ACT, MUTATE } from "../types";
import MappingService from "@/services/mapping-service";

export default {
  state: {
    enrolledUsers: [],
    userRoles: [],
    userCanMod: false,
  },
  getters: {
    [GET.USER]: (_, getters) => (id) =>
      getters[GET.USERS].find(
        (user) => user.id === (id || getters[GET.LONGPAGE_CONTEXT].userId),
      ),
    [GET.USER_ROLES]:
      ({ userRoles }, getters) =>
      (userId) =>
        userRoles.filter((role) =>
          getters[GET.USER](userId).roles.includes(role.id),
        ),
    [GET.USERS]: ({ enrolledUsers }) => enrolledUsers,

    [GET.CAN_USER_MOD_ANNOTATION]: (state) => {
      return state.userCanMod;
    },
  },
  actions: {
    [ACT.FETCH_ENROLLED_USERS]({ commit, getters }) {
      const context = getters[GET.LONGPAGE_CONTEXT];
      ajax.call([
        {
          methodname: MoodleWSMethods.GET_ENROLLED_USERS,
          args: {
            longpageid: context.longpageid,
          },
          done: ({ users }) => {
            commit(
              MUTATE.SET_ENROLLED_USERS,
              users.map((response) =>
                MappingService.mapResponseToUser(response),
              ),
            );
          },
          fail: (e) => {
            console.error(`"${MoodleWSMethods.GET_ENROLLED_USERS}" failed`, e);
            console.log(context);
          },
        },
      ]);
    },
    [ACT.FETCH_USER_ROLES]({ commit, getters }) {
      const context = getters[GET.LONGPAGE_CONTEXT];
      ajax.call([
        {
          methodname: MoodleWSMethods.GET_USER_ROLES_FOR_MODULE,
          args: {
            longpageid: context.longpageid,
          },
          done: ({ userroles }) => {
            commit(
              MUTATE.SET_USER_ROLES,
              userroles.map((response) =>
                MappingService.mapResponseToUserRole(response),
              ),
            );
          },
          fail: (e) => {
            console.error(
              `"${MoodleWSMethods.GET_USER_ROLES_FOR_MODULE}" failed`,
              e,
            );
            console.log(context);
          },
        },
      ]);
    },
    [ACT.USER_CAN_MOD_ANNOTATION]({ commit, getters }) {
      const context = getters[GET.LONGPAGE_CONTEXT];
      ajax.call([
        {
          methodname: "mod_longpage_can_madify_annotations",
          args: {
            longpageid: context.longpageid,
          },
          done: (result) => {
            commit(MUTATE.SET_USER_CAN_MOD_ANNOTATION, result);
          },
          fail: (e) => {
            console.error(
              `"${MoodleWSMethods.USER_CAN_MOD_ANNOTATION}" failed`,
              e,
            );
            console.log(context);
          },
        },
      ]);
    },
  },
  mutations: {
    [MUTATE.SET_ENROLLED_USERS](state, enrolledUsers) {
      state.enrolledUsers = enrolledUsers;
    },
    [MUTATE.SET_USER_ROLES](state, userRoles) {
      state.userRoles = userRoles;
    },
    [MUTATE.SET_USER_CAN_MOD_ANNOTATION](state, result) {
      state.userCanMod = result.canmodannotations;
    },
  },
};
