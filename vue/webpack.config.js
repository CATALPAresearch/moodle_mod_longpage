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

//const {BundleAnalyzerPlugin} = require('webpack-bundle-analyzer');
const FileManagerPlugin = require("filemanager-webpack-plugin");
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
      publicPath: "/dist/",
      filename: "app-lazy.min.js",
      chunkFilename: "[id].app-lazy.js?v=[hash]",
      libraryTarget: "amd",
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
    //devtool: 'eval-source-map',
    plugins: [
      //new BundleAnalyzerPlugin(),
      new VueLoaderPlugin(),
      new webpack.ProvidePlugin({
        $: "jquery",
        jQuery: "jquery",
        "window.jQuery": "jquery",
      }),
      new FileManagerPlugin({
        events: {
          onStart: {
            delete: [
              {
                source: path.resolve(__dirname, "../amd/src/app-lazy.js"),
                options: { force: true },
              },
              {
                source: path.resolve(__dirname, "../amd/build/app-lazy.min.js"),
                options: { force: true },
              },
            ],
          },
          onEnd: {
            copy: [
              {
                source: path.resolve(__dirname, "../amd/build"),
                destination: path.resolve(__dirname, "../amd/src"),
              },
            ],
            move: [
              {
                source: path.resolve(__dirname, "../amd/src/app-lazy.min.js"),
                destination: path.resolve(__dirname, "../amd/src/app-lazy.js"),
              },
            ],
            delete: [
              {
                source: path.resolve(__dirname, "../amd/src/app-lazy.min.js"),
                options: { force: true },
              },
            ],
          },
        },
      }),
      new WebpackShellPlugin({
        onBuildEnd: [
          path.resolve(__dirname, "../../../../php/php.exe") +
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
              drop_console: true, // Remove all console.* calls
              drop_debugger: true, // Remove debugger statements
              pure_funcs: ["console.log", "console.info", "console.debug"], // Remove specific functions
              passes: 2, // Multiple compression passes
            },
            mangle: {
              safari10: true, // Fix Safari 10/11 bugs
            },
            format: {
              comments: false, // Remove all comments
            },
          },
        }),
      ],
    };
  }
  return exports;
};
