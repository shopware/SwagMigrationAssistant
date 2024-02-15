const baseRules = {
    // Match the max line length with the phpstorm default settings
    'max-len': ['error', 125, { ignoreRegExpLiterals: true }],
    // Warn about useless path segment in import statements
    'import/no-useless-path-segments': 0,
    // don't require .vue and .js extensions
    'import/extensions': ['error', 'always', {
        js: 'never',
        ts: 'never',
    }],
    'no-console': ['error', { allow: ['warn', 'error'] }],
    'comma-dangle': ['error', 'always-multiline'],
    'no-restricted-exports': 'off',
};

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
        'html',
    ],
    parser: '@typescript-eslint/parser',
    settings: {
        'import/resolver': {
            node: {},
            webpack: {
                config: {
                    resolve: {
                        extensions: ['.js', '.ts', '.json', '.less', '.twig'],
                    },
                },
            },
        },
    },
    rules: {
        ...baseRules,
    },
    overrides: [
        {
            files: ['**/*.ts'],
            extends: [
                '@shopware-ag/eslint-config-base',
                'plugin:@typescript-eslint/eslint-recommended',
                'plugin:@typescript-eslint/recommended',
                'plugin:@typescript-eslint/recommended-requiring-type-checking',
            ],
            parser: '@typescript-eslint/parser',
            parserOptions: {
                tsconfigRootDir: __dirname,
                project: ['./tsconfig.json'],
            },
            plugins: ['@typescript-eslint'],
            rules: {
                '@typescript-eslint/ban-ts-comment': 0,
                '@typescript-eslint/no-unsafe-member-access': 'error',
                '@typescript-eslint/no-explicit-any': 'error',
                '@typescript-eslint/no-unsafe-call': 'error',
                '@typescript-eslint/no-unsafe-assignment': 'error',
                '@typescript-eslint/no-unsafe-return': 'error',
                '@typescript-eslint/explicit-module-boundary-types': 0,
                '@typescript-eslint/prefer-ts-expect-error': 'error',
                'no-shadow': 'off',
                '@typescript-eslint/no-shadow': ['error'],
                '@typescript-eslint/consistent-type-imports': ['error'],
                'no-void': 'off',
                // Disable the base rule as it can report incorrect errors
                'no-unused-vars': 'off',
                '@typescript-eslint/no-unused-vars': 'error',
                'import/no-unresolved': 0,
                ...baseRules,
            },
        },
        {
            files: ['*.ts'],
            plugins: ['simple-import-sort', 'import'],
            rules: {
                'simple-import-sort/imports': [
                    'error',
                    {
                        groups: [
                            // Packages `vue` related packages come first.
                            ['^vue', '^@?\\w'],
                            // Internal packages.
                            ['^(@|shopware-ag)(/.*|$)'],
                            // Side effect imports.
                            ['^\\u0000'],
                            // Parent imports. Put `..` last.
                            ['^\\.\\.(?!/?$)', '^\\.\\./?$'],
                            // Other relative imports. Put same-folder imports and `.` last.
                            ['^\\./(?=.*/)(?!/?$)', '^\\.(?!/?$)', '^\\./?$'],
                            // Style imports.
                            ['^.+\\.?(css)$'],
                        ],
                    },
                ],
            },
        },
    ],
};
