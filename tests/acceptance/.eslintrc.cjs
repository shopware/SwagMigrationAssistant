/**
 * @package fundamentals@after-sales
 */
module.exports = {
    root: true,
    env: {
        node: true,
        browser: true,
        es6: true,
    },

    parserOptions: {
        ecmaVersion: 'latest',
        sourceType: 'module',
        project: true,
        tsconfigRootDir: __dirname,
    },

    globals: {
        Shopware: true,
    },

    plugins: [
        '@typescript-eslint',
    ],

    extends: [
        'eslint:recommended',
        'plugin:@typescript-eslint/recommended-type-checked',
        'plugin:@typescript-eslint/stylistic-type-checked',
        'plugin:playwright/recommended',
        '@shopware-ag/eslint-config-base',
        'prettier',
    ],

    parser: '@typescript-eslint/parser',

    rules: {
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
        'no-console': ['error', { allow: ['warn', 'error'] }],
        'no-useless-escape': 'off',
        'import/extensions': 'off',
        'import/no-unresolved': 'off',
        'import/prefer-default-export': 'off',
        'no-unused-vars': 'off',
        '@typescript-eslint/no-unused-vars': ['error', { argsIgnorePattern: '^_' }],
        '@typescript-eslint/no-floating-promises': 'warn',
        '@typescript-eslint/no-unsafe-call': 'off',
        '@typescript-eslint/no-unsafe-member-access': 'off',
        '@typescript-eslint/no-unsafe-assignment': 'off',
        '@typescript-eslint/no-unsafe-return': 'off',
        'playwright/expect-expect': 'off',
        // Playwright tests often require sequential awaits in loops
        'no-await-in-loop': 'off',
        'no-restricted-syntax': 'off',
        'no-plusplus': 'off',
        'no-use-before-define': 'off',
        '@typescript-eslint/no-use-before-define': 'off',
        'no-empty-pattern': 'off',
        'no-return-await': 'off',
        '@typescript-eslint/return-await': 'off',
        'no-promise-executor-return': 'off',
        camelcase: 'off',
    },
};
