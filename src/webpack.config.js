const path = require('path')
const webpack = require('webpack')
const CleanWebpackPlugin = require('clean-webpack-plugin')
const CompressionPlugin = require('compression-webpack-plugin')
const CopyPlugin = require('copy-webpack-plugin')
const HtmlWebpackPlugin = require('html-webpack-plugin')
const VueLoaderPlugin = require('vue-loader/lib/plugin')
const WorkboxPlugin = require('workbox-webpack-plugin')

module.exports = {
    entry: './resources/js/app.js',
    output: {
        chunkFilename: '[name].[contenthash].bundle.js',
        path: path.join(__dirname, 'build'),
        publicPath: '/',
        filename: '[name].[contenthash].bundle.js'
    },
    module: {
        rules: [{
            test: /\.scss$/,
            use: [
                'style-loader',
                'css-loader',
                'sass-loader'
            ]
        },{
            test: /\.css$/,
            use: [
                'vue-style-loader',
                'css-loader'
            ]
        },{
            test: /\.vue$/,
            loader: 'vue-loader'
        },{
            test: /\.woff(2)?(\?v=[0-9]\.[0-9]\.[0-9])?$/,
            loader: "url-loader?limit=10000&mimetype=application/font-woff"
        },{
            test: /\.(ttf|eot|svg)(\?v=[0-9]\.[0-9]\.[0-9])?$/,
            loader: "file-loader"
        }]
    },
    plugins: [
        new CleanWebpackPlugin(),
        // new CompressionPlugin(),
        new HtmlWebpackPlugin({
            inject: 'body',
            template: 'resources/index.html',
            title: 'Lowdown'
        }),
        new VueLoaderPlugin(),
        new webpack.HashedModuleIdsPlugin(),
        new CopyPlugin([
            { from: 'static', dest: 'build' }
        ]),
        new WorkboxPlugin.GenerateSW({
            navigateFallback: '/index.html',
            swDest: 'sw.js'
        })
    ],
    optimization: {
        runtimeChunk: 'single',
        splitChunks: {
            cacheGroups: {
                vendor: {
                    test: /[\\/]node_modules[\\/]/,
                    name: 'vendors',
                    chunks: 'all'
                }
            }
        }
    },
    stats: {
        maxModules: Infinity,
        optimizationBailout: true
    },
    devServer: {
        contentBase: path.join(__dirname, 'build'),
        historyApiFallback: true
    }
}
