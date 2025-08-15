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
    ],
    plugins: [
        '@typescript-eslint',
        'jest',
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
            node: {},
            webpack: {
                config: {
                    resolve: {
                        extensions: ['.js', '.ts', '.vue', '.json', '.less', '.twig'],
                    },
                },
            },
        },
    },
    rules: {
        'comma-dangle': ['error', 'always-multiline'],
        'max-len': ['error', 125, {
            ignoreRegExpLiterals: true,
        }],
        'no-console': ['error', {
            allow: ['warn', 'error'],
        }],
        'import/extensions': [
            'error',
            'ignorePackages',
            {
                js: 'never',
                ts: 'never',
            },
        ],
        'no-void': 'off',
        'no-unused-vars': 'off',
        '@typescript-eslint/no-unused-vars': 'error',
    },
};
