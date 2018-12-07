var gulp = require('gulp');
var spawn = require('child_process').spawn;
var npm = /^win/.test(process.platform) ? 'npm.cmd' : 'npm';

gulp.task('build', function (next) {
    spawn(npm, ['run', 'build'], {stdio: 'inherit'});
    spawn(npm, ['run', 'sass'], {stdio: 'inherit'});
    next();
});
gulp.task('watch', function (next) {
    spawn(npm, ['run', 'dev'], {stdio: 'inherit'});
    spawn(npm, ['run', 'devsass'], {stdio: 'inherit'});
    next();
});

gulp.task('default', gulp.series('watch'));
gulp.task('validate', gulp.series('build'));
gulp.task('compile', gulp.series('build'));
