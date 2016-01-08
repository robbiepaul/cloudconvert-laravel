var gulp = require('gulp'),
    phpunit = require('gulp-phpunit'),
    _       = require('lodash');

gulp.task('phpunit', function() {
    gulp.src('phpunit.xml')
        .pipe(phpunit('', {notify: true}));
});

gulp.task('default', function(){
    gulp.run('phpunit');
    gulp.watch('src/RobbieP/**/*.php', function(){
        gulp.run('phpunit');
    });
});