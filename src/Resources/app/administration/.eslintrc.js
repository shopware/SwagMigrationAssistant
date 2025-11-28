// eslint-disable-next-line no-undef
module.exports = {
    root: true,
    env: {
        browser: true,
    },
    globals: {
        Shopware: true,
    },
    extends: [
        '@shopware-ag/eslint-config-base',
        'eslint:recommended',
        'plugin:@typescript-eslint/recommended',
        'plugin:vue/vue3-recommended',
        'prettier',
    ],
    plugins: [
        '@typescript-eslint',
        'inclusive-language',
        'vuejs-accessibility',
        'file-progress',
        'filename-rules',
        'vue',
        'html',
    ],
    parser: '@typescript-eslint/parser',
    settings: {
        'import/resolver': {
            node: {
                extensions: ['.js', '.ts', '.vue', '.json', '.less', '.twig'],
            },
        },
    },
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
        'no-debugger': 'error',
        'no-var': 'error',
        'prefer-const': 'error',
        'prefer-arrow-callback': 'error',
        'arrow-spacing': ['error', { before: true, after: true }],
        'no-empty-function': 'error',
        'import/extensions': [
            'error',
            'ignorePackages',
            { js: 'never', ts: 'never' },
        ],
        'no-void': 'off',
        'no-unused-vars': 'off',
        '@typescript-eslint/no-unused-vars': ['error', { argsIgnorePattern: '^_' }],
        '@typescript-eslint/explicit-function-return-type': ['error', { allowExpressions: true }],
        '@typescript-eslint/no-explicit-any': 'error',
        '@typescript-eslint/consistent-type-imports': 'error',
        '@typescript-eslint/ban-ts-comment': 'off',
        'implicit-arrow-linebreak': 'off',
        'function-paren-newline': 'off',
    },
    overrides: [
        {
            files: ['*.ts', '*.vue'],
            rules: {
                '@typescript-eslint/explicit-module-boundary-types': 'error',
            },
        },
        {
            files: ['*.spec.ts', '*.test.ts'],
            rules: {
                '@typescript-eslint/no-empty-function': 'off',
            },
        },
    ],
};
