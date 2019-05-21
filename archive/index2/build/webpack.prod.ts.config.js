
const merge = require('webpack-merge')
const baseConfig = require('./webpack.base.config')
const CleanWebPackPlugin = require('clean-webpack-plugin')
const cus = require('./webpack.custom.config')
const UglifyJSPlugin = require('uglifyjs-webpack-plugin');

module.exports = merge(baseConfig, cus, {
    plugins: [
        // new CleanWebPackPlugin(["./dist/*"]),
    ],
    mode: 'production',
    optimization: {
        minimizer: [
            new UglifyJSPlugin(),
        ],
        
    },
});