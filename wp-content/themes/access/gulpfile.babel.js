'use strict';

/****************
 * Dependencies
 ***************/

import 'dotenv/config';

/**
 * Utility Deps
 */
import del from 'del';
import gulp from 'gulp';
import notify from 'gulp-notify';
import gulpif from 'gulp-if';
import rename from 'gulp-rename';
import hashFilename from 'gulp-hash-filename';
import browserSync from 'browser-sync';

/**
 * Style Deps
 */
import sass from 'gulp-sass';
import postcss from 'gulp-postcss';
import sourcemaps from 'gulp-sourcemaps';
import autoprefixer from 'autoprefixer';
import cssnano from 'cssnano';
import mqpacker from 'css-mqpacker';

/**
 * Script Deps
 */
import eslint from 'gulp-eslint';
import webpack from 'webpack-stream';
import named from 'vinyl-named';
import VueLoaderPlugin from 'vue-loader/lib/plugin';
// import TerserPlugin from 'terser-webpack-plugin';

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

/*************
 * Constants
 ************/

const NODE_ENV = process.env.NODE_ENV;
const DIST = 'assets';
const SRC = 'src';
const PATTERNS_SRC = 'node_modules/access-nyc-patterns/src';
const PATTERNS_DIST = 'node_modules/access-nyc-patterns/dist';
const HASH_FORMAT = '{name}.{hash:8}{ext}';
const HASH_FORMAT_WEBPACK = '[name].[chunkhash:8].js';
const HASH_FILES = [
  'manifest-screener-field.json',
  'manifest.json'
];

/*********
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

gulp.task('sass', () => gulp.src(`${ SRC }/scss/style-*.scss`)
  .pipe(sourcemaps.init())
  .pipe(sass({
    includePaths: ['node_modules', PATTERNS_SRC]
    .concat(require('bourbon').includePaths)
  })
  .on('error', notify.onError())
  .on('error', sass.logError))
  .pipe(postcss([
    autoprefixer('last 2 versions'),
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
    `${ DIST }/js/*`
  ]);
  callback();
});

gulp.task('lint', () =>
  gulp.src(`${ SRC }/js/**/*.js`)
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
  gulp.src(`${ SRC }/js/*.js`)
  .pipe(named())
  .pipe(webpack({
    output: { filename: HASH_FORMAT_WEBPACK },
    mode: NODE_ENV,
    devtool: 'source-map',
    target: 'web',
    performance: { hints: false },
    resolve: {
      modules: [
        'node_modules', PATTERNS_SRC, PATTERNS_DIST, `${ SRC }/js`
      ]
    },
    module: {
      rules: [
        {
          // JavaScript (ES6)
          test: /\.(js)$/,
          exclude: /(node_modules)/,
          loader: 'babel-loader',
          query: {
            presets: [
              ['@babel/preset-env', { targets: { ie: '11' } }]
            ]
          }
        },
        {
          // Vue Components
          test: /\.vue$/,
          loader: 'vue-loader'
        }
      ]
    },
    plugins: [
      new VueLoaderPlugin()
    ]//,
    // optimization: (NODE_ENV === 'development') ? {} : {
    //   minimizer: [
    //     new TerserPlugin({
    //       terserOptions: {
    //         extractComments: /^\**!|@preserve|@license|@cc_on/i
    //       }
    //     })
    //   ]
    // }
  }))
  .pipe(gulp.dest(`${ DIST }/js`))
);

gulp.task('scripts', gulp.series(
  'clean:scripts',
  'lint',
  'webpack'
));

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
    .pipe(gulp.dest('./'));

  callback();
});

/**
 * Images
 */
gulp.task('images', callback => {
  gulp.src([
      `${ PATTERNS_SRC }/images/**/*.jpg`,
      `${ PATTERNS_SRC }/images/**/*.png`,
      `${ PATTERNS_SRC }/images/**/*.ico`,
      `${ PATTERNS_SRC }/images/**/*.gif`
    ])
    .pipe(cache(imagemin({
      optimizationLevel: 5,
      progressive: true,
      interlaced: true
    })))
    .pipe(gulp.dest(`${ DIST }/img`))
    .pipe(notify({ message: 'Images task complete' }))

  gulp.src([
      `${ PATTERNS_SRC }/svg/**/*.svg`
    ])
    .pipe(gulp.dest(`${ DIST }/svg`))
    .pipe(notify({ message: 'Images task complete' }));

  callback();
});

/**
 * SVGs
 */
gulp.task('svgs', () =>
  gulp.src(`${ PATTERNS_SRC }/svg/*.svg`)
    .pipe(svgmin())
    .pipe(svgstore({
      inlineSvg: true
    }))
    .pipe(rename('icons.svg'))
    .pipe(gulp.dest('assets/svg/'))
    .pipe(notify({
      message: 'SVG task complete'
    }))
);

/**
 * All tasks needed to build the app
 */
gulp.task('build', gulp.parallel(
  'styles',
  'scripts',
  'svgs',
  'hashfiles'
));

/**
 * Watching Tasks
 */
gulp.task('default', () => {
  let reload = () => {
    browserSync.reload();
    notify({ message: 'Reload' });
  };

  // Create a .env file in the theme directory to define this.
  browserSync.init({
    proxy: process.env.WP_DEV_URL,
    port: 3001,
    ghostMode: {
      scroll: true
    },
    open: false
  });

  // Watch .scss files
  gulp.watch(`${ SRC }/scss/**/*.scss`,
    gulp.series(
      'clean:styles',
      'styles',
      reload
    ));

  // Watch .js files
  if (NODE_ENV === 'production') {
    gulp.watch(`${ SRC }/js/**/*.js`,
      gulp.series(
        'clean:scripts',
        'lint',
        'webpack',
        reload
      ));
  } else {
    gulp.watch(`${ SRC }/js/**/*.js`,
      gulp.series(
        'clean:scripts',
        'webpack',
        reload
      ));
  }

  // Watch image files
  gulp.watch(`${ SRC }/img/**/*`, gulp.series('images', reload));

  // Watch hashed files
  gulp.watch(HASH_FILES, gulp.series('hashfiles', reload));

  gulp.watch([
      'assets/**/*',
      'views/**/*',
      'includes/**/*'
    ], {dot: true})
      .on('change', reload);
});