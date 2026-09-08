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
 * @copyright  2026 Niels Seidel <niels.seidel@fernuni-hagen.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

//const {BundleAnalyzerPlugin} = require('webpack-bundle-analyzer');
const CompressionPlugin = require("compression-webpack-plugin");
const fs = require("fs");
var path = require("path");
const TerserPlugin = require("terser-webpack-plugin");
const { VueLoaderPlugin } = require("vue-loader");
const WebpackShellPlugin = require("webpack-shell-plugin-next");
var webpack = require("webpack");

module.exports = (env, options) => {
  const exports = {
    entry: "./src/main.js",
    output: {
      path: path.resolve(__dirname, "../amd/build"),
      publicPath: "",
      filename: "app-lazy.min.js",
      chunkFilename: "[id].app-lazy.js?v=[hash]",
      library: {
        type: "amd",
        export: "default",
      },
    },
    target: "web",
    module: {
      rules: [
        {
          test: /\.js?$/,
          loader: "babel-loader",
          exclude: /node_modules/,
          options: {
            babelrc: false,
            presets: [
              //["@vue/babel-preset-app"],
              [
                "@babel/preset-env",
                {
                  forceAllTransforms: true,
                },
              ],
            ],
            //["@babel/preset-es2015"],//
            plugins: [
              "@babel/plugin-proposal-class-properties",
              "@babel/plugin-syntax-dynamic-import",
              "@babel/plugin-transform-modules-amd",
              "@babel/plugin-proposal-async-generator-functions",
              "@babel/plugin-proposal-async-do-expressions",
              "@babel/plugin-proposal-export-default-from",
              //"babel-plugin-transform-remove-strict-mode"/**/
            ],
          },
        },
        {
          test: /\.(sa|sc|c)ss$/,
          use: [
            "vue-style-loader",
            // Creates `style` nodes from JS strings
            "css-loader",
            // Compiles Sass to CSS
            {
              loader: "sass-loader",
              options: {
                api: "modern",
                sassOptions: {
                  silenceDeprecations: ["legacy-js-api", "import"],
                },
              },
            },
          ],
        },
        {
          test: /\.vue$/,
          loader: "vue-loader",
          options: {
            loaders: {
              //scss: 'vue-style-loader!css-loader!sass-loader',
              //prettify: false
            },
          },
        } /*,
                {
                    test: /\.(eot|svg|ttf|woff|woff2)$/,
                    loader: 'url-loader'
                }*/,
      ],
    },
    resolve: {
      alias: {
        "@": path.resolve(__dirname, "src"),
        vue$: "vue/dist/vue.esm-bundler.js",
      },
      extensions: ["*", ".js", ".vue", ".json"],
    },
    devServer: {
      historyApiFallback: true,
      noInfo: true,
      overlay: true,
      headers: {
        "Access-Control-Allow-Origin": "\*",
      },
      disableHostCheck: true,
      https: true,
      public: "https://127.0.0.1:8080",
      hot: true,
    },
    performance: {
      hints: false,
    },
    devtool: false, // Set per mode below
    plugins: [
      //new BundleAnalyzerPlugin(),
      new VueLoaderPlugin(),
      new webpack.ProvidePlugin({
        $: "jquery",
        jQuery: "jquery",
        "window.jQuery": "jquery",
      }),
      // Custom plugin to clean old build files before build starts
      {
        apply: (compiler) => {
          compiler.hooks.beforeRun.tap("CleanBeforeBuild", () => {
            // Delete specific files
            const buildDir = path.resolve(__dirname, "../amd/build");
            const srcDir = path.resolve(__dirname, "../amd/src");

            // Delete app-lazy.min.js
            const mainBuildFile = path.join(buildDir, "app-lazy.min.js");
            if (fs.existsSync(mainBuildFile)) {
              fs.unlinkSync(mainBuildFile);
            }

            // Delete all *.app-lazy.js files in build
            if (fs.existsSync(buildDir)) {
              fs.readdirSync(buildDir)
                .filter((file) => file.endsWith(".app-lazy.js"))
                .forEach((file) => fs.unlinkSync(path.join(buildDir, file)));
            }

            // Delete app-lazy.js in src
            const srcFile = path.join(srcDir, "app-lazy.js");
            if (fs.existsSync(srcFile)) {
              fs.unlinkSync(srcFile);
            }
          });

          // Copy built file to amd/src after emit is complete
          compiler.hooks.afterEmit.tap("CopyToSrc", () => {
            const srcPath = path.resolve(
              __dirname,
              "../amd/build/app-lazy.min.js",
            );
            const destPath = path.resolve(__dirname, "../amd/src/app-lazy.js");
            if (fs.existsSync(srcPath)) {
              fs.copyFileSync(srcPath, destPath);
              console.log("Copied app-lazy.min.js to amd/src/app-lazy.js");
            } else {
              console.error("Warning: app-lazy.min.js not found for copying");
            }
          });
        },
      },
      new WebpackShellPlugin({
        onBuildEnd: [
          (process.platform === "win32" ? "php.exe" : "php") +
            " " +
            path.resolve(__dirname, "../../../admin/cli/purge_caches.php") +
            " --muc --theme --lang --js --filter --other",
        ],
      }),
    ],
    watchOptions: {
      ignored: /node_modules/,
    },
    externals: {
      "core/ajax": {
        amd: "core/ajax",
      },
      "core/str": {
        amd: "core/str",
      },
      "core/modal_factory": {
        amd: "core/modal_factory",
      },
      "core/modal_events": {
        amd: "core/modal_events",
      },
      "core/fragment": {
        amd: "core/fragment",
      },
      "core/yui": {
        amd: "core/yui",
      },
      "core/localstorage": {
        amd: "core/localstorage",
      },
      "core/notification": {
        amd: "core/notification",
      },
      "core/pubsub": {
        amd: "core/pubsub",
      },
      "theme_boost/bootstrap/tooltip": {
        amd: "theme_boost/bootstrap/tooltip",
      },
      jquery: {
        amd: "jquery",
      },
    },
  };
  console.log("MODE:: ", options);
  if (options.mode === "production") {
    console.log("MODE:: ", options.mode);
    // Production: No source maps, full minification
    exports.devtool = false;
    // http://vue-loader.vuejs.org/en/workflow/production.html
    // exp
    exports.plugins = (exports.plugins || []).concat([
      new webpack.DefinePlugin({
        "process.env": {
          NODE_ENV: '"production"',
        },
      }),
      new webpack.LoaderOptionsPlugin({
        minimize: true,
      }),
      new webpack.optimize.ModuleConcatenationPlugin(),
      // Brotli compression (best compression ratio)
      new CompressionPlugin({
        filename: "[path][base].br",
        algorithm: "brotliCompress",
        test: /\.(js|css|html|svg)$/,
        compressionOptions: {
          level: 11, // Maximum compression
        },
        threshold: 10240, // Only compress files > 10KB
        minRatio: 0.8,
        deleteOriginalAssets: false,
      }),
      // Gzip compression (fallback for older browsers)
      new CompressionPlugin({
        filename: "[path][base].gz",
        algorithm: "gzip",
        test: /\.(js|css|html|svg)$/,
        compressionOptions: {
          level: 9, // Maximum gzip compression
        },
        threshold: 10240,
        minRatio: 0.8,
        deleteOriginalAssets: false,
      }),
    ]);
    exports.optimization = {
      minimize: true,
      nodeEnv: "production",
      usedExports: true, // Enable tree shaking
      sideEffects: false, // All modules have no side effects
      minimizer: [
        new TerserPlugin({
          parallel: true,
          extractComments: false,
          terserOptions: {
            compress: {
              //drop_console: true, // Remove all console.* calls
              drop_debugger: true, // Remove debugger statements
              pure_funcs: ["console.log", "console.info", "console.debug"], // Remove specific functions
              passes: 2, // Multiple compression passes
            },
            mangle: {
              safari10: true, // Fix Safari 10/11 bugs
            },
            format: {
              comments: false, // Remove all comments
              ascii_only: true, // Escape non-ASCII characters
            },
          },
        }),
      ],
    };
  } else if (options.mode === "development") {
    console.log("MODE:: ", options.mode);
    // Development: Source maps enabled, no minification, faster builds
    exports.devtool = "eval-source-map";
    exports.optimization = {
      minimize: false, // No minification in dev
      nodeEnv: "development",
      usedExports: true, // Still tree shake in dev
    };
    exports.plugins = (exports.plugins || []).concat([
      new webpack.DefinePlugin({
        "process.env": {
          NODE_ENV: '"development"',
        },
      }),
    ]);
  }
  return exports;
};
