const path = require("path")
const merge = require('webpack-merge')
const webpack = require('webpack');
const baseConfig = require('./webpack.base.config')
const UglifyJSPlugin = require('uglifyjs-webpack-plugin');

module.exports = merge(baseConfig, {
    // mode: 'production',
    mode: 'development',
    plugins: [
        new webpack.NamedModulesPlugin(),
        new webpack.HotModuleReplacementPlugin(),
        new webpack.DllReferencePlugin({
            context: __dirname,
            manifest: path.join(__dirname, '../dist/dll', 'vendor-manifest.json')
        }),
    ],
    devtool: 'inline-source-map',
    devServer: {
        host: "0.0.0.0",
        port: 9510,
        contentBase: path.join(__dirname, '../dist'),
        hot: true,
        disableHostCheck: true,
    },
    optimization: {
        minimizer: [
            new UglifyJSPlugin(),
        ],
    },
});