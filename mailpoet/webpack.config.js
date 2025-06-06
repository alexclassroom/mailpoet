const webpack = require('webpack');
const { WebpackManifestPlugin } = require('webpack-manifest-plugin');
const WebpackTerserPlugin = require('terser-webpack-plugin');
const WebpackCopyPlugin = require('copy-webpack-plugin');
const path = require('path');
const fs = require('fs');
const wpScriptConfig = require('@wordpress/scripts/config/webpack.config');
const DependencyExtractionWebpackPlugin = require('@wordpress/dependency-extraction-webpack-plugin');
const ForkTsCheckerWebpackPlugin = require('fork-ts-checker-webpack-plugin');

const globalPrefix = 'MailPoetLib';
const PRODUCTION_ENV = process.env.NODE_ENV === 'production';
const manifestSeed = {};

const stats = {
  preset: 'minimal',
  assets: false,
  modules: false,
  chunks: true,
};

// A custom plugin to copy a file without Webpack processing it in
// production mode. This is needed for the happiness-assistant-widget.js
// because it would occasionally fail to process in CircleCI.
class DirectCopyPlugin {
  constructor(options) {
    this.options = options;
  }

  apply(compiler) {
    compiler.hooks.afterEmit.tapAsync(
      'DirectCopyPlugin',
      (_compilation, callback) => {
        const sourcePath = path.resolve(__dirname, this.options.from);
        const targetPath = path.resolve(__dirname, this.options.to);

        // Ensure target directory exists
        const targetDir = path.dirname(targetPath);
        if (!fs.existsSync(targetDir)) {
          fs.mkdirSync(targetDir, { recursive: true });
        }

        // Copy file directly
        fs.copyFileSync(sourcePath, targetPath);
        callback();
      },
    );
  }
}

