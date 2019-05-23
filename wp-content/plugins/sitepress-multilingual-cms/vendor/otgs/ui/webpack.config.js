const path                  = require('path');
const ExtractTextPlugin     = require('extract-text-webpack-plugin');
const WebpackAssetsManifest = require('webpack-assets-manifest');
const CleanWebpackPlugin    = require('clean-webpack-plugin');

/**
 * @typedef LibrariesHash
 * @property {object} [entryChunkName: string] A unique entry ID
 * @property {array} entryChunkName.entry - A list of entries to bundle with the library
 * @property {string} [entryChunkName.filename] - The target file name (if omitted, the entry entryChunkName will be used
 * @property {string} [entryChunkName.var] - The name of the global variable to which the library will be assigned
 *
 * @type LibrariesHash
 */
const libraries = {
	'otgsSwitcher':          {
		entry:    ['./src/js/otgsSwitcher.js'],
	},
	'otgsPopoverTooltip':    {
		entry:    ['./src/js/otgsPopoverTooltip.js'],
	},
	'otgsTableStickyHeader': {
		entry:    ['./src/js/otgsTableStickyHeader.js'],
	},
};

const getEntries       = () => {
	const entries = {};
	Object.keys(libraries).map(key => entries[key] = libraries[key].entry);

	return entries;
};

const getEntryFileName = (chunk) => {
	if (libraries.hasOwnProperty(chunk.id) && libraries[chunk.id].hasOwnProperty('filename') && libraries[chunk.id].filename) {
		return libraries[chunk.id].filename;
	}
	return path.join(chunk.name);
};

module.exports = env => {
	const isProduction = env === 'production';

	console.log('getEntries()', getEntries());
	// console.log('getVars()',getVars());

	return {
		entry: getEntries,
		output: {
			path:          path.join(__dirname, 'dist'),
			filename:      chunkData => path.join('js', getEntryFileName(chunkData.chunk) + '.js?ver=' + chunkData.chunk.hash),
			chunkFilename: '[id].[name].js?ver=[chunkhash]',
			library:       ["OTGSUI", "[name]"],
			libraryTarget: 'var'
		},
		module: {
			rules: [
				{
					loader: 'babel-loader',
					test: /\.js$/,
					exclude: /node_modules/,
					query: {
						presets: ['es2015'],
					},
				},
				{
					test: /\.s?css$/,
					use: ExtractTextPlugin.extract({
						fallback: 'style-loader',
						use: [
							{
								loader: 'css-loader',
								options: {
									sourceMap: !isProduction,
									minimize: isProduction,
								},
							},
							{
								loader: 'sass-loader',
								options: {
									sourceMap: !isProduction,
								},
							},
							{
								loader: 'postcss-loader',
							},
						],
					}),
				},
			],
		},
		plugins: [
			new CleanWebpackPlugin(['dist']),
			new ExtractTextPlugin({
				filename: path.join('css', '[name].css?ver=[chunkhash]'),
			}),
			new WebpackAssetsManifest({
				output: path.join(__dirname, 'dist', 'assets.json'),
				entrypoints: true,
			}),
		],
		devtool: isProduction ? '' : 'inline-source-map'
	};
};
