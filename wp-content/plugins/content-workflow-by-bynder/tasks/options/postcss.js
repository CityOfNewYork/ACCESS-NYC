module.exports = {
	dist: {
		options: {
			processors: [
				require('autoprefixer')({browsers: 'last 2 versions'})
			]
		},
		files: {
			'assets/css/gathercontent-importer.css': ['assets/css/gathercontent-importer.css']
		}
	}
};
