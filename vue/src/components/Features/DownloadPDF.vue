<template>
  <a
    href="javascript:void(0)"
    class="text-dark"
    role="button"
    :aria-label="
      $t('features.downloadPDF.label') || 'Seite als PDF herunterladen'
    "
    :title="$t('features.downloadPDF.label') || 'Seite als PDF herunterladen'"
    @click="downloadPDF"
  >
    <i class="icon fa fa-fw text-dark fa-download" aria-hidden="true" />
  </a>
</template>

<script>
import { GET } from "@/store/types";
import { jsPDF, FontFace } from "jspdf";
import html2canvas from "html2canvas";
import { AnnotationFactory } from "annotpdf";
import * as pdfjs from "pdfjs-dist";
import pdfjsWorker from "pdfjs-dist/build/pdf.worker.entry";
import { toRaw } from "vue";
import { mapGetters } from "vuex";
import { LONGPAGE_CONTENT_ID } from "@/util/constants";
import { lazyModules } from "@/store";

export default {
  name: "DownloadPDF",
  computed: {
    ...mapGetters("annotation", { annotations: GET.ANNOTATIONS }),
    ...mapGetters({ getUser: GET.USER }),
  },
  methods: {
    async ensureAnnotationsLoaded() {
      // Ensure AnnotationModule is loaded before accessing annotations
      if (!this.$store.hasModule("annotation")) {
        try {
          const module = await lazyModules.AnnotationModule();
          this.$store.registerModule("annotation", module.default);
        } catch (error) {
          console.error("Failed to load AnnotationModule:", error);
        }
      }
    },
    gettext(pdf) {
      var maxPages = pdf.numPages;
      var countPromises = [];
      for (var j = 1; j <= maxPages; j++) {
        var page = pdf.getPage(j);

        countPromises.push(
          page.then(function (page) {
            var textContent = page.getTextContent();
            return textContent.then(function (text) {
              return text.items;
            });
          }),
        );
      }
      return Promise.all(countPromises).then(function (texts) {
        return texts;
      });
    },
    convertToCanvasCoords([x, y, width, height]) {
      var scale = 1;
      var margin = 15;
      return [
        x * scale,
        (y + height) * scale - margin,
        width * scale,
        height * scale,
      ];
    },
    findtext(doc, items, selector, startPage = 0, startIdx = 0) {
      var maxPages = items.length;
      var coords = [-1, 0, 0, 0, 0, startPage, startIdx];
      var str = (selector.prefix + selector.exact + selector.suffix)
        .replace(/(?:\r\n|\r|\n)/g, "")
        .replace(/\s/g, "");
      var strExact = selector.exact
        .replace(/(?:\r\n|\r|\n)/g, "")
        .replace(/\s/g, "");
      for (var j = startPage; j < maxPages; j++) {
        var textItems = items[j];
        if (j > startPage) {
          startIdx = 0;
        }
        var pageItems = textItems.map((item) => item.str.replace(/\s/g, ""));
        var pageStr = pageItems.join("");
        var idx = pageStr.indexOf(str);
        if (idx != -1) //found
        {
          var idxExact = pageStr.indexOf(
            strExact,
            idx + selector.prefix.replace(/\s/g, "").length - 1,
          );
          if (idxExact == -1) continue;
          var cumsum = 0;
          var i = 0;
          for (; i < pageItems.length; i++) {
            cumsum += pageItems[i].length;
            if (cumsum > idxExact) break;
          }

          var item = textItems[i];
          var x = item.transform[4]; // + doc.getTextWidth(item.str.substring(0, item.str.indexOf(selector.exact)))*0.65;
          var y = item.transform[5];
          var width = doc.getTextWidth(selector.exact) * 1.2; //factor empirically found... 1.2  0.65
          var height = item.height;
          var conv = this.convertToCanvasCoords([x, y, width, height]);
          coords[0] = 1;
          coords[1] = conv[0];
          coords[2] = conv[1];
          coords[3] = conv[2];
          coords[4] = conv[3];
          coords[5] = j;
          coords[6] = idxExact;
          return coords;
        }
      }
      return coords;
    },
    annotate(doc, pdf, texts) {
      var factory = new AnnotationFactory(pdf);
      var result = [0, 0, 0, 0, 0, 0];
      var annots = toRaw(this.annotations);
      annots.sort(
        (a, b) => a.target.selectors[1].start - b.target.selectors[1].start,
      );
      var lastPage = 0;
      var lastIdx = 0;
      var rect = [];
      var found = 0;
      var coords = [];
      annots.forEach((annotation) => {
        if (annotation.type > 1) return;
        var text = annotation.target.selectors[2].exact;
        result = this.findtext(
          doc,
          texts,
          annotation.target.selectors[2],
          Math.max(0, lastPage - 1),
          Math.max(0, lastIdx - 40),
        );
        found = result[0];
        if (found == 1) {
          lastPage = result[5];
          lastIdx = result[6];
          coords = result.slice(1, 5);
          rect = [
            coords[0],
            coords[1],
            coords[0] + coords[2],
            coords[1] + coords[3],
          ];
        }
        switch (annotation.type) {
          case 0:
            if (found == 1) {
              const styleElement = document.querySelector(
                "." + annotation.target.styleClass,
              );
              var color = styleElement
                ? window.getComputedStyle(styleElement).backgroundColor
                : "rgb(255,255,255)";
              color = color == null ? "rgb(255,255,255)" : color;
              factory.createHighlightAnnotation({
                page: lastPage,
                rect: rect,
                contents: text,
                author: this.getUser(annotation.creatorId).fullName,
                updateDate: annotation.timeModified,
                color: {
                  r: color.slice(4, color.indexOf(",")),
                  g: color.slice(
                    color.indexOf(",") + 2,
                    color.indexOf(",", color.indexOf(",") + 1),
                  ),
                  b: color.slice(
                    color.indexOf(",", color.indexOf(",") + 1) + 2,
                    color.length - 1,
                  ),
                },
                opacity: 1,
              });
            }
            break;
          case 1:
            if (found == 1) {
              annotation.body.posts.forEach((post) => {
                factory.createTextAnnotation({
                  page: lastPage,
                  rect: rect,
                  contents: post.content,
                  author: this.getUser(post.creatorId).fullName,
                  updateDate: post.timeModified,
                  opacity: 1,
                });
              });
            }
            break;
        }
      });
      factory.download(document.title + ".pdf");
    },
    async downloadPDF() {
      // Ensure annotations are loaded before generating PDF
      await this.ensureAnnotationsLoaded();

      var doc = new jsPDF({
        orientation: "portrait",
        unit: "pt",
        format: "a4",
      });

      // fetch("vue/src/ARIALUNI.TTF").then((res) => res.blob()).then().then(result => {
      //   return new Promise((resolve) => {
      //     const reader = new FileReader();
      //     reader.onloadend = () => resolve(reader.result);
      //     reader.readAsDataURL(result);
      //   })
      // }).then(result => {
      //   var fileName = "ARIALUNI.TTF";
      //   doc.addFileToVFS(fileName, result.split(",")[1]);
      //   doc.addFont(fileName, "ARIALUNI", 'normal');
      //   doc.setFont("ARIALUNI");
      //   doc.setFontSize(20);
      // });
      var texts = null;
      var _this = this;

      var html = document.getElementById(LONGPAGE_CONTENT_ID).cloneNode(true);

      // Unwrap em, longpage-highlight, and strong elements
      html.querySelectorAll("em, longpage-highlight, strong").forEach((el) => {
        while (el.firstChild) {
          el.parentNode.insertBefore(el.firstChild, el);
        }
        el.remove();
      });

      html.style.hyphens = "none";
      html.style.textAlign = "justify";

      doc.html(html, {
        callback: function (doc) {
          var pdf = doc.output("arraybuffer");
          var pdfData = null;
          pdfjs
            .getDocument(pdf) //"1801-KE1.pdf")//pdf
            .promise.then(function (pdfDoc) {
              if (texts == null) {
                _this.gettext(pdfDoc).then(function (t) {
                  texts = t;
                  pdfDoc.getData().then(function (data) {
                    pdfData = data;
                    _this.annotate(doc, pdfData, texts);
                  });
                });
              } else {
                _this.annotate(doc, pdfData, texts);
              }
            });
        },
        margin: 50,
        html2canvas: {
          scale: 0.65,
          backgroundColor: null,
        },
        autoPaging: "text",
        width: 750, //document.getElementById("longpage-content").clientWidth,
        windowWidth: 750, // document.getElementById("longpage-content").clientWidth
      });
    },
  },
};
</script>
