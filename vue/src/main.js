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
import './components/LongpageContent/Footnote';
import './lib/hashchange-listening';
import './lib/page-ready-listening';
import './lib/scroll-snapping';
import App from './App.vue';
import {createApp} from 'vue';
import {initStore} from '@/store';
import {LONGPAGE_APP_CONTAINER_ID} from '@/config/constants';
import {toIdSelector} from '@/util/style';
import {i18n} from '@/config/i18n';

export const init = (courseId, longpageid, pageName, userId, content, scrollTop,
    showreadingprogress, showreadingcomprehension, showsearch, showtableofcontents, showposts, showhighlights, showbookmarks, tags, isAdmin) => {
    try {
        const store = initStore({
            courseId: Number(courseId), longpageid: Number(longpageid), pageName, userId: Number(userId),
            showreadingprogress, showreadingcomprehension, showsearch, showtableofcontents, showposts, showhighlights, showbookmarks,
            tags, isAdmin
        });
        
        createApp(App, {content, scrollTop: Number(scrollTop)})
            .use(store)
            .use(i18n)
            .mount(toIdSelector(LONGPAGE_APP_CONTAINER_ID));
    } catch (e) {
        /* eslint-disable no-console */
        //console.error(e);
    }
};
