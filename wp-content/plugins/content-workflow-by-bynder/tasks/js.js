module.exports = function (grunt) {
	grunt.registerTask('js', ['jshint', 'browserify', 'uglify']);
};
