const gulp        = require('gulp');
const { series, parallel } = require('gulp');
const browserSync = require('browser-sync').create();
const sass        = require('gulp-sass')(require('sass'));
const prefix      = require('gulp-autoprefixer');
const concat      = require('gulp-concat');
const babel       = require('gulp-babel');
const cp          = require('child_process');
const sassGlob    = require('gulp-sass-glob');
const { reload } = require('browser-sync');

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
  return gulp.src(['_js/*.js', '_js/custom.js'])
    .pipe(babel({
      presets: ['@babel/preset-env']
    }))
    .pipe(concat('scripts.js'))
    .pipe(gulp.dest('js'))
    .pipe(browserSync.stream());
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
  gulp.watch(paths.styles.src, series(sassTask));
  gulp.watch(['_js/*.js'], scriptsTask);
  gulp.watch(['templates/*.html.twig', '**/*.yml'], gulp.series(clearcacheTask, reloadTask));
}

/**
 * Default task, running just `gulp` will 
 * compile Sass files, launch BrowserSync, watch files.
 */
exports.default = gulp.series(browserSyncTask, watchTask);