// Base config
const baseConfig = {
  stats,
  ignoreWarnings: [
    (warnings) => {
      // Todo: remove this if statement per MAILPOET-4544
      if (
        warnings &&
        [
          'AssetsOverSizeLimitWarning',
          'EntrypointsOverSizeLimitWarning',
          'NoAsyncChunksWarning',
        ].includes(warnings.name)
      ) {
        return true;
      }

      // only show warnings when watching
      if (process.env.WEBPACK_WATCH || process.argv.includes('--watch')) {
        return false;
      }

      if (warnings) {
        // eslint-disable-next-line
        console.warn(warnings);
        process.emitWarning(warnings); // emit for listeners
        process.exit(1);
      }

      return false;
    },
  ],
  mode: PRODUCTION_ENV ? 'production' : 'development',
  devtool: PRODUCTION_ENV ? undefined : 'eval-source-map',
  cache: true,
  bail: PRODUCTION_ENV,
  context: __dirname,
  watchOptions: {
    aggregateTimeout: 300,
    poll: true,
  },
  optimization: {
    minimizer: [
      new WebpackTerserPlugin({
        terserOptions: {
          // preserve identifier names for easier debugging & support
          mangle: false,
        },
        parallel: false,
      }),
    ],
  },
  output: {
    publicPath: '', // This is needed to have correct names in WebpackManifestPlugin
    path: path.join(__dirname, 'assets/dist/js'),
    filename: '[name].js',
    chunkFilename: '[name].chunk.js',
  },
  resolve: {
    modules: ['node_modules', 'assets/js/src'],
    fallback: {
      fs: false,
      path: false, // path is used in css module, but we don't use the functionality which requires it
    },
    extensions: ['.js', '.jsx', '.ts', '.tsx'],
    alias: {
      handlebars: 'handlebars/dist/handlebars.js',
      'backbone.marionette': 'backbone.marionette/lib/backbone.marionette',
      'backbone.supermodel$':
        'backbone.supermodel/build/backbone.supermodel.js',
      'sticky-kit': 'vendor/jquery.sticky-kit.js',
      interact$: 'interact.js/interact.js',
      spectrum$: 'spectrum-colorpicker/spectrum.js',
      'wp-js-hooks': path.resolve(__dirname, 'assets/js/src/hooks.js'),
      blob$: 'blob-tmp/Blob.js',
      chai: 'chai/index.js',
      papaparse: 'papaparse/papaparse.min.js',
      html2canvas: 'html2canvas/dist/html2canvas.js',
      asyncqueue: 'vendor/jquery.asyncqueue.js',
      '@woocommerce/settings': path.resolve(
        __dirname,
        'assets/js/src/mock-woocommerce-settings.ts',
      ),
      '@automattic/tour-kit': path.resolve(
        __dirname,
        'assets/js/src/mock-empty-module.js',
      ),
    },
  },
  plugins: PRODUCTION_ENV ? [] : [new ForkTsCheckerWebpackPlugin()],
  module: {
    noParse: /node_modules\/lodash\/lodash\.js/,
    rules: [
      {
        test: /\.(j|t)sx?$/,
        exclude: /(node_modules|src\/vendor)/,
        loader: 'babel-loader',
        resolve: {
          fullySpecified: false,
        },
      },
      {
        include: path.resolve(
          __dirname,
          'assets/js/src/webpack-admin-expose.js',
        ),
        loader: 'expose-loader',
        options: { exposes: globalPrefix },
      },
      {
        include: require.resolve('underscore'),
        loader: 'expose-loader',
        options: {
          exposes: '_',
        },
      },
      {
        include: /Blob.js$/,
        loader: 'exports-loader',
        options: {
          exports: 'default window.Blob',
        },
      },
      {
        test: /backbone.supermodel/,
        loader: 'exports-loader',
        options: {
          exports: 'default Backbone.SuperModel',
        },
      },
      {
        include: require.resolve('velocity-animate'),
        loader: 'imports-loader',
        options: {
          imports: {
            name: 'jQuery',
            moduleName: 'jquery',
          },
        },
      },
      {
        test: /node_modules\/tinymce/,
        loader: 'string-replace-loader',
        options: {
          // prefix TinyMCE to avoid conflicts with other plugins
          multiple: [
            {
              search: 'window\\.tinymce',
              replace: 'window.mailpoetTinymce',
              flags: 'g',
            },
            {
              search: 'window\\.tinyMCE',
              replace: 'window.mailpoetTinyMCE',
              flags: 'g',
            },
            {
              search: 'tinymce\\.util',
              replace: 'window.mailpoetTinymce.util',
              flags: 'g',
            },
            {
              search: "resolve\\('tinymce",
              replace: "resolve('mailpoetTinymce",
              flags: 'g',
            },
            {
              search: 'tinymce.Resource',
              replace: 'mailpoetTinymce.Resource',
              flags: 'g',
            },
          ],
        },
      },
    ],
  },
};

// Admin config
const adminConfig = {
  name: 'admin',
  entry: {
    vendor: 'webpack-vendor-index.jsx',
    mailpoet: 'webpack-mailpoet-index.jsx',
    admin_vendor: ['prop-types', 'lodash', 'webpack-admin-expose.js'], // libraries shared between free and premium plugin
    admin: 'webpack-admin-index.tsx',
    automation: 'automation/automation.tsx',
    automation_editor: 'automation/editor/index.tsx',
    automation_analytics:
      'automation/integrations/mailpoet/analytics/index.tsx',
    automation_templates: 'automation/templates/index.tsx',
    newsletter_editor: 'newsletter-editor/webpack-index.jsx',
    form_editor: 'form-editor/form-editor.jsx',
    settings: 'settings/index.tsx',
  },
  plugins: [
    ...baseConfig.plugins,

    new WebpackCopyPlugin({
      patterns: [
        {
          from: 'node_modules/tinymce/skins/ui/oxide',
          to: 'skins/ui/oxide',
        },
      ],
    }),
    new DirectCopyPlugin({
      from: 'assets/js/src/vendor/happiness-assistant-widget.js',
      to: 'assets/dist/js/haw.js',
    }),
  ],
  optimization: {
    runtimeChunk: 'single',
    splitChunks: {
      cacheGroups: {
        commons: {
          name: 'commons',
          chunks: 'initial',
          minChunks: 2,
        },
      },
    },
  },
  externals: {
    jquery: 'jQuery',
  },
};

