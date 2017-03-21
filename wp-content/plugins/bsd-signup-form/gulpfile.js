// BSD Signup Form

var browserify = require('browserify'),
    gulp = require('gulp'),
    jshint = require('gulp-jshint'),
    rename = require('gulp-rename'),
    sourcestream = require('vinyl-source-stream'),
    uglify = require('gulp-uglify');

// Script Linter
gulp.task('lint', function() {
    return gulp.src('src/main.js')
        .pipe(jshint())
        .pipe(jshint.reporter('default'));
});

// Browserify
gulp.task('browserify', function() {
    return browserify('src/main.js')
        .bundle()
        .pipe(sourcestream('bsd-signup-form.dev.js'))
        .pipe(gulp.dest('./'));
});

// Minify
gulp.task('uglify', ['lint', 'browserify'], function() {
    return gulp.src('./bsd-signup-form.dev.js')
    .pipe(rename('bsd-signup-form.min.js'))
    .pipe(uglify())
    .pipe(gulp.dest('./'));
});

gulp.task('build', ['uglify']);