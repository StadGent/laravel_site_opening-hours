var gulp = require('gulp');
var spawn = require('child_process').spawn;
var npm = /^win/.test(process.platform) ? 'npm.cmd' : 'npm';


gulp.task('build', function() {
  spawn(npm, ['run', 'build'], { stdio: 'inherit' });
  spawn(npm, ['run', 'sass'], { stdio: 'inherit' });
});
gulp.task('watch', function() {
  spawn(npm, ['run', 'dev'], { stdio: 'inherit' });
  spawn(npm, ['run', 'devsass'], { stdio: 'inherit' });
});

gulp.task('default', ['watch']);
gulp.task('validate', ['build']);
gulp.task('compile', ['build']);
