/**
 * @package fundamentals@after-sales
 */
import js from '@eslint/js';
import tsPlugin from '@typescript-eslint/eslint-plugin';
import tsParser from '@typescript-eslint/parser';
import pluginVue from 'eslint-plugin-vue';
import vueParser from 'vue-eslint-parser';
import prettierConfig from 'eslint-config-prettier';
import pluginImportX from 'eslint-plugin-import-x';
import pluginInclusiveLanguage from 'eslint-plugin-inclusive-language';
import pluginVueA11y from 'eslint-plugin-vuejs-accessibility';
import pluginFileProgress from 'eslint-plugin-file-progress';
import pluginFilenameRules from 'eslint-plugin-filename-rules';
import pluginHtml from 'eslint-plugin-html';
import globals from 'globals';

export default [
    {
        ignores: ['node_modules/**', 'static/**', '.tmp/**'],
    },
    js.configs.recommended,
    ...tsPlugin.configs['flat/recommended'],
    ...pluginVue.configs['flat/recommended'],
    {
        files: ['**/*.js', '**/*.ts', '**/*.vue', '**/*.html'],
        languageOptions: {
            parser: vueParser,
            parserOptions: {
                parser: tsParser,
                ecmaVersion: 'latest',
                sourceType: 'module',
            },
            globals: {
                ...globals.browser,
                Shopware: 'readonly',
            },
        },
        plugins: {
            '@typescript-eslint': tsPlugin,
            'import-x': pluginImportX,
            'inclusive-language': pluginInclusiveLanguage,
            'vuejs-accessibility': pluginVueA11y,
            'file-progress': pluginFileProgress,
            'filename-rules': pluginFilenameRules,
            html: pluginHtml,
        },
        settings: {
            'import-x/resolver': {
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
            'import-x/extensions': [
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
            'no-shadow': 'off',
            '@typescript-eslint/no-shadow': 'error',
        },
    },
    prettierConfig,
    {
        files: ['**/*.ts', '**/*.vue'],
        rules: {
            '@typescript-eslint/explicit-module-boundary-types': 'error',
        },
    },
    {
        files: ['**/*.spec.ts', '**/*.test.ts'],
        rules: {
            '@typescript-eslint/no-empty-function': 'off',
        },
    },
];
