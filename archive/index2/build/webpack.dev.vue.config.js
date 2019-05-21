const dev = require('./webpack.dev.config')
const custom = require('./webpack.custom.config')

const HtmlWebpackPlugin = require('html-webpack-plugin')
const VueLoaderPlugin = require('vue-loader/lib/plugin')
const webpack = require('webpack')
const merge = require('webpack-merge')
const path = require('path')

dev.entry = "./src/index.js";
dev.plugins = [
    new VueLoaderPlugin(),
    new HtmlWebpackPlugin({
        filename: 'index.html',
        template: 'index.vue.html',
        inject: true
    }),
    new webpack.NamedModulesPlugin(),
    new webpack.HotModuleReplacementPlugin(),
    new webpack.DllReferencePlugin({
        context: __dirname,
        manifest: path.join(__dirname, '../dist/dll', 'vendor-manifest.json')
    }),
];

module.exports = merge(dev, custom, {
    resolve: {
        alias: {
            'vue$': 'vue/dist/vue.esm.js',
        }
    }
});