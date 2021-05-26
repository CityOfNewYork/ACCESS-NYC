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
import through from 'through2';
import underscore from 'underscore';

/**
 * Style Deps
 */

import sass from 'gulp-dart-sass';
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

gulp.task('sass', () => gulp.src(`${SRC}/scss/style-*.scss`)
  .pipe(sourcemaps.init())
  .pipe(sass({
    includePaths: [
      'node_modules',
      `${PATTERNS_ACCESS}/src/`
    ].concat(
      require('bourbon').includePaths
    )
  })
  .on('error', sass.logError))
  .pipe(postcss([
    purgecss({
      content: [
        './views/**/*.twig',
        './views/**/*.vue',
        './src/**/*.scss'
      ],
      fontFace: true,
      keyframes: true,
      whitelistPatterns: [
        /** Patterns */
        /o-[\S]*/g, // matches object patterns
        /c-[\S]*/g, // matches component patterns
        /** Button Patterns */
        /btn[\S]*/g, // matches "btn{{ token }}"
        /** Utilities */
        /error[\S]*/g, // matches "error{{ token }}"
        /fill-[\S]*/, // matches "fill-{{ token }}"
        /text-[\S]*/g, // matches "text-{{ token }}"
        /color-[\S]*/g, // matches "color-{{ token }}"
        /** WPML Class */
        /wpml-[\S]*/g, // matches "wpml-{{ token }}"
        /** Subway Icon Trunks */
        /bg-eighth-avenue/g,
        /bg-sixth-avenue/g,
        /bg-crosstown/g,
        /bg-canarsie/g,
        /bg-nassau/g,
        /bg-broadway/g,
        /bg-broadway-seventh-avenue/g,
        /bg-lexington-avenue/g,
        /bg-flushing/g,
        /bg-shuttles/g,
        /** spinner */
        /success/g,
        /processing/g
      ],
      // Example. Holds onto children of listed selectors
      whitelistPatternsChildren: [
        /c-share-form__success/g
      ],
      /**
       * Tailwindcss Extractor
       * @source https://tailwindcss.com/docs/controlling-file-size#setting-up-purge-css-manually
       */
      defaultExtractor: content => content.match(/[\w-/:]+(?<!:)/g) || []
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
      // Config from the NYCO Patterns Framework
      'extends': 'google',
      'env': {
        'browser': true,
        'es6': true
      },
      'parserOptions': {
        'ecmaVersion': 6,
        'sourceType': 'module'
      },
      'rules': {
        'no-console': 1,
        'one-var': 0,
        'comma-dangle': 0,
        // 'curly': [
        //   'error',
        //   'multi-or-nest'
        // ]
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
        underscore.template(template).source]
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
let list = [
  'icon-card-*-v2',
  'icon-cash-expenses-v2',
  'icon-child-care-v2',
  'icon-city-id-card-v2',
  'icon-education-v2',
  'icon-enrichment-v2',
  'icon-family-services-v2',
  'icon-food-v2',
  'icon-health-v2',
  'icon-housing-v2',
  'icon-people-with-disabilities-v2',
  'icon-work-v2',
  'icon-urgent',
  'icon-info',
  'icon-success',
  'icon-warning'
];

gulp.task('svgs:clean', (done) => {
    del([
      `${DIST}/svg/icons.*.svg`,
    ]);

    done();
});

gulp.task('svgs:list', () => gulp.src([
    `${VIEWS}/**/*.twig`,
    `${VIEWS}/**/*.vue`
  ]).pipe(through.obj((chunk, encoding, callback) => {
    const regex = /xlink:href="([\S]*)#([\S]+)"/g
    let content = chunk.contents.toString('utf8');
    let m;
    while ((m = regex.exec(content)) !== null) {
      list.push(m[2]);
    }
    callback(null, chunk);
  }))
);

gulp.task('svgs:add', () => gulp.src('assets/svg/icons.svg')
  .pipe(through.obj((chunk, encoding, callback) => {

    list = list.filter((item, index) => list.indexOf(item) === index)
      .map(item => `${PATTERNS_ACCESS}/src/svg/${item}.svg`);

    callback(null, chunk);
  }))
);

gulp.task('svgs:compile', () =>
  gulp.src(list)
    .pipe(svgmin())
    .pipe(svgstore({
      inlineSvg: true
    }))
    .pipe(rename('icons.svg'))
    .pipe(gulp.dest('assets/svg/'))
    .pipe(hashFilename({format: HASH_FORMAT}))
    .pipe(gulp.dest('assets/svg/'))
);

gulp.task('svgs', gulp.series(
  'svgs:clean',
  'svgs:list',
  'svgs:add',
  'svgs:compile'
));

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
  // Watch .scss files
  gulp.watch([
      `${SRC}/scss/**/*.scss`,
      './views/**/*.twig',
      './views/**/*.vue',
    ],
    gulp.series(
      'clean:styles',
      'styles'
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
  gulp.watch(`${SRC}/img/**/*`, gulp.series('images'));

  // Watch hashed files
  gulp.watch(HASH_FILES, gulp.series('hashfiles'));
});
