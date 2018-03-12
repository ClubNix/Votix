var Encore = require('@symfony/webpack-encore');

Encore
    .setOutputPath('public/build/')
    .setPublicPath('/build')
    .cleanupOutputBeforeBuild()
    .enableVersioning(false)
    .addEntry('js/app', './assets/js/app.js')
;

module.exports = Encore.getWebpackConfig();