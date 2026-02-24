<template>
  <div class="download-pdf-container">
    <button
      type="button"
      class="btn btn-secondary btn-sm d-flex align-items-center"
      :aria-label="
        $t('features.downloadPDF.label') || 'Seite als PDF herunterladen'
      "
      :title="$t('features.downloadPDF.label') || 'Seite als PDF herunterladen'"
      :disabled="isGenerating"
      @click="downloadPDF"
    >
      <i v-if="!isGenerating" class="fa fa-download mr-1" aria-hidden="true" />
      <i v-else class="fa fa-spinner fa-spin mr-1" aria-hidden="true" />
      <span>{{
        isGenerating
          ? $t("features.downloadPDF.generating")
          : $t("features.downloadPDF.button")
      }}</span>
    </button>
  </div>
</template>

<script>
import { GET } from "@/store/types";
import { jsPDF } from "jspdf";
import html2canvas from "html2canvas";
import { toRaw } from "vue";
import { mapGetters } from "vuex";
import { LONGPAGE_CONTENT_ID } from "@/util/constants";
import { lazyModules } from "@/store";

export default {
  name: "DownloadPDF",
  data() {
    return {
      isGenerating: false,
    };
  },
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
    /**
     * Apply inline styles to highlights so they render properly in PDF
     */
    applyInlineStylesToHighlights(html) {
      const highlightStyles = {
        "bg-yellow": { background: "#ffe47c" },
        "bg-green": { background: "#6fe2d5" },
        "bg-orange": { background: "#ffbea9" },
        "bg-pink": { background: "#fec1de" },
        "bg-blue": { background: "#b3d9ff" },
        underline: {
          textDecoration: "underline",
          textDecorationColor: "#ff0000",
        },
      };

      html.querySelectorAll("longpage-highlight").forEach((el) => {
        // Apply all matching style classes (don't break after first match)
        for (const [className, styles] of Object.entries(highlightStyles)) {
          if (el.classList.contains(className)) {
            Object.assign(el.style, styles);
          }
        }

        // Also check for underline class specifically
        if (el.classList.contains("underline")) {
          el.style.textDecoration = "underline";
          el.style.textDecorationColor = "#ff0000";
          el.style.textDecorationThickness = "2px";
        }

        // Ensure proper inline alignment - use negative margin to shift up
        el.style.display = "inline";
        el.style.verticalAlign = "baseline";
        el.style.lineHeight = "inherit";
        el.style.padding = "0";
        el.style.margin = "0";
        el.style.position = "relative";
        el.style.top = "-15px";
      });

      return html;
    },
    /**
     * Create a section with posts/comments to append to the PDF
     */
    createAnnotationsSummary(annotations) {
      const postAnnotations = toRaw(annotations || []).filter(
        (a) => a.type === 1 && a.body?.posts?.length > 0,
      );

      if (postAnnotations.length === 0) {
        return null;
      }

      const container = document.createElement("div");
      container.style.marginTop = "40px";
      container.style.paddingTop = "20px";
      container.style.borderTop = "2px solid #333";
      container.style.pageBreakBefore = "always";

      const title = document.createElement("h2");
      title.textContent = this.$t(
        "features.downloadPDF.annotationsSummaryTitle",
      );
      title.style.marginBottom = "20px";
      title.style.fontSize = "18px";
      title.style.fontWeight = "bold";
      container.appendChild(title);

      postAnnotations.forEach((annotation) => {
        const annotContainer = document.createElement("div");
        annotContainer.style.marginBottom = "15px";
        annotContainer.style.padding = "10px";
        annotContainer.style.backgroundColor = "#f5f5f5";
        annotContainer.style.borderLeft = "3px solid #007bff";

        // Quote the highlighted text
        const quote = document.createElement("blockquote");
        quote.style.fontStyle = "italic";
        quote.style.marginBottom = "8px";
        quote.style.color = "#555";
        quote.style.borderLeft = "none";
        quote.style.paddingLeft = "0";
        const quoteText = annotation.target?.selectors?.[2]?.exact || "";
        quote.textContent =
          '"' +
          quoteText.substring(0, 150) +
          (quoteText.length > 150 ? "..." : "") +
          '"';
        annotContainer.appendChild(quote);

        // Add posts
        annotation.body.posts.forEach((post) => {
          const postDiv = document.createElement("div");
          postDiv.style.marginTop = "5px";

          const author = this.getUser(post.creatorId);
          const authorName =
            author?.fullName || this.$t("features.downloadPDF.unknownAuthor");
          const date = new Date(post.timeModified * 1000).toLocaleDateString(
            "de-DE",
          );

          const metaSpan = document.createElement("span");
          metaSpan.style.fontWeight = "bold";
          metaSpan.style.fontSize = "12px";
          metaSpan.textContent = authorName + " (" + date + "): ";
          postDiv.appendChild(metaSpan);

          const contentSpan = document.createElement("span");
          contentSpan.style.fontSize = "12px";
          contentSpan.textContent = post.content;
          postDiv.appendChild(contentSpan);

          annotContainer.appendChild(postDiv);
        });

        container.appendChild(annotContainer);
      });

      return container;
    },
    async downloadPDF() {
      if (this.isGenerating) return;

      this.isGenerating = true;

      try {
        // Ensure annotations are loaded before generating PDF
        await this.ensureAnnotationsLoaded();

        const doc = new jsPDF({
          orientation: "portrait",
          unit: "pt",
          format: "a4",
        });

        const _this = this;

        const html = document
          .getElementById(LONGPAGE_CONTENT_ID)
          .cloneNode(true);

        // Apply inline styles to keep highlights visible in the PDF
        this.applyInlineStylesToHighlights(html);

        // Remove unwanted elements but KEEP highlights
        html
          .querySelectorAll(
            ".annotation-toolbar-item, .longpage-annotation-indicator",
          )
          .forEach((el) => {
            el.remove();
          });

        // Create annotations summary section
        const annotationsSummary = this.createAnnotationsSummary(
          this.annotations,
        );
        if (annotationsSummary) {
          html.appendChild(annotationsSummary);
        }

        html.style.hyphens = "none";
        html.style.textAlign = "justify";

        doc.html(html, {
          callback: function (doc) {
            doc.save(document.title + ".pdf");
            _this.isGenerating = false;
          },
          margin: 50,
          html2canvas: {
            scale: 0.65,
            backgroundColor: "#ffffff",
          },
          autoPaging: "text",
          width: 750,
          windowWidth: 750,
        });
      } catch (error) {
        console.error("PDF export failed:", error);
        this.isGenerating = false;
        alert(this.$t("features.downloadPDF.errorMessage"));
      }
    },
  },
};
</script>

<style scoped>
.download-pdf-container {
  display: inline-block;
}
</style>
