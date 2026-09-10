const path = require('path');

/**
 * Custom webpack config for Angular build.
 * Handles Quill's SVG icon requires and qrcode browser build resolution.
 */
module.exports = {
  resolve: {
    modules: [path.resolve(__dirname, 'node_modules'), 'node_modules'],
    alias: {
      'qrcode': path.resolve(__dirname, 'node_modules/qrcode/lib/browser.js')
    }
  },
  module: {
    rules: [
      {
        test: /[\\/]node_modules[\\/]quill[\\/].*\.svg$/,
        use: 'raw-loader'
      }
    ]
  }
};