// Public config
const publicConfig = {
  name: 'public',
  entry: {
    public: 'webpack-public-index.jsx',
  },
  plugins: [
    ...baseConfig.plugins,

    // replace MailPoet definition with a smaller version for public
    new webpack.NormalModuleReplacementPlugin(
      /mailpoet\.ts/,
      './mailpoet-public.ts',
    ),
  ],
  externals: {
    jquery: 'jQuery',
  },
};

// Newsletter Editor Tests Config
const testConfig = {
  name: 'test',
  mode: PRODUCTION_ENV ? 'production' : 'development', // Add mode directly to testConfig
  entry: {
    vendor: 'webpack-vendor-index.jsx',
    testNewsletterEditor: [
      'webpack-mailpoet-index.jsx',
      'newsletter-editor/webpack-index.jsx',

      'components/config.spec.js',
      'components/content.spec.js',
      'components/heading.spec.js',
      'components/history.spec.js',
      'components/save.spec.js',
      'components/sidebar.spec.js',
      'components/styles.spec.js',
      'components/communication.spec.js',

      'blocks/automated-latest-content-layout.spec.js',
      'blocks/button.spec.js',
      'blocks/container.spec.js',
      'blocks/coupon.spec.js',
      'blocks/divider.spec.js',
      'blocks/dynamic-products.spec.js',
      'blocks/footer.spec.js',
      'blocks/header.spec.js',
      'blocks/image.spec.js',
      'blocks/posts.spec.js',
      'blocks/products.spec.js',
      'blocks/social.spec.js',
      'blocks/spacer.spec.js',
      'blocks/text.spec.js',
    ],
  },
  module: {
    ...baseConfig.module,
    rules: [
      {
        test: /\.(j|t)sx?$/,
        exclude: /(node_modules|src\/vendor)/,
        loader: 'babel-loader',
        resolve: { fullySpecified: false },
        options: {
          presets: [['@babel/preset-env', { modules: 'commonjs' }]],
        },
      },
      ...baseConfig.module.rules,
    ],
  },
  output: {
    path: path.join(
      __dirname,
      'tests/javascript-newsletter-editor/testBundles',
    ),
    filename: '[name].js',
  },
  plugins: [
    // replace MailPoet definition with a smaller version for public
    new webpack.NormalModuleReplacementPlugin(
      /mailpoet\.js/,
      './mailpoet-tests.js',
    ),
  ],
  resolve: {
    modules: [
      'node_modules',
      'assets/js/src',
      'tests/javascript-newsletter-editor/newsletter-editor',
    ],
    extensions: ['.js', '.jsx', '.ts', '.tsx'],
    alias: {
      handlebars: 'handlebars/dist/handlebars.js',
      'sticky-kit': 'vendor/jquery.sticky-kit.js',
      'backbone.marionette': 'backbone.marionette/lib/backbone.marionette',
      'backbone.supermodel$':
        'backbone.supermodel/build/backbone.supermodel.js',
      blob$: 'blob-tmp/Blob.js',
      'wp-js-hooks': path.resolve(__dirname, 'assets/js/src/hooks.js'),
    },
    fallback: {
      fs: false,
    },
  },
  externals: {
    jquery: 'jQuery',
    interact: 'interact',
    spectrum: 'spectrum',
  },
};

// Form preview config
const formPreviewConfig = {
  name: 'form_preview',
  entry: {
    form_preview: 'form-editor/form-preview.ts',
  },
  externals: {
    jquery: 'jQuery',
  },
};

// Block config
const postEditorBlock = {
  name: 'post_editor_block',
  entry: {
    post_editor_block: 'post-editor-block/blocks.jsx',
  },
};

