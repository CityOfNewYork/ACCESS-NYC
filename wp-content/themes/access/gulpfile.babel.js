'use strict';

/***************
 * Dependencies
 ***************/

/**
 * Utility Deps
 */

import del from 'del';
import gulp from 'gulp';
import rename from 'gulp-rename';
import hashFilename from 'gulp-hash-filename';
import browserSync from 'browser-sync';
import through from 'through2';
import _ from 'underscore';

/**
 * Style Deps
 */

import sass from 'sass';
import gulpSass from 'gulp-sass';
import postcss from 'gulp-postcss';
import sourcemaps from 'gulp-sourcemaps';
import autoprefixer from 'autoprefixer';
import cssnano from 'cssnano';
import mqpacker from 'css-mqpacker';
import purgecss from '@fullhuman/postcss-purgecss';

/**
 * Script Deps
 */

import eslint from 'gulp-eslint';
import webpack from 'webpack-stream';
import named from 'vinyl-named';
import VueLoaderPlugin from 'vue-loader/lib/plugin';
import {CleanWebpackPlugin} from 'clean-webpack-plugin';

/**
 * Image Deps
 */

import cache from 'gulp-cache';
import imagemin from 'gulp-imagemin';

/**
 * SVG Deps
 */

import svgmin from 'gulp-svgmin';
import svgstore from 'gulp-svgstore';

/************
 * Constants
 ************/

const NODE_ENV = process.env.NODE_ENV;
const PROXY = process.env.PROXY;
const DIST = 'assets';
const SRC = 'src';
const VIEWS = 'views';
const NYCOPPORTUNITY = 'node_modules/@nycopportunity';
const PATTERNS_ACCESS = `${NYCOPPORTUNITY}/access-patterns`;
const PATTERNS_FRAMEWORK = `${NYCOPPORTUNITY}/patterns-framework`;
const HASH_FORMAT = '{name}.{hash:8}{ext}';
const HASH_FORMAT_WEBPACK = '[name].[chunkhash:8].js';
const HASH_FILES = ['manifest.json']; // more can be added

/********
 * Tasks
 ********/

/**
 * Styles
 */

gulp.task('clean:styles', callback => {
  del([
    './assets/styles/style-*.css',
    './assets/styles/style-*.css.map'
  ]);
  callback();
});

gulpSass.compiler = sass;

