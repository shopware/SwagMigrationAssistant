/**
 * @package admin
 */
module.exports = {
    root: true,
    env: {
        node: true,
        browser: true,
        es6: true,
        'jest/globals': true,
    },

    parserOptions: {
        ecmaVersion: 'latest',
        sourceType: 'module',
    },

    globals: {
        Shopware: true,
        flushPromises: true,
        wrapTestComponent: true,
    },

    plugins: [
        'jest',
        'file-progress',
        '@typescript-eslint',
    ],

    extends: [
        'eslint:recommended',
        'plugin:jest/recommended',
        '@shopware-ag/eslint-config-base',
        'prettier',
    ],

    parser: '@typescript-eslint/parser',

    rules: {
        'file-progress/activate': 1,
        quotes: ['error', 'single', { avoidEscape: true }],
        semi: ['error', 'always'],
        'comma-dangle': ['error', 'always-multiline'],
        'max-len': ['error', 125, {
            ignoreRegExpLiterals: true,
            ignoreComments: false,
            ignoreStrings: true,
            ignoreTemplateLiterals: true,
            ignoreUrls: true,
        }],
        'no-console': 'error',
        'no-useless-escape': 'off',
        'jest/expect-expect': 'error',
        'jest/no-duplicate-hooks': 'error',
        'jest/no-test-return-statement': 'error',
        'jest/prefer-hooks-in-order': 'error',
        'jest/prefer-hooks-on-top': 'error',
        'jest/prefer-to-be': 'error',
        'jest/require-top-level-describe': 'error',
        'jest/prefer-to-contain': 'error',
        'jest/prefer-to-have-length': 'error',
        'jest/consistent-test-it': ['error', { fn: 'it', withinDescribe: 'it' }],
        'import/extensions': 'off',
        'import/no-unresolved': 'off',
    },
};
