module.exports = {
	options: {
		stripBanners: true,
		banner: '/*! <%= pkg.title %> - v<%= pkg.version %>\n' +
			' * <%= pkg.homepage %>\n' +
			' * Copyright (c) <%= grunt.template.today("yyyy") %>;' +
			' * Licensed GPL-2.0+' +
			' */\n'
	},
	main: {
		src: [
			'assets/js/src/gathercontent.js'
		],
		dest: 'assets/js/gathercontent.js'
	}
};