gulp.task('sass', () => gulp.src(`${SRC}/scss/style-*.scss`)
  .pipe(sourcemaps.init())
  .pipe(gulpSass({
    includePaths: ['node_modules', `${PATTERNS_ACCESS}/src/`]
    .concat(require('bourbon').includePaths)
  })
  .on('error', gulpSass.logError))
  .pipe(postcss([
    purgecss({
      content: ['./views/**/*.twig'],
      whitelistPatterns: [
        /fill-[a-z-]*/, // matches "fill-{{ token }}"
        /text-[a-z-]*/g, // matches "text-{{ token }}"
        /wpml-[a-z-]*/g, // matches "wpml-{{ token }}"
        /btn-[a-z-]*/g, // matches "btn-{{ token }}"
        /color-[a-z-]*/g // matches "color-{{ token }}"
      ],
      /**
       * Tailwindcss Extractor
       * @source https://tailwindcss.com/docs/controlling-file-size#setting-up-purge-css-manually
       */
      defaultExtractor: content => {
        // Capture as liberally as possible, including things like `h-(screen-1.5)`
        return content.match(/[^<>"'`\s]*[^<>"'`\s:]/g) || [];
      }
    }),
    autoprefixer(),
    mqpacker({sort: true}),
    cssnano()
  ]))
  .pipe(hashFilename({format: HASH_FORMAT}))
  .pipe(sourcemaps.write('./'))
  .pipe(gulp.dest('./assets/styles'))
);

gulp.task('styles', gulp.series('clean:styles', 'sass'));

/**
 * Scripts
 */

gulp.task('clean:scripts', callback => {
  del([
    `${DIST}/js/*`
  ]);
  callback();
});

gulp.task('lint', () =>
  gulp.src(`${SRC}/js/**/*.js`)
    .pipe(eslint({
      parser: 'babel-eslint',
      parserOptions: {
        ecmaVersion: 6,
        sourceType: 'module',
        ecmaFeatures: {
          modules: true
        }
      },
      rules: {
        strict: 0
      }
    }))
    .pipe(eslint.format())
);

gulp.task('webpack', () =>
  gulp.src(`${SRC}/js/*.js`)
    .pipe(named())
    .pipe(webpack({
      output: {
        filename: (NODE_ENV === 'development')
          ? `[name].${NODE_ENV}.js` : HASH_FORMAT_WEBPACK
      },
      mode: NODE_ENV,
      devtool: 'source-map',
      target: 'web',
      performance: {hints: false},
      watch: false,
      resolve: {
        modules: [
          'node_modules',
          `${PATTERNS_ACCESS}/src`,
          `${PATTERNS_ACCESS}/dist`,
          `${PATTERNS_FRAMEWORK}/src`,
          `${PATTERNS_FRAMEWORK}/dist`,
          `${SRC}/js`
        ]
      },
      module: {
        rules: [
          {
            // Vue Components
            loader: 'vue-loader',
            test: /\.vue$/
          },
          {
            // JavaScript (ES6)
            loader: 'babel-loader',
            test: /\.js$/,
            exclude: {
              test: /.\/node_modules/,
              exclude: /.\/node_modules\/@nycopportunity/
            },
            // include: [
            //   /.\/src/,
            //   /.\/node_modules\/@nycopportunity\/access-patterns\/src/
            // ],
            query: {
              // .babelrc
              presets: [
                [
                  '@babel/preset-env',
                  {
                    targets: {ie: '11'}
                  }
                ]
              ],
              plugins: [
                [
                  '@babel/plugin-transform-runtime',
                  {
                    'regenerator': true
                  }
                ]
              ]
            }
          },
          {
            test: /\.js$/,
            loader: 'string-replace-loader',
            options: {
              multiple: [
                 {search: 'SCREEN_DESKTOP', replace: '960'},
                 {search: 'SCREEN_TABLET', replace: '768'},
                 {search: 'SCREEN_MOBILE', replace: '480'},
                 {search: 'SCREEN_SM_MOBILE', replace: '400'}
              ]
            }
          }
        ]
      },
      plugins: [
        new VueLoaderPlugin(),
        new CleanWebpackPlugin({
          cleanOnceBeforeBuildPatterns: [`${DIST }/js/*`],
        }),
      ]
    }))
    .pipe(gulp.dest(`${DIST}/js`))
);

gulp.task('scripts', gulp.series('lint', 'webpack'));

/**
 * JST
 * This task precompiles underscore templates to avoid using unsafe eval
 * functions on the client side. They remain .twig templates because they
 * include string tags for content.
 */

gulp.task('jst', () => gulp.src(`${VIEWS }/**/*.jst.twig`)
  .pipe((() => through.obj(function (file, encoding, callback) {
    file.basename = file.basename.replace('jst.twig', 'js');

    let template = file.contents.toString('utf8');
    let compiled = [
        '// Compiled template. Do not edit.\n',
        'window.JST = window.JST || {};\n',
        'window.JST["' + file.relative.replace('.js', '') + '"] = ',
        _.template(template).source]
      .join('');

    file.contents = Buffer.from(compiled);

    callback(null, file);
  }))())
  .pipe(gulp.dest(`${VIEWS}/jst`))
);

/**
 * Hashing
 */

gulp.task('hashfiles', callback => {
  let oldhashfiles = [];
  for (let i = HASH_FILES.length - 1; i >= 0; i--) {
    oldhashfiles[i] = HASH_FILES[i].split('.').join('.*.');
  }

  del(oldhashfiles);

  gulp.src(HASH_FILES)
    .pipe(hashFilename({format: HASH_FORMAT}))
    .pipe(gulp.dest(f => f.base));

  callback();
});

/**
 * Images
 */

gulp.task('images', callback => {
  gulp.src([
      `${PATTERNS_ACCESS}/src/images/**/*.jpg`,
      `${PATTERNS_ACCESS}/src/images/**/*.png`,
      `${PATTERNS_ACCESS}/src/images/**/*.ico`,
      `${PATTERNS_ACCESS}/src/images/**/*.gif`
    ])
    .pipe(cache(imagemin({
      optimizationLevel: 5,
      progressive: true,
      interlaced: true
    })))
    .pipe(gulp.dest(`${DIST}/img`));

  gulp.src([
      `${PATTERNS_ACCESS}/dist/svg/**/*.svg`
    ])
    .pipe(gulp.dest(`${DIST}/svg`));

  callback();
});

/**
 * SVGs
 */

gulp.task('svgs', () =>
  gulp.src(`${PATTERNS_ACCESS}/src/svg/*.svg`)
    .pipe(svgmin())
    .pipe(svgstore({
      inlineSvg: true
    }))
    .pipe(rename('icons.svg'))
    .pipe(gulp.dest('assets/svg/'))
    .pipe(hashFilename({format: HASH_FORMAT}))
    .pipe(gulp.dest('assets/svg/'))
);

/**
 * All tasks needed to build the app
 */

gulp.task('build', gulp.parallel(
  'styles',
  'scripts',
  'jst',
  'svgs',
  'hashfiles'
));

/**
 * Watching Tasks
 */

gulp.task('default', () => {
  let reload = () => {
    browserSync.reload();
  };

  // Create a .env file in the theme directory to define this.
  browserSync.init({
    proxy: PROXY,
    port: 3001,
    ghostMode: {
      scroll: true
    },
    open: false
  });

  // Watch .scss files
  gulp.watch(`${SRC}/scss/**/*.scss`,
    gulp.series(
      'clean:styles',
      'styles',
      reload
    ));

  // Watch .js files. Watching is handled by Webpack
  if (NODE_ENV === 'production') {
    gulp.watch(`${SRC}/js/**/*.js`,
      gulp.series('lint')
    );
  }

  gulp.watch(`${SRC}/js/**/*.js`, gulp.series('webpack'));

  // Watch changes to underscore templates to compile them
  gulp.watch(`${VIEWS}/**/*.underscore.twig`, gulp.series('jst'));

  // Watch image files
  gulp.watch(`${SRC}/img/**/*`, gulp.series('images', reload));

  // Watch hashed files
  gulp.watch(HASH_FILES, gulp.series('hashfiles', reload));

  gulp.watch([
      'assets/**/*',
      'views/**/*',
      'includes/**/*'
    ], {dot: true})
      .on('change', reload);
});
