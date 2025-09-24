// eslint-disable-next-line import/no-extraneous-dependencies
import defineConfig from 'stylelint-define-config';

export default defineConfig({
    plugins: ['@shopware-ag/stylelint-plugin-meteor'],
    rules: {
        'meteor/prefer-sizing-token': [true, { severity: 'error' }],
        'meteor/prefer-background-token': [true, { severity: 'error' }],
        'meteor/prefer-color-token': [true, { severity: 'error' }],
        'meteor/no-primitive-token': [true, { severity: 'error' }],
        'meteor/prefer-font-token': [true, { severity: 'error' }],
        'meteor/prefer-border-token': [true, { severity: 'error' }],
    },
});
