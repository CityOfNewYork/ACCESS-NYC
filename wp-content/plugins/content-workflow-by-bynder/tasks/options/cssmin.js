module.exports = {
	options: {
		banner: '/*! <%= pkg.title %> - v<%= pkg.version %> - <%= grunt.template.today("yyyy-mm-dd") %>' +
			' | <%= pkg.homepage %>' +
			' | Copyright (c) <%= grunt.template.today("yyyy") %> <%= pkg.author.name %>' +
			' | Licensed <%= pkg.license %>' +
			' */\n'
	},
	minify: {
		expand: true,

		cwd: 'assets/css/',
		src: ['gathercontent-importer.css', 'cwby-component-disabled.css'],

		dest: 'assets/css/',
		ext: '.min.css'
	}
};
