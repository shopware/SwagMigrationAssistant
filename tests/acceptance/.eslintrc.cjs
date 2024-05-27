/* eslint-env node */

module.exports = {
    extends: [
        'eslint:recommended',
        'plugin:@typescript-eslint/recommended-type-checked',
        'plugin:@typescript-eslint/stylistic-type-checked',
        'plugin:playwright/recommended',
    ],
    parser: '@typescript-eslint/parser',
    parserOptions: {
        project: true,
        tsconfigRootDir: __dirname,
    },
    plugins: ['@typescript-eslint'],
    root: true,
    rules: {
        quotes: ['error', 'single', { allowTemplateLiterals: true }],
        'no-console': ['error', { allow: ['warn', 'error'] }],
        'comma-dangle': ['error', 'always-multiline'],
        'no-unused-vars': 'off', // better use the typescript variant of it to prevent false positives
        '@typescript-eslint/no-unused-vars': 'warn',
        '@typescript-eslint/no-floating-promises': 'warn',
        'playwright/expect-expect': 'off',
        '@typescript-eslint/no-unsafe-call': 'off',
        '@typescript-eslint/no-unsafe-member-access': 'off',
        '@typescript-eslint/no-unsafe-assignment': 'off',
        '@typescript-eslint/no-unsafe-return': 'off',
    },
};
