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
import $ from 'jquery';
 
 $(() => {
 
    //  $('.longpage-footnote button').popover({
    //      html: true,
    //      trigger: 'focus',
    //      sanitize: false
    //  });
 
     const popovers = document.querySelectorAll('.longpage-footnote button[data-toggle="popover"]');
     for (let popover of popovers)
     {
         var html = $(popover).html();
         var pos = html.indexOf("&gt;");
         if (pos >= 0)
         {
             var footnote = html.substring(pos + 4);
             html = html.substring(0, pos - 2).replaceAll("\"", "'");
             var content = popover.getAttribute('data-content');
             content += html;
             popover.setAttribute('data-content', content);
             $(popover).text(footnote);
         }
     }
 });
 