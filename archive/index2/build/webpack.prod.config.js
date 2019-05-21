
const merge = require('webpack-merge')
const baseConfig = require('./webpack.base.config')
const CleanWebPackPlugin = require('clean-webpack-plugin')

module.exports = merge(baseConfig, {
    plugins: [
        // new CleanWebPackPlugin(["./dist/*"]),
    ],
    mode: 'production'
});