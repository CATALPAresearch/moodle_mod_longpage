/**
 * Longpage TinyMCE conflict prevention script
 *
 * This script prevents conflicts between the Vue.js application
 * and TinyMCE editor initialization in form editing contexts.
 * Includes aggressive standards mode enforcement.
 */

(function () {
  "use strict";

  console.log("Longpage: Advanced conflict prevention script loading...");

  // EMERGENCY: Override TinyMCE standards mode check BEFORE it loads
  var originalTinyMCE = window.tinymce;

  // Intercept TinyMCE loading
  Object.defineProperty(window, "tinymce", {
    get: function () {
      return originalTinyMCE;
    },
    set: function (value) {
      if (value && typeof value === "object") {
        console.log(
          "Longpage: Intercepting TinyMCE setup for standards mode fix",
        );

        // Force environment settings
        if (value.Env) {
          value.Env.quirks = false;
          value.Env.webkit = false;
          value.Env.ie = false;
        }

        // Override init method to force standards mode
        if (value.init) {
          var originalInit = value.init;
          value.init = function (settings) {
            console.log("Longpage: Applying TinyMCE standards mode override");

            // Force document into standards mode for TinyMCE
            var originalCompatMode = document.compatMode;
            if (originalCompatMode !== "CSS1Compat") {
              try {
                Object.defineProperty(document, "compatMode", {
                  value: "CSS1Compat",
                  configurable: true,
                });
                console.log(
                  "Longpage: Successfully overrode document.compatMode to CSS1Compat",
                );
              } catch (e) {
                console.warn("Longpage: Could not override compatMode:", e);
              }
            }

            // Ensure proper settings
            settings = settings || {};
            settings.doctype = "<!DOCTYPE html>";
            settings.forced_root_block = false;

            try {
              return originalInit.call(this, settings);
            } finally {
              // Restore original compatMode if we changed it
              if (originalCompatMode !== "CSS1Compat") {
                try {
                  Object.defineProperty(document, "compatMode", {
                    value: originalCompatMode,
                    configurable: true,
                  });
                } catch (e) {
                  // Ignore restore errors
                }
              }
            }
          };
        }
      }
      originalTinyMCE = value;
    },
    configurable: true,
  });

  // Set editing flag IMMEDIATELY
  window.EDITING_LONGPAGE_MODULE = true;

  // Block any potential Vue.js loading
  if (typeof window.define === "function") {
    try {
      window.require.undef("mod_longpage/app-lazy");
    } catch (e) {
      // Module might not be loaded yet
    }
  }

  // Comprehensive editor conflict prevention
  function preventEditorConflicts() {
    console.log("Longpage: Applying comprehensive editor conflict prevention");

    // AGGRESSIVE standards mode enforcement
    var currentCompatMode = document.compatMode;
    console.log("Longpage: Current document.compatMode:", currentCompatMode);
    console.log(
      "Longpage: Current DOCTYPE:",
      document.doctype ? document.doctype.name : "NONE",
    );

    if (!document.doctype || currentCompatMode !== "CSS1Compat") {
      console.warn(
        "Longpage: Document not in standards mode - attempting comprehensive fix",
      );

      // Method 1: Add proper DOCTYPE if missing
      if (!document.doctype) {
        console.log("Longpage: No DOCTYPE found, creating one");
        var doctype = document.implementation.createDocumentType(
          "html",
          "",
          "",
        );
        if (document.firstChild) {
          document.insertBefore(doctype, document.firstChild);
        } else {
          document.appendChild(doctype);
        }
      }

      // Method 2: Force IE compatibility mode
      var ieMetaExists = false;
      var metas = document.getElementsByTagName("meta");
      for (var i = 0; i < metas.length; i++) {
        if (metas[i].getAttribute("http-equiv") === "X-UA-Compatible") {
          ieMetaExists = true;
          metas[i].setAttribute("content", "IE=edge");
          break;
        }
      }

      if (!ieMetaExists) {
        var meta = document.createElement("meta");
        meta.setAttribute("http-equiv", "X-UA-Compatible");
        meta.setAttribute("content", "IE=edge");
        var head = document.getElementsByTagName("head")[0];
        if (head && head.firstChild) {
          head.insertBefore(meta, head.firstChild);
        } else if (head) {
          head.appendChild(meta);
        }
      }

      // Method 3: Force body/html attributes for standards mode
      if (document.documentElement) {
        document.documentElement.setAttribute("data-forced-standards", "true");
      }
      if (document.body) {
        document.body.setAttribute("data-forced-standards", "true");
      }

      console.log(
        "Longpage: Applied standards mode fixes, new compatMode:",
        document.compatMode,
      );
    } else {
      console.log("Longpage: Document already in standards mode");
    }

    // Disable all atto_embedquestion functionality
    if (window.jQuery) {
      window.jQuery(document).ready(function ($) {
        // Hide and disable embedquestion buttons completely
        $(".atto_embedquestion_button").each(function () {
          $(this).hide().prop("disabled", true).off();
        });

        // Remove embedquestion event handlers
        $(".embedExistingQuestion, .atto_embedquestion_button").off("click");

        console.log("Longpage: Atto embedquestion buttons disabled");
      });
    }

    // Clean up Vue.js instances
    if (window.Vue) {
      window.Vue.config.silent = true;
      if (window.Vue._installedPlugins) {
        window.Vue._installedPlugins = [];
      }
    }

    // Ensure TinyMCE compatibility
    if (window.tinyMCE) {
      try {
        // Handle different TinyMCE versions and structures
        if (
          window.tinyMCE.dom &&
          window.tinyMCE.dom.DOMUtils &&
          window.tinyMCE.dom.DOMUtils.prototype
        ) {
          window.tinyMCE.dom.DOMUtils.prototype.doc = document;
        }

        // Alternative approach for newer TinyMCE versions
        if (
          window.tinyMCE.DOM &&
          typeof window.tinyMCE.DOM.doc !== "undefined"
        ) {
          window.tinyMCE.DOM.doc = document;
        }

        // Ensure proper document mode setting for TinyMCE
        if (window.tinyMCE.Env) {
          window.tinyMCE.Env.quirks = false;
        }

        console.log("Longpage: TinyMCE compatibility ensured");
      } catch (e) {
        console.log(
          "Longpage: TinyMCE compatibility adjustment failed (this might be normal):",
          e.message,
        );
      }
    }

    // Override any Moodle editor initialization that might conflict
    if (window.M && window.M.editor_atto && window.M.editor_atto.plugins) {
      if (window.M.editor_atto.plugins.embedquestion) {
        window.M.editor_atto.plugins.embedquestion.Button = function () {
          return {
            initialise: function () {
              console.log("Longpage: Embedquestion plugin blocked");
            },
            destroy: function () {},
          };
        };
      }
    }

    console.log("Longpage: Editor conflict prevention complete");
  }

  // Apply fixes immediately if DOM is ready, otherwise wait
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", preventEditorConflicts);
  } else {
    preventEditorConflicts();
  }

  // Also apply on window load as backup
  window.addEventListener("load", preventEditorConflicts);

  console.log("Longpage: Conflict prevention script loaded successfully");
})();
