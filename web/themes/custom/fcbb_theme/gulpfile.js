const gulp        = require('gulp');
const { series, parallel } = require('gulp');
const browserSync = require('browser-sync').create();
const sass        = require('gulp-sass')(require('sass'));
const prefix      = require('gulp-autoprefixer');
const rename      = require('gulp-rename');
const cleanCSS    = require('gulp-clean-css');
const cp          = require('child_process');
const sassGlob    = require('gulp-sass-glob');
const { reload } = require('browser-sync');
const webpack     = require('webpack-stream');
const cfg         = require('./webpack.config');

const paths = {
  styles: {
    src: 'scss/**/*.scss',
    dest: 'css',
  },
  scripts: {
    src: 'src/**/*.js',
    dest: 'js',
  },
}

/**
 * Launch the Server
 */
function browserSyncTask(done) {
  browserSync.init({
    proxy: "fcbb-site.ddev.site",
    port: 3000
  });
  done();
}

/**
 * Compile files from scss
 */
function sassTask() {
  return gulp.src(paths.styles.src)
    .pipe(sassGlob())
    .pipe(sass.sync().on('error', sass.logError))
    .pipe(prefix())
    .pipe(gulp.dest(paths.styles.dest))
    .pipe(browserSync.stream());
}

/**
 * Compile files from js
 */
function scriptsTask() {
  return gulp.src(paths.scripts.src)
    .pipe(webpack(cfg))
    .pipe(gulp.dest(paths.scripts.dest))
    .pipe(browserSync.stream());
}

function minifyCss() {
  return gulp
    .src(['css/**/*.css', '!css/*.min.css', '!css/**/*.min.css'])
    .pipe(
      rename({
        suffix: '.min',
      }),
    )
    .pipe(cleanCSS({ specialComments: 0 }))
    .pipe(gulp.dest('css'));
}

/**
 * Clear all caches
 */
function clearcacheTask(done) {
  return cp.spawn('drush', ['cache-rebuild'], { stdio: 'inherit' })
    .on('close', done);
}

/**
 * Refresh the page after clearing cache
 */
function reloadTask(done) {
  browserSync.reload();
  done();
}

/**
 * Watch scss files for changes & recompile
 * Clear cache when Drupal related files are changed
 */
function watchTask() {
  gulp.watch(paths.styles.src, series(sassTask, minifyCss));
  gulp.watch(paths.scripts.src, scriptsTask);
  gulp.watch(['templates/*.html.twig', '**/*.yml'], gulp.series(clearcacheTask, reloadTask));
}


exports.default = series(
  sassTask,
  minifyCss,
  scriptsTask
  );

exports.build = series(
  sassTask,
  minifyCss,
  scriptsTask
);

exports.watch = parallel(watchTask, browserSyncTask);
