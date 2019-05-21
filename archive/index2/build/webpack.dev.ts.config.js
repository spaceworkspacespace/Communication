const merge = require('webpack-merge')

const dev = require('./webpack.dev.config')
const prod = require('./webpack.prod.config')
const cus = require('./webpack.custom.config')

module.exports = merge(dev, cus);