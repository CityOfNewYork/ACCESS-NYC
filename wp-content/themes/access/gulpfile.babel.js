'use strict';

/**
 * WordPress Starterkit Gulpfile
 * (c) Blue State Digital
 *
 * Maintained by NYC Opportunity
 *
 * Usage; Use npm scripts defined in package.json to run and manage tasks
 */


/**
 * Dependencies
 */

import 'dotenv/config';
import browserify from 'browserify';
import browserSync from 'browser-sync';
import del from 'del';
import gulp from 'gulp';
import gulpLoadPlugins from 'gulp-load-plugins';
import path from 'path';
import buffer from 'vinyl-buffer';
import sourcestream from 'vinyl-source-stream';
import es from 'event-stream';
import p from './package.json';
import envify from 'envify/custom';
import autoprefixer from 'autoprefixer';
import cssnano from 'cssnano';


/**
 * Constants
 */

const $ = gulpLoadPlugins();
const reload = function() {
  browserSync.reload();
  $.notify({ message: 'Reload' });
};

const NODE_ENV = process.env.NODE_ENV;

const DIST = 'assets';
const SRC = 'src';

const HASH_FILES = [
  'manifest-screener-field.json',
  'manifest.json'
];

const HASH_FORMAT = '{name}.{hash}{ext}';


/**
 * Functions
 */

/**
 * Error Handling
 */
function handleError() {
  this.emit('end');
}


/**
 * Styles
 */

/**
 * Build Styles
 */
gulp.task('styles', () => {
  let plugins = [
    autoprefixer('last 2 versions'),
    cssnano()
  ];
  return gulp.src([
    `${SRC}/scss/style-latin.scss`,
    `${SRC}/scss/style-*.scss`
  ]).pipe($.jsonToSass({
    jsonPath: `${SRC}/variables.json`,
    scssPath: `${SRC}/scss/_variables-json.scss`
  }))
  .pipe($.sourcemaps.init())
  .pipe($.sass({
    includePaths: ['node_modules']
      .concat(require('bourbon').includePaths)
      .concat(require('bourbon-neat').includePaths)
  })
  .on('error', $.notify.onError())
  .on('error', $.sass.logError))
  .pipe($.postcss(plugins))
  .pipe($.hashFilename({format: HASH_FORMAT}))
  .pipe($.sourcemaps.write('./'))
  .pipe(gulp.dest('./'));
});

/**
 * Clean Styles
 */
gulp.task('clean (styles)', (callback) => {
  del(['style-*.css', 'style-*.css.map'], callback);
});


/**
 * Scripts
 */

/**
 * Build Scripts
 */
gulp.task('scripts', (callback) => {
  const apps = [
    'main',
    'main-field'
  ];

  function dev(entry) {
    return browserify({
      entries: [`${SRC}/js/${entry}.js`],
      debug: true,  // must be true for sourcemaps path
      paths: ['node_modules',`${SRC}/js`]
    }).transform('babelify', {
      presets: ['es2015'],
      sourceMaps: true // must be true for sourcemaps path
    }).transform(
      {global: true},
      envify({NODE_ENV: 'development'})
    ).bundle()
    .pipe(sourcestream(`${entry}.js`))
    .pipe(buffer())
    .pipe($.hashFilename({format: HASH_FORMAT}))
    .pipe($.sourcemaps.init({loadMaps: true}))
    .pipe($.sourcemaps.write('./'))
    .pipe(gulp.dest(`${DIST}/js`));
  }

  function prod(entry) {
    return browserify({
      entries: [`${SRC}/js/${entry}.js`],
      debug: true, // must be true for sourcemaps path
      paths: ['node_modules',`${SRC}/js`]
    }).transform('babelify', {
      presets: ['es2015'],
      sourceMaps: true // must be true for sourcemaps path
    }).transform(
      {global: true},
      envify({NODE_ENV: NODE_ENV})
    ).bundle()
    .pipe(sourcestream(`${entry}.js`))
    .pipe(buffer())
    .pipe($.hashFilename({format: HASH_FORMAT}))
    .pipe($.rename({extname: '.min.js'}))
    .pipe($.sourcemaps.init({loadMaps: true}))
    .pipe($.uglify())
    .pipe($.sourcemaps.write('./', {addComment: false}))
    .pipe(gulp.dest(`${DIST}/js`));
  }

  let rundev = es.merge.apply(null, apps.map(dev));
  let runprod = es.merge.apply(null, apps.map(prod));

  return callback;
});

/**
 * Script Linter
 */
gulp.task('lint', () =>
    gulp.src(`${SRC}/js/**/*.js`)
      .pipe($.eslint({
        "parser": "babel-eslint",
        "rules": {
          "strict": 0
        }
      }
  ))
  .pipe($.eslint.format())
  .pipe($.if(!browserSync.active, $.eslint.failOnError()))
);


/**
 * Other
 */

/**
 * Clean Scripts
 */
gulp.task('clean (scripts)', (callback) => {
  del([`${DIST}/js/*`], callback);
});

/**
 * Hashing
 */
gulp.task('hashfiles', (callback) => {
  let oldhashfiles = [];

  for (let i = HASH_FILES.length - 1; i >= 0; i--) {
    oldhashfiles[i] = HASH_FILES[i].split('.').join('.*.');
  }

  del(oldhashfiles, callback);

  gulp.src(HASH_FILES)
  .pipe($.hashFilename({format: HASH_FORMAT}))
  .pipe(gulp.dest('./'));
})

/**
 * Cleaning
 */
gulp.task('clean', ['clean (scripts)', 'clean (styles)']);

/**
 * Images
 */
gulp.task('images', () => {
  return gulp.src([
    `${SRC}/img/**/*.jpg`,
    `${SRC}/img/**/*.png`,
    `${SRC}/img/**/*.gif`,
    `${SRC}/img/**/*.svg`,
    `!${SRC}/img/sprite/**/*`
  ])
  .pipe($.cache($.imagemin({
    optimizationLevel: 5,
    progressive: true,
    interlaced: true
  })))
  .pipe(gulp.dest(`${DIST}/img`))
  .pipe($.notify({ message: 'Images task complete' }));
});

/**
 * SVG Sprite
 */
gulp.task('svg-sprites', () => {
  return gulp.src(`${SRC}/img/sprite/**/*.svg`)
  .pipe($.svgmin())
  .pipe($.svgstore())
  .pipe($.rename('icons.svg'))
  .pipe(gulp.dest(`${DIST}/img`))
  .pipe($.notify({ message: 'SVG task complete' }));
});


/**
 * Tasks
 */

/**
 * Development (watch)
 */
gulp.task('default', function() {
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
  gulp.watch(`${SRC}/scss/**/*.scss`, [
    'clean (styles)',
    'styles',
    reload
  ]);

  // Watch .js files
  gulp.watch(`${SRC}/js/**/*.js`, [
    'lint',
    'clean (scripts)',
    'scripts',
    reload
  ]);

  // Watch image files
  gulp.watch(`${SRC}/img/**/*`, ['images', reload]);

  // Watch hashed files
  gulp.watch(HASH_FILES, ['hashfiles', reload]);

  gulp.watch([
    'assets/**/*',
    'views/**/*',
    'includes/**/*'
  ], {dot: true})
  .on('change', reload);
});

/**
 * Production and Pre-deploy (build)
 */
gulp.task('build', [
  'clean',
  'styles',
  'lint',
  'scripts',
  'svg-sprites',
  'hashfiles'
]);