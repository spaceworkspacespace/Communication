const fs = require("fs")
const path = require('path')
const merge = require('webpack-merge')

const dev = require('./webpack.dev.config')
const prod = require('./webpack.prod.config')
const cus = require('./webpack.custom.config')

module.exports = merge(merge(dev, cus), {
    devServer: {
        host: "0.0.0.0",
        // host: "127.0.0.1",
        port: 10443,
        contentBase: path.join(__dirname, '../dist'),
        hot: true,
        disableHostCheck: true,
        https: {
            key: fs.readFileSync(path.join(__dirname, "2285556_im.5dx.ink.key")),
            cert: fs.readFileSync(path.join(__dirname, "2285556_im.5dx.ink.crt")),
        }
    }
});