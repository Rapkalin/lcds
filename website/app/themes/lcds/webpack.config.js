const fs = require('fs');
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

// tarteaucitron charge ces fichiers depuis le dossier de son script, et
// node_modules ne part pas au déploiement : ils sont recopiés dans dist/.
const CONSENT_MANAGER_FILES = [
    'tarteaucitron.min.js',
    'tarteaucitron.services.min.js',
    'lang/tarteaucitron.fr.min.js',
    'css/tarteaucitron.min.css',
    'LICENSE',
];

class CopyConsentManager {
    apply(compiler) {
        compiler.hooks.afterEmit.tap('CopyConsentManager', () => {
            const source = path.dirname(require.resolve('tarteaucitronjs/package.json'));
            const target = path.resolve(__dirname, 'dist/tarteaucitron');

            for (const file of CONSENT_MANAGER_FILES) {
                fs.mkdirSync(path.dirname(path.join(target, file)), { recursive: true });
                fs.copyFileSync(path.join(source, file), path.join(target, file));
            }
        });
    }
}

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
        new CopyConsentManager(),
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