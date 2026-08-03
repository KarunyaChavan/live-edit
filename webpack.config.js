const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const path = require( 'path' );

module.exports = {
	...defaultConfig,
	entry: {
		main: path.resolve( process.cwd(), 'assets/src/js/main.js' ),
		admin: path.resolve( process.cwd(), 'assets/src/js/admin.js' ),
	},
	output: {
		filename: '[name].js',
		path: path.resolve( process.cwd(), 'assets/build/js' ),
	},
};
