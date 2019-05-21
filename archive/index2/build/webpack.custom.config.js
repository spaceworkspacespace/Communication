
const path = require("path")

module.exports = {
    externals: {
        jquery: 'jQuery',
        layer: 'layer',
        layui: "layui",
    },
    resolve: {
        alias: {
            "@": path.resolve(__dirname, "..", "src")
        }
    }
};