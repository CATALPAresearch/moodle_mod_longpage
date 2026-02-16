<template>
  <div></div>
</template>

<script>
import { LONGPAGE_CONTENT_ID } from "@/config/constants";

export default {
  name: "ReadingTime",
  props: {},
  data() {
    return {
      parentSelector: "#" + LONGPAGE_CONTENT_ID,
      language: "de",
      readingSpeedPerLanguage: {
        // 200 word per Minute https://de.wikipedia.org/wiki/Lesegeschwindigkeit
        // add 12 seconds for each inline image. Boom, read time.
        de: {
          cpm: 250,
          variance: 50,
        },
      },
      slowSum: 0,
      fastSum: 0,
    };
  },
  mounted: function () {
    this.calcH3();
    this.calcH2();
  },
  methods: {
    calcH2: function () {
      const parentEl = document.querySelector(this.parentSelector);
      if (!parentEl) return;
      
      let numberOfHeadings = parentEl.querySelectorAll("h2").length;
      
      // add a dummy heading at the end.
      const dummyHeading = document.createElement('h2');
      dummyHeading.style.cssText = 'display:inline; color:#fff; font-size:7;';
      dummyHeading.className = 'dummy-heading';
      dummyHeading.textContent = '.';
      parentEl.appendChild(dummyHeading);
      
      // iterate over all headings and determine the text length and number of images
      for (var i = 0; i < numberOfHeadings; i++) {
        let numberOfImages = 0;
        const fromm = parentEl.querySelectorAll("h2")[i];
        const to = parentEl.querySelectorAll("h2")[i + 1];
        
        // Get elements between fromm and to
        let current = fromm.nextElementSibling;
        let elements = [];
        while (current && current !== to) {
          elements.push(current);
          current = current.nextElementSibling;
        }
        
        // concat text from DOM
        var out = "";
        elements.forEach(el => {
          out = out + " " + el.textContent;
          if (el.tagName === "IMG" || el.querySelectorAll("img").length > 0) {
            numberOfImages++;
          }
        });
        
        const output = document.createElement('span');
        output.className = 'mx-0 my-1 p-0 section-info';
        output.style.cssText = 'font-size: 0.8em; color: #333333;';
        output.innerHTML = this.estimateTime(out, numberOfImages);
        fromm.after(output);
      }
      
      // Remove dummy heading
      const dummy = parentEl.querySelector('.dummy-heading');
      if (dummy) dummy.remove();
    },

    calcH3: function () {
      const parentEl = document.querySelector(this.parentSelector);
      if (!parentEl) return;
      
      let numberOfHeadings = parentEl.querySelectorAll("h3").length;
      
      // add a dummy heading at the end.
      const dummyHeading = document.createElement('h3');
      dummyHeading.style.cssText = 'display:inline;color:#fff;';
      dummyHeading.className = 'dummy-heading-3';
      dummyHeading.textContent = 'ENDE';
      parentEl.appendChild(dummyHeading);
      
      // iterate over all headings and determine the text length and number of images
      for (var i = 0; i < numberOfHeadings; i++) {
        let numberOfImages = 0;
        const fromm = parentEl.querySelectorAll("h3")[i];
        const to = parentEl.querySelectorAll("h3")[i + 1];
        
        // Get elements between fromm and to
        let current = fromm.nextElementSibling;
        let elements = [];
        while (current && current !== to) {
          elements.push(current);
          current = current.nextElementSibling;
        }
        
        // concat text from DOM
        var out = "";
        elements.forEach(el => {
          out = out + " " + el.textContent;
          if (el.tagName === "IMG" || el.querySelectorAll("img").length > 0) {
            numberOfImages++;
          }
        });
        
        const output = document.createElement('span');
        output.className = 'mx-0 my-3 p-0 d-inline section-info';
        output.style.cssText = 'font-size: 0.8em; color: #333333;';
        output.innerHTML = this.estimateTime(out, numberOfImages);
        fromm.after(output);
      }
      
      // Remove dummy heading
      const dummy = parentEl.querySelector('.dummy-heading-3');
      if (dummy) dummy.remove();
    },

    estimateTime: function (text, numImg) {
      if (text === "undefined" || text.length < 1) {
        //console.log("problem");
        return;
      }
      let textlength = text.match(/([\s]+)/g).length;
      numImg =
        parseInt(numImg, 10) === 0 || typeof numImg !== "number" ? 1 : numImg;
      let readingSpeed = this.readingSpeedPerLanguage[this.language];
      let readingTimeSlow = Math.ceil(
        textlength / (readingSpeed.cpm - readingSpeed.variance) + numImg * 0.3
      );
      let readingTimeFast = Math.ceil(
        textlength / (readingSpeed.cpm + readingSpeed.variance) + numImg * 0.3
      );
      this.slowSum += readingTimeSlow;
      this.fastSum += readingTimeFast;
      return (
        "Geschätzte Lesezeit: " +
        this.convertToReadableTime(readingTimeFast, readingTimeSlow)
      ); // + ' (' + textlength+' Wörter)';
    },

    convertToReadableTime: function (fasttime, slowtime) {
      //return time;
      let time = slowtime;
      if (slowtime < 60 && slowtime === fasttime) {
        return slowtime + " Minuten";
      } else if (slowtime < 60 && slowtime !== fasttime) {
        return fasttime + "-" + slowtime + " Minuten"; // '0:' + (time < 10 ? '0' + time : time);
      } else if (slowtime > 59 && fasttime < 3600) {
        let slowhours = Math.ceil(slowtime / 60);
        let slowminutes = slowtime % 60;
        let fasthours = Math.ceil(fasttime / 60);
        let fastminutes = fasttime % 60;

        return (
          fasthours +
          ":" +
          (fastminutes < 10 ? "0" + fastminutes : fastminutes) +
          " &ndash; " +
          slowhours +
          ":" +
          (slowminutes < 10 ? "0" + slowminutes : slowminutes) +
          " Stunden"
        );
      }
      return time; // should be a rar case, but needs to be treated in some way
    },
  },
};
</script>

<style>
</style>