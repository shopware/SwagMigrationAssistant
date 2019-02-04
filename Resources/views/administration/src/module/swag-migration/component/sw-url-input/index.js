import { Component } from 'src/core/shopware';
import template from './sw-url-input.html.twig';
import './sw-url-input.less';

Component.register('sw-url-input', {
    template,

    props: {
        value: {
            type: String,
            default: ''
        },
        label: {
            type: String,
            required: false,
            default: ''
        },
        placeholder: {
            type: String,
            required: false,
            default: ''
        },
        switchLabel: {
            type: String,
            required: false,
            default: ''
        }
    },

    data() {
        return {
            sslActive: true,
            urlInput: ''
        };
    },

    computed: {
        prefixClass() {
            if (this.sslActive) {
                return 'is--ssl';
            }

            return '';
        },

        urlPrefix() {
            if (this.sslActive) {
                return 'https://';
            }

            return 'http://';
        }
    },

    watch: {
        value: {
            immediate: true,
            handler(newUrl) {
                this.checkInput(newUrl);
            }
        }
    },

    methods: {
        urlChanged(inputValue) {
            if (inputValue === null) {
                this.setUrlInputValue('');
                return;
            }

            this.checkInput(inputValue);
        },

        checkInput(inputValue) {
            let newValue = inputValue;

            if (newValue.match(/^\s*https?:\/\//) !== null) {
                const sslFound = newValue.match(/^\s*https:\/\//);
                this.sslActive = (sslFound !== null);
                newValue = newValue.replace(/^\s*https?:\/\//, '');
            }

            this.setUrlInputValue(newValue);
        },

        /**
         * Set the urlInput variable and also the current value inside the html input.
         * The sw-field does not update the html if there is no change in the binding variable (urlInput /
         * because it gets watched), so it must be done manually (to replace / remove unwanted user input).
         *
         * @param newValue
         */
        setUrlInputValue(newValue) {
            this.urlInput = newValue;
            this.emitUrl();

            if (this.$refs.urlField !== undefined) {
                this.$refs.urlField.currentValue = this.urlInput;
            } else {
                this.$nextTick(() => {
                    this.$refs.urlField.currentValue = this.urlInput;
                });
            }
        },

        sslChanged(newValue) {
            this.sslActive = newValue;
            this.emitUrl();
        },

        emitUrl() {
            this.$emit('input', this.urlPrefix + this.urlInput.trim());
        }
    }
});
