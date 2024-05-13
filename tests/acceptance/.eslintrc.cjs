/* eslint-env node */
module.exports = {
    extends: [
        "eslint:recommended",
        "plugin:@typescript-eslint/recommended-type-checked",
        "plugin:@typescript-eslint/stylistic-type-checked",
        "plugin:playwright/recommended"
    ],
    plugins: ["@typescript-eslint"],
    parser: "@typescript-eslint/parser",
    parserOptions: {
        project: true,
        tsconfigRootDir: __dirname,
    },
    root: true,
    rules: {
        quotes: ["error", "single", { allowTemplateLiterals: true }],
        "no-console": ["error", { allow: ["warn", "error"] }],
        "comma-dangle": ["error", "always-multiline"],
        "no-unused-vars": "warn",
        "@typescript-eslint/no-unused-vars": "warn",
        "@typescript-eslint/no-floating-promises": "warn",
        "playwright/expect-expect": "off",
    },
};