// Marketing Optin config
function requestToExternal(request) {
  const wcDepMap = {
    '@woocommerce/settings': ['wc', 'wcSettings'],
    '@woocommerce/blocks-checkout': ['wc', 'blocksCheckout'],
  };
  if (wcDepMap[request]) {
    return wcDepMap[request];
  }
  // DependencyExtractionWebpackPlugin has native handling for @wordpress/*
  // packages, for that handling to kick in, we must not return anything from
  // function.
  /* eslint-disable-next-line consistent-return, no-useless-return */
  return;
}

function requestToHandle(request) {
  const wcHandleMap = {
    '@woocommerce/settings': 'wc-settings',
    '@woocommerce/blocks-checkout': 'wc-blocks-checkout',
  };
  if (wcHandleMap[request]) {
    return wcHandleMap[request];
  }
  // DependencyExtractionWebpackPlugin has native handling for @wordpress/*
  // packages, for that handling to kick in, we must not return anything from
  // function.
  /* eslint-disable-next-line consistent-return, no-useless-return */
  return;
}
const marketingOptinBlock = Object.assign({}, wpScriptConfig, {
  stats,
  name: 'marketing_optin_block',
  entry: {
    'marketing-optin-block': '/assets/js/src/marketing-optin-block/index.tsx',
    'marketing-optin-block-frontend':
      '/assets/js/src/marketing-optin-block/frontend.ts',
  },
  output: {
    filename: '[name].js',
    path: path.join(__dirname, 'assets/dist/js/marketing-optin-block'),
  },
  module: Object.assign({}, wpScriptConfig.module, {
    rules: [
      ...wpScriptConfig.module.rules,
      {
        test: /\.(t|j)sx?$/,
        exclude: /node_modules/,
        use: {
          loader: 'babel-loader?cacheDirectory',
          options: {
            presets: ['@wordpress/babel-preset-default'],
          },
        },
      },
    ],
  }),
  resolve: {
    extensions: ['.js', '.jsx', '.ts', '.tsx'],
  },
  // use only needed plugins from wpScriptConfig and add the custom ones
  plugins: [
    ...wpScriptConfig.plugins.filter(
      (plugin) =>
        plugin.constructor.pluginName === 'mini-css-extract-plugin' ||
        plugin.constructor.name === 'CleanWebpackPlugin',
    ),
    new DependencyExtractionWebpackPlugin({
      injectPolyfill: true,
      requestToExternal,
      requestToHandle,
    }),
    new WebpackCopyPlugin({
      patterns: [
        {
          from: 'assets/js/src/marketing-optin-block/block.json',
          to: 'block.json',
        },
      ],
    }),
  ],
});

const emailEditorBlocks = Object.assign({}, wpScriptConfig, {
  name: 'email-editor-blocks',
  entry: {
    'powered-by-mailpoet-block':
      '/assets/js/src/mailpoet-custom-email-editor-blocks/powered-by-mailpoet/block.tsx',
    mailpoet: '/assets/js/src/mailpoet.ts',
  },
  output: {
    filename: '[name].js',
    path: path.join(__dirname, 'assets/dist/js/email-editor-blocks'),
  },
  module: Object.assign({}, wpScriptConfig.module, {
    rules: [
      ...wpScriptConfig.module.rules,
      {
        test: /\.(t|j)sx?$/,
        exclude: /node_modules/,
        use: {
          loader: 'babel-loader?cacheDirectory',
          options: {
            presets: ['@wordpress/babel-preset-default'],
          },
        },
      },
    ],
  }),
  resolve: {
    alias: {
      mailpoet: path.resolve(__dirname, 'assets/js/src/mailpoet.ts'),
    },
    extensions: ['.js', '.jsx', '.ts', '.tsx'],
  },
  // use only needed plugins from wpScriptConfig and add the custom ones
  plugins: [
    ...wpScriptConfig.plugins.filter(
      (plugin) =>
        plugin.constructor.pluginName === 'mini-css-extract-plugin' ||
        plugin.constructor.name === 'CleanWebpackPlugin',
    ),
    new DependencyExtractionWebpackPlugin({
      injectPolyfill: true,
      requestToExternal,
      requestToHandle,
    }),
    new WebpackCopyPlugin({
      patterns: [
        {
          from: 'assets/js/src/mailpoet-custom-email-editor-blocks/powered-by-mailpoet/block.json',
          to: 'powered-by-mailpoet/block.json',
        },
      ],
    }),
  ],
});

