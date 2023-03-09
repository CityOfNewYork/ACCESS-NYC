'use strict';

var importOnce = require('node-sass-import-once'),
  path = require('path');

var options = {};

options.rootPath = __dirname + '/';

options.theme = {
  root: options.rootPath,
  sass: {
    root: options.rootPath + 'src/sass/',
    library: options.rootPath + 'src/sass/_library/',
    dist: options.rootPath + 'dist/css/'
  }
};

options.sass = {
  importer: importOnce,
  includePaths: [
    options.theme.sass.library,
    options.rootPath + 'node_modules/breakpoint-sass/stylesheets'
  ],
  outputStyle: 'compressed'
};

options.autoprefixer = {
  browsers: [
    'last 2 versions',
    '> 2%'
  ]
};


options.gulpWatchOptions = {};


// Load Gulp and tools we will use.
var gulp = require('gulp'),
  $ = require('gulp-load-plugins')(),
  del = require('del'),
  sass = require('gulp-sass');

// Default task
gulp.task('default', ['build']);
gulp.task('build', ['styles']);



// Build CSS
var sassMain = [
    options.theme.sass.root + '**/*.scss',
    '!' + options.theme.sass.root + '**/_*.scss'
  ];


gulp.task('styles', ['clean:css', 'styles:main:min'], function () {
  options.sass.outputStyle = 'expanded';

  return gulp.src(sassMain)
    .pipe(sass(options.sass).on('error', sass.logError))
    .pipe($.autoprefixer(options.autoprefixer))
    .pipe($.size({showFiles: true}))
    .pipe(gulp.dest(options.theme.sass.dist));
});

gulp.task('styles:main:min', function () {
  options.sass.outputStyle = 'compressed';

  return gulp.src(sassMain)
    .pipe($.sourcemaps.init())
    .pipe(sass(options.sass).on('error', sass.logError))
    .pipe($.autoprefixer(options.autoprefixer))
    .pipe($.cleanCss({level: 2}))
    .pipe($.size({showFiles: true}))
    .pipe($.rename({
      suffix: '.min'
    }))
    .pipe($.sourcemaps.write('./'))
    .pipe(gulp.dest(options.theme.sass.dist));
});



// Clean all directories.
gulp.task('clean', ['clean:css']);

// Clean CSS files.
gulp.task('clean:css', function () {
  return del([
    options.theme.sass.dist + '**/*.css',
    options.theme.sass.dist + '**/*.map'
  ], {force: true});
});


// Watch for changes and rebuild.
gulp.task('watch', ['watch:css'], function () {
  $.livereload.listen();
});

gulp.task('watch:css', ['styles'], function () {
  return gulp.watch(options.theme.sass.root + '**/*.scss', options.gulpWatchOptions, ['styles']);
});
