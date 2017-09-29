// WordPress Starterkit Gulpfile
// (c) Blue State Digital

// TASKS
// ------
// `gulp`: watch, compile styles and scripts; Browserify
// `gulp build`: default compile task

'use strict';

// PLUGINS
// --------
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

const $ = gulpLoadPlugins();
const reload = function() {
  browserSync.reload();
  $.notify({ message: 'Reload' });
};

// VARIABLES
// ----------
const dist = 'assets';
const appRoot = '/wp-content/themes/access/assets';
const src = 'src';


// ERROR HANDLING
// ---------------
function handleError() {
  this.emit('end');
}

// BUILD SUBTASKS
// ---------------
// Styles
gulp.task('styles_dev', () => {
  return gulp.src([
    `${src}/scss/style.scss`,
    `${src}/scss/style-*.scss`
  ])
  .pipe($.sourcemaps.init())
  .pipe($.sass({
    includePaths: ['node_modules']
      .concat(require('bourbon').includePaths)
      .concat(require('bourbon-neat').includePaths)
  })
  .on('error', $.notify.onError())
  .on('error', $.sass.logError))
  .pipe($.autoprefixer('last 2 versions'))
  .pipe($.sourcemaps.write('./'))
  .pipe(gulp.dest('./'))
});

gulp.task('styles', () => {
  return gulp.src([
    `${src}/scss/style.scss`,
    `${src}/scss/style-*.scss`
  ]).pipe($.jsonToSass({
    jsonPath: `${src}/variables.json`,
    scssPath: `${src}/_variables.json.scss`
  }))
  .pipe($.sourcemaps.init())
  .pipe($.sass({
    includePaths: ['node_modules']
      .concat(require('bourbon').includePaths)
      .concat(require('bourbon-neat').includePaths)
  }).on('error', $.notify.onError()).on('error', $.sass.logError))
  .pipe($.autoprefixer())
  .pipe($.cleanCss())
  .pipe($.sourcemaps.write('./'))
  .pipe(gulp.dest('./'));
});


// Script Linter
gulp.task('lint', () =>
  gulp.src(`${src}/js/**/*.js`)
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


// Scripts
gulp.task('scripts', () => {
 const apps = [
    'main', 'main.ssp'
  ];
  let tasks = apps.map(function(entry) {
    const b = browserify({
      entries: [`${src}/js/${entry}.js`],
      debug: true,
      paths: ['node_modules',`${src}/js`]
    });
    return b.transform('babelify', {
      presets: ['es2015']
    })
    .bundle()
    .pipe(sourcestream(`${entry}.js`))
    .pipe(buffer())
    .pipe(gulp.dest(`${dist}/js`))
    .pipe($.sourcemaps.init({loadMaps: true}))
    .pipe($.uglify())
    .pipe($.sourcemaps.write('./'))
    .pipe($.rename(`${entry}.min.js`))
    .pipe(gulp.dest(`${dist}/js`));
  });
  return es.merge.apply(null, tasks);
});


// Clean
gulp.task('clean', (callback) => {
  del([`${dist}/js/*`], callback);
});


// Images
gulp.task('images', () => {
  return gulp.src([
    `${src}/img/**/*.jpg`,
    `${src}/img/**/*.png`,
    `${src}/img/**/*.gif`,
    `${src}/img/**/*.svg`,
    `!${src}/img/sprite/**/*`

  ])
  .pipe($.cache($.imagemin({
    optimizationLevel: 5,
    progressive: true,
    interlaced: true
  })))
  .pipe(gulp.dest(`${dist}/img`))
  .pipe($.notify({ message: 'Images task complete' }));
});


// SVG Sprite
gulp.task('svg-sprites', () => {
  return gulp.src(`${src}/img/sprite/**/*.svg`)
  .pipe($.svgmin())
  .pipe($.svgstore())
  .pipe($.rename('icons.svg'))
  .pipe(gulp.dest(`${dist}/img`))
  .pipe($.notify({ message: 'SVG task complete' }));
});


// BUILD TASKS
// ------------

// Watch
gulp.task('default', ['build'], function() {

  browserSync.init({
    // Create a .env file in the theme directory to define this.
    proxy: process.env.WP_DEV_URL,
    port:3001,
    ghostMode: {
      scroll: true
    },
    open:true
  });

  // Watch .scss files
  gulp.watch(`${src}/scss/**/*.scss`, ['styles_dev', reload]);

  // Watch .js files
  gulp.watch(`${src}/js/**/*.js`, ['lint', 'scripts', reload]);

  // Watch image files
  gulp.watch(`${src}/img/**/*`, ['images', reload]);

  gulp.watch([
    'access/**/*',
    'views/**/*',
  ], { dot: true })
  .on('change', reload);

});

// Build
gulp.task('build', ['clean'], () => {
  gulp.start('styles', 'lint', 'scripts', 'svg-sprites');
});
