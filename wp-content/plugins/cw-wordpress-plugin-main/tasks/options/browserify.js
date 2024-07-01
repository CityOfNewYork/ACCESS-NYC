module.exports = {
	options: {
		stripBanners: true,
		banner: '/**\n' + ' * <%= pkg.title %> - v<%= pkg.version %> - <%= grunt.template.today("yyyy-mm-dd") %>\n' + ' * <%= pkg.homepage %>\n' + ' *\n' + ' * Copyright (c) <%= grunt.template.today("yyyy") %> <%= pkg.author.name %>\n' + ' * Licensed under the <%= pkg.license %> license.\n' + ' */\n',
		transform: [
			'babelify',
			'browserify-shim'
		]
	},
	dist: {
		files: {
			'assets/js/gathercontent.js': 'assets/js/src/components/main.js',
			'assets/js/gathercontent-general.js': 'assets/js/src/components/general.js',
			'assets/js/gathercontent-single.js': 'assets/js/src/components/single.js',
			'assets/js/gathercontent-mapping.js': 'assets/js/src/components/mapping.js',
			'assets/js/gathercontent-database.js': 'assets/js/src/components/database.js',
			'assets/js/gathercontent-sync.js': 'assets/js/src/components/sync.js'
		}
	}
};
