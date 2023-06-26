'use strict';

var del = require('del');
var gulp = require('gulp');
var cleanCSS = require('gulp-clean-css');
var concat = require('gulp-concat');
var execSync = require('child_process').execSync;
var less = require('gulp-less');
var rename = require('gulp-rename');
var sourcemaps = require('gulp-sourcemaps');
var uglify = require('gulp-uglify');
var shell = require('gulp-shell');
var config = require('./resources.json');

function clean(done) {
    return del([
        config.less.dest + "*.css",
        config.js.dest + "*.js"
    ], done);
}
clean.description = 'Clean output files.';

function styles() {
    return gulp.src(config.less.main.src)
        .pipe(sourcemaps.init())
        .pipe(less({ relativeUrls: true, paths: [ config.less.main.root ] }))
        .pipe(cleanCSS({ rebase: true, rebaseTo: config.less.dest }))
        .pipe(sourcemaps.write())
        .pipe(gulp.dest(config.less.dest));
}
styles.description = 'Run less to generate stylesheets.';

function stylesInstaller() {
    return gulp.src(config.less.installer.src)
        .pipe(sourcemaps.init())
        .pipe(less({ relativeUrls: true, paths: [ config.less.installer.root ] }))
        .pipe(cleanCSS({ rebase: true, rebaseTo: config.less.dest }))
        .pipe(sourcemaps.write())
        .pipe(gulp.dest(config.less.dest));
}
stylesInstaller.description = 'Run less to generate stylesheets for the installer.';

function scripts() {
    return gulp.src(config.js.src)
        .pipe(sourcemaps.init())
        .pipe(concat('scripts.min.js'))
        .pipe(gulp.dest(config.js.dest))
        .pipe(uglify())
        .pipe(sourcemaps.write('.'))
        .pipe(gulp.dest(config.js.dest));
}
scripts.description = 'Combine and minify javascript files.';

function translate() {
    var poDomain = 'runalyze';
    execSync(`rm -rf translations/gettext/ && mkdir translations/gettext`);
    return gulp.src(`./translations/${poDomain}.*.po`)
        .pipe(shell([
            'mkdir translations/gettext/<%= extract_locale(file.path) %>',
            'mkdir translations/gettext/<%= extract_locale(file.path) %>/LC_MESSAGES',
            `msgfmt -v <%= file.path %> -o translations/gettext/<%= extract_locale(file.path) %>/LC_MESSAGES/${poDomain}.mo`
        ], {
            templateData: { 
                extract_locale: (f) => f.replace(new RegExp(`^(?:.*\/)?${poDomain}\\.([a-z]{2}_[A-Z]{2})\\.po$`), "$1")
            },
            verbose: true
        }));
}
translate.description = 'Compile translation files.';

function extractTranslations() {
    var poDomain = 'runalyze';
    var poTemplatePath = `translations/${poDomain}.pot`;
    execSync(`rm -f ${poTemplatePath} && touch ${poTemplatePath}`);
    execSync(`find inc -name "*.php" -print0 | xargs -0 xgettext -o ${poTemplatePath} --join-existing --keyword=__ --keyword=_e --keyword=_n --from-code=utf-8`);
    return gulp.src(`./translations/${poDomain}.*.po`)
        .pipe(shell([
            `msgmerge --backup=none -U <%= file.path %> ${poTemplatePath}`
        ], { verbose: true }));
}
extractTranslations.description = 'Extract translatable strings from source files and update translation tables.';

exports.clean = clean;
exports.styles = styles;
exports.stylesInstaller = stylesInstaller;
exports.scripts = scripts;
exports.translate = translate;
exports.extractTranslations = extractTranslations;

var build = gulp.series(clean, gulp.parallel(styles, stylesInstaller, scripts));

gulp.task('build', build);
gulp.task('default', gulp.parallel(build, translate));
gulp.task('updateTranslations', gulp.series(extractTranslations, translate));
