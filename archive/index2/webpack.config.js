const merge = require('webpack-merge')

const dev = require('./build/webpack.dev.config')
const prod = require('./build/webpack.prod.config')
const cus = require('./build/webpack.custom.config')

module.exports = merge(dev, cus);