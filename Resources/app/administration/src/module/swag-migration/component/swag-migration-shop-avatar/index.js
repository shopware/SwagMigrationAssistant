import template from './swag-migration-shop-avatar.html.twig';
import './swag-migration-shop-avatar.scss';

const { Component } = Shopware;

Component.register('swag-migration-shop-avatar', {
    template,

    props: {
        size: {
            type: String,
            required: true,
        },
        color: {
            type: String,
            required: true,
        },
    },

    data() {
        return {
            fontSize: 16,
            lineHeight: 16,
        };
    },

    computed: {
        avatarStyle() {
            return {
                width: this.size,
                height: this.size,
                'background-color': this.color,
                'font-size': `${this.fontSize}px`,
                'line-height': `${this.lineHeight}px`,
            };
        },
    },

    mounted() {
        this.mountedComponent();
    },

    methods: {
        mountedComponent() {
            this.generateAvatarInitialsSize();
        },

        generateAvatarInitialsSize() {
            const avatarSize = this.$refs.shopAvatar.offsetHeight;

            this.fontSize = Math.round(avatarSize * 0.4);
            this.lineHeight = Math.round(avatarSize * 0.98);
        },
    },
});
