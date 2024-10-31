const path     = require( 'path' );
module.exports = [];

module.exports.push( {
    mode: "none",
    entry: { export_file: './js/mywpglossary_editor.jsx' },
    output: {
        path: path.join( __dirname, '../js' ),
        filename: "mywpglossary_editor.min.js",
    },
    module: {
        rules: [ {
            test: /\.(js|jsx)$/,
            exclude: /node_modules/,
            use: { loader: "babel-loader" }
        } ]
    }
} );