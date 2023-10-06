// webpack.config.js
const path = require('path')
const glob = require('glob')
const context = path.resolve(__dirname, './src')

module.exports = {
  context,
  entry: glob.sync('./src/**/*.bundle.js').reduce((acc, item) => {
    // get the relative path
    const relativePath = path.relative(context, item)
    // remove the .bundle.js extension
    const entry = relativePath.replace(/\.bundle\.js$/, '')
    // add the entry to the acc
    acc[entry] = item.replace('src/', './')
    return acc
  }, {}),
  output: {
    path: path.resolve(__dirname, './js'),
    filename: '[name].min.js',
  },
  mode: 'production',
  // ...
  module: {
    rules: [
      {
        test: /\.js$/,
        exclude: [/node_modules/],
        use: [
          {
            loader: 'babel-loader',
            options: { presets: ['@babel/env'] },
          },
        ],
      },
      {
        test: /\.scss$/,
        use: ['css-loader', 'sass-loader'],
      },
      {
        test: /\.css$/,
        use: ['css-loader'],
      },
    ],
  },
  plugins: [],
  externals: {
    // jquery: 'jQuery $',
    Drupal: 'Drupal',
    drupalSettings: 'drupalSettings',
    // Swiper: 'Swiper',
  },
  resolve: {
    extensions: ['.js', '.json'],
    alias: {},
  },
}
