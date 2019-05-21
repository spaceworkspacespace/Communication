const base = require('./webpack.base.config')
const custom = require('./webpack.custom.config')

const HtmlWebpackPlugin = require('html-webpack-plugin')
const VueLoaderPlugin = require('vue-loader/lib/plugin')
const webpack = require('webpack')
const merge = require('webpack-merge')
const UglifyJSPlugin = require('uglifyjs-webpack-plugin');

base.entry = "./src/index.js";
base.plugins = [
    new VueLoaderPlugin(),
    new HtmlWebpackPlugin({
        filename: 'index.vue.html',
        template: 'index.vue.html',
        inject: true
    }),
    new webpack.NamedModulesPlugin(),
];

module.exports = merge(base, custom, {
    mode: 'production',
    resolve: {
        alias: {
            'vue$': 'vue/dist/vue.esm.js',
        }
    },
    optimization: {
        minimizer: [
            new UglifyJSPlugin(),
        ],
        
    },
});