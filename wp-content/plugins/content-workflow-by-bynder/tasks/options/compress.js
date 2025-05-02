module.exports = {
	main: {
		options: {
			mode: 'zip',
			archive: 'gathercontent-importer.zip'
		},
		expand: true,
		src: [
			'**',
			'!**/**.xml',
			'!**/**.DS_Store',
			'!**/phploy.ini',
			'!**/Dockunit.json',
			'!**/package.json',
			'!**/node_modules/**',
			'!**/.git/**',
			'!**/bin/**',
			'!**/.svn/**',
			'!**/tests/**',
			'!**/sass/**',
			'!**.zip',
			'!**.gitignore',
			'!**.jshintrc',
			'!**.log',
			'!**.dist',
			'!**/**.orig',
			'!**/**.map',
			'!**/**Gruntfile.js',
			'!**/**composer.lock',
			'!**/**bower.json',
			'!**/**bower.json',
			'!assets/js/src/**',
			'!release/**',
			'!tasks/**'
		],
		dest: 'gathercontent-importer/'
	}
};
