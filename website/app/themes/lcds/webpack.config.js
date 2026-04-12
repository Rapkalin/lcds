const path = require('path');
const dev = process.env.NODE_ENV === "development";
const TerserPlugin = require("terser-webpack-plugin");
const MiniCssExtractPlugin = require("mini-css-extract-plugin");

let cssLoader = [
    dev ? "style-loader" : MiniCssExtractPlugin.loader,
    {
        loader: "css-loader",
        options: {
            importLoaders: 1
        }
    },
    {
        loader: "postcss-loader",
        options: {
            postcssOptions: {
                plugins: [
                    "autoprefixer",
                ],
            },
        },
    },
]

let config = {
    entry: './assets/scripts/app.js',
    output: {
        filename: 'main.js',
        path: path.resolve(__dirname, 'dist'),
    },
    watch: dev,
    mode: "development",
    devtool: dev ? "eval-cheap-module-source-map" : "hidden-source-map",
    module: {
        rules: [
            {
                test: /\.js$/i,
                exclude: /node_modules/,
                use: ['babel-loader']
            },
            {
                test: /\.css$/i,
                use: cssLoader
            },
            {
                test: /\.scss$/i,
                use: [...cssLoader,
                    {
                        loader: 'resolve-url-loader',
                    },
                    {
                        loader: "sass-loader",
                        options: {
                            implementation: require('sass'),
                            sourceMap: true
                        },
                    },
                ],
            },
        ],
    },
    optimization: {
        minimize: false,
        minimizer: [],
    },
    plugins: [
        new MiniCssExtractPlugin({
            filename: "[name].css",
        }),
    ],
    resolve: {
        modules: ['node_modules']
    },
};

if (!dev) {
    config.optimization.minimize = true;
    config.optimization.minimizer.push(new TerserPlugin());
    config.mode = 'production'
}

console.log('Webpack Config', config);

module.exports = config;