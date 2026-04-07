/**
 * @package fundamentals@after-sales
 */
import js from '@eslint/js';
import tsPlugin from '@typescript-eslint/eslint-plugin';
import tsParser from '@typescript-eslint/parser';
import pluginJest from 'eslint-plugin-jest';
import pluginFileProgress from 'eslint-plugin-file-progress';
import prettierConfig from 'eslint-config-prettier';
import globals from 'globals';

export default [
    {
        ignores: ['node_modules/**'],
    },
    js.configs.recommended,
    ...tsPlugin.configs['flat/recommended'],
    pluginJest.configs['flat/recommended'],
    {
        files: ['**/*.js', '**/*.ts'],
        languageOptions: {
            parser: tsParser,
            parserOptions: {
                ecmaVersion: 'latest',
                sourceType: 'module',
            },
            globals: {
                ...globals.node,
                ...globals.browser,
                ...globals.jest,
                Shopware: 'readonly',
                flushPromises: 'readonly',
                wrapTestComponent: 'readonly',
            },
        },
        plugins: {
            '@typescript-eslint': tsPlugin,
            jest: pluginJest,
            'file-progress': pluginFileProgress,
        },
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
            'eqeqeq': ['error', 'always', { null: 'ignore' }],
            'array-callback-return': ['error', { allowImplicit: true }],
            'no-return-assign': 'error',
            'no-throw-literal': 'error',
            'no-loop-func': 'error',
            'no-unused-expressions': ['error', { allowShortCircuit: true, allowTernary: true, allowTaggedTemplates: true }],
            'no-new-wrappers': 'error',
            'default-case': ['error', { commentPattern: '^no default$' }],
            'radix': 'error',
            'no-eval': 'error',
            'no-implied-eval': 'error',
            'no-new-func': 'error',
            'no-script-url': 'error',
            'prefer-template': 'error',
            'prefer-rest-params': 'error',
            'prefer-spread': 'error',
            'no-restricted-imports': ['error', { patterns: ['../**'] }],
            'no-shadow': 'off',
            '@typescript-eslint/no-shadow': 'error',
        },
    },
    prettierConfig,
];