const emailEditorCustom = Object.assign({}, wpScriptConfig, {
  name: 'email_editor',
  entry: {
    email_editor: '../packages/js/email-editor/src/index.ts',
  },
  output: {
    filename: '[name].js',
    path: path.join(__dirname, 'assets/dist/js/email-editor'),
  },
  resolve: {
    ...wpScriptConfig.resolve,
    modules: ['node_modules', '../packages/js/email-editor'],
  },
  module: Object.assign({}, wpScriptConfig.module, {
    rules: [
      ...wpScriptConfig.module.rules,
      {
        test: /\.(t|j)sx?$/,
        exclude: /node_modules/,
        use: {
          loader: 'babel-loader?cacheDirectory',
          options: {
            presets: ['@wordpress/babel-preset-default'],
          },
        },
      },
    ],
  }),
  plugins: PRODUCTION_ENV
    ? wpScriptConfig.plugins
    : [...wpScriptConfig.plugins, new ForkTsCheckerWebpackPlugin()],
});

// Temporary solution to build rich-text package for email editor
const emailEditorRichText = Object.assign({}, wpScriptConfig, {
  name: 'email-editor-rich-text',
  entry: {
    'rich-text': path.resolve(
      __dirname,
      '../packages/js/email-editor/node_modules/@wordpress/rich-text/build/index.js',
    ),
  },
  output: {
    filename: '[name].js',
    path: path.join(__dirname, 'assets/dist/js/email-editor'),
    library: ['wp', 'richText'], // Expose the richText package to the global wp object
    libraryTarget: 'window', // Ensure it is accessible globally
  },
  resolve: {
    extensions: ['.js', '.jsx', '.ts', '.tsx'],
  },
  module: Object.assign({}, wpScriptConfig.module, {
    rules: [
      ...wpScriptConfig.module.rules,
      {
        test: /\.(t|j)sx?$/,
        exclude: /node_modules/,
        use: {
          loader: 'babel-loader?cacheDirectory',
          options: {
            presets: ['@wordpress/babel-preset-default'],
          },
        },
      },
    ],
  }),
});

const emailEditorIntegration = Object.assign({}, wpScriptConfig, {
  name: 'email_editor_integration',
  entry: {
    email_editor_integration: 'mailpoet-email-editor-integration/index.ts',
  },
  output: {
    filename: '[name].js',
    path: path.join(__dirname, 'assets/dist/js/email_editor_integration'),
  },
  resolve: {
    ...wpScriptConfig.resolve,
    modules: ['node_modules', 'assets/js/src'],
  },
  plugins: PRODUCTION_ENV
    ? wpScriptConfig.plugins
    : [...wpScriptConfig.plugins, new ForkTsCheckerWebpackPlugin()],
});

const configs = [
  publicConfig,
  adminConfig,
  emailEditorCustom,
  formPreviewConfig,
  postEditorBlock,
  marketingOptinBlock,
  emailEditorBlocks,
  emailEditorIntegration,
  emailEditorRichText,
];

module.exports = (env) => {
  // Include tests build only if requested
  if (env && env.BUILD_TESTS === 'build') {
    configs.push(testConfig);
  }

  // If only the test build is requested
  if (env && env.BUILD_ONLY_TESTS === 'true') {
    return [testConfig];
  }

  return configs.map((conf) => {
    const config = Object.assign({}, conf);
    if (
      config.name === 'marketing_optin_block' ||
      config.name === 'email_editor' ||
      config.name === 'email_editor_integration'
    ) {
      return config;
    }
    if (config.name !== 'test') {
      config.plugins = config.plugins || [];
      config.plugins.push(
        new WebpackManifestPlugin({
          // create single manifest file for all Webpack configs
          seed: manifestSeed,
        }),
      );
    }
    return Object.assign({}, baseConfig, config);
  });
};
