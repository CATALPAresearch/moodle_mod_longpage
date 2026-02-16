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
/* eslint-disable max-len, no-console, no-loop-func, no-undef, no-unused-vars, no-bitwise */
/**
 * TODO
 *
 * ---
 * - counting images does not work
 * - the estimation by certain types of headings should be abstracted
 * - language support / language detection
 * - separate display and calculation
 * - make calculation more generic (not bound to headings as delimiters)
 * - localization of strings in display
 */
import {toClassSelector} from '@/util/style';

export class ReadingTimeEstimator {
    constructor(textContainerSelector) {
        this.textContainerSel = textContainerSelector;
        this.language = 'de';
        this.readingSpeedPerLanguage = {
            // 200 word per Minute https://de.wikipedia.org/wiki/Lesegeschwindigkeit
            // add 12 seconds for each inline image. Boom, read time.
            de: {
                cpm: 250, variance: 50
            }
        };
        this.slowSum = 0;
        this.fastSum = 0;
    }

    calcAndDisplay(headingTag) {
        const dummyHeadingClass = `dummy-heading-${headingTag}`;
        const tmpMarkerClass = `tmp-marked-${headingTag}`;
        const container = document.querySelector(this.textContainerSel);
        if (!container) return;
        
        let noOfHeadings = container.querySelectorAll(headingTag).length;
        
        const dummyHeading = document.createElement(headingTag);
        dummyHeading.style.cssText = 'display: inline; color: #fff;';
        dummyHeading.className = dummyHeadingClass;
        container.appendChild(dummyHeading);
        
        for (let i = 0; i < noOfHeadings; i++) {
            let numberOfImages = 0;
            const from = container.querySelectorAll(headingTag)[i];
            const to = container.querySelectorAll(headingTag)[i + 1];
            
            // Get elements between from and to
            let current = from.nextElementSibling;
            let elements = [];
            while (current && current !== to) {
                elements.push(current);
                current.classList.add(tmpMarkerClass);
                current = current.nextElementSibling;
            }

            // Concat text from DOM
            let out = '';
            elements.forEach(el => {
                out = `${out} ${el.textContent}`;
                if (el.tagName === 'IMG') {
                    numberOfImages++;
                }
                el.classList.remove(tmpMarkerClass);
            });
            
            const output = document.createElement('div');
            output.className = 'mx-0 my-1 p-0';
            output.style.cssText = 'font-size: 0.8em; color: #333333;';
            output.innerHTML = this.estimateTime(out, numberOfImages);
            from.after(output);
        }
        
        // Remove dummy heading
        const dummy = container.querySelector(toClassSelector(dummyHeadingClass));
        if (dummy) dummy.remove();
    }

    estimateTime(text, numImg) {
        let textlength = text.match(/([\s]+)/g).length;
        numImg = parseInt(numImg, 10) === 0 || typeof (numImg) !== 'number' ? 1 : numImg;
        let readingSpeed = this.readingSpeedPerLanguage[this.language];
        let readingTimeSlow = Math.ceil(textlength / (readingSpeed.cpm - readingSpeed.variance) + numImg * 0.3);
        let readingTimeFast = Math.ceil(textlength / (readingSpeed.cpm + readingSpeed.variance) + numImg * 0.3);
        this.slowSum += readingTimeSlow;
        this.fastSum += readingTimeFast;
        return 'Geschätzte Lesezeit ' + this.convertToReadableTime(readingTimeFast, readingTimeSlow);// + ' (' + textlength+' Wörter)';
    }

    convertToReadableTime(fasttime, slowtime) {
        // Return time;
        let time = slowtime;
        if (slowtime < 60) {
            return fasttime + '-' + slowtime + ' Minuten';// '0:' + (time < 10 ? '0' + time : time);
        } else if (slowtime > 59 && fasttime < 3600) {
            let slowhours = Math.ceil(slowtime / 60);
            let slowminutes = slowtime % 60;
            let fasthours = Math.ceil(fasttime / 60);
            let fastminutes = fasttime % 60;

            return fasthours + ':' + (fastminutes < 10 ? '0' + fastminutes : fastminutes) + ' &ndash; ' + slowhours + ':' + (slowminutes < 10 ? '0' + slowminutes : slowminutes) + ' Stunden';
        }
        return time; // Should be a rar case, but needs to be treated in some way

    }
}
