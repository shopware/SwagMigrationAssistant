/**
 * @package fundamentals@after-sales
 */
import js from '@eslint/js';
import tsPlugin from '@typescript-eslint/eslint-plugin';
import tsParser from '@typescript-eslint/parser';
import pluginPlaywright from 'eslint-plugin-playwright';
import prettierConfig from 'eslint-config-prettier';
import globals from 'globals';

export default [
    {
        ignores: [
            'node_modules/**',
            'test-results/**',
            'playwright-report/**',
            'summary.json',
            '.vscode/**',
            '.idea/**',
        ],
    },
    js.configs.recommended,
    ...[
        ...tsPlugin.configs['flat/recommended-type-checked'],
        ...tsPlugin.configs['flat/stylistic-type-checked'],
    ].map(config => ({ ...config, files: ['**/*.ts'] })),
    pluginPlaywright.configs['flat/recommended'],
    {
        files: ['**/*.ts'],
        languageOptions: {
            parser: tsParser,
            parserOptions: {
                projectService: true,
            },
            globals: {
                ...globals.node,
                ...globals.browser,
                Shopware: 'readonly',
            },
        },
        plugins: {
            '@typescript-eslint': tsPlugin,
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
            'no-useless-escape': 'off',
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
            // Correctness rules (aligned with other configs)
            'eqeqeq': ['error', 'always', { null: 'ignore' }],
            'array-callback-return': ['error', { allowImplicit: true }],
            'no-return-assign': 'error',
            'no-throw-literal': 'error',
            'no-loop-func': 'error',
            'no-unused-expressions': ['error', { allowShortCircuit: true, allowTernary: true, allowTaggedTemplates: true }],
            'no-new-wrappers': 'error',
            'default-case': ['error', { commentPattern: '^no default$' }],
            'radix': 'error',
            // Security rules
            'no-eval': 'error',
            'no-implied-eval': 'error',
            'no-new-func': 'error',
            'no-script-url': 'error',
            // Modern JS rules
            'prefer-template': 'error',
            'prefer-rest-params': 'error',
            'prefer-spread': 'error',
            // Variable shadowing (TS-aware version)
            'no-shadow': 'off',
            '@typescript-eslint/no-shadow': 'error',
        },
    },
    prettierConfig,
];
