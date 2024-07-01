const sass = require('node-sass');

module.exports = {
	all: {
		options: {
			implementation: sass,
			precision: 2,
			sourceMap: true
		},
		files: {
			'assets/css/gathercontent-importer.css': 'assets/css/sass/gathercontent-importer.scss'
		}
	}
};
