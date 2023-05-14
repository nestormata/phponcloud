const path = require('path');
const MiniCssExtractPlugin = require("mini-css-extract-plugin");
const { WebpackManifestPlugin } = require('webpack-manifest-plugin');

module.exports = {
  entry: {
    app: './assets/js/app.js',
    frontpage: './assets/sass/frontpage.scss',
    post: './assets/sass/post.scss',
    notfound: './assets/sass/404.scss',
  },
  output: {
    filename: '[name]-[contenthash].js',
    path: path.resolve(__dirname, 'public/assets'),
    clean: true,
  },
  module: {
    rules: [
        {
            test: /\.s[ac]ss$/i,
            use: [
                // fallback to style-loader in development
                // process.env.NODE_ENV !== "production"
                // ? "style-loader"
                // : MiniCssExtractPlugin.loader,
                MiniCssExtractPlugin.loader,
                "css-loader",
                "sass-loader",
            ]
        }
    ]
  },
  plugins: [
    new MiniCssExtractPlugin({
      filename: "[name]-[contenthash].css",
      chunkFilename: "[id].css",
    }),
    new WebpackManifestPlugin({
        publicPath: "/assets/",
    }),
  ],
};