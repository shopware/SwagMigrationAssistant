'use strict';

// eslint-disable-next-line @typescript-eslint/no-require-imports
module.exports = require('babel-jest').default.createTransformer({
    presets: [
        ['@babel/preset-env', { targets: { node: 'current' } }],
        '@babel/preset-typescript',
    ],
    plugins: ['shopware-vite-meta-glob'],
});
