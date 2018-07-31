import { Component } from 'src/core/shopware';
import template from './sw-migration-wizard.html.twig';
import './sw-migration-wizard.less';

Component.register('sw-migration-wizard', {
    template,

    data() {
        return {
            showModal: true,
            breadcrumbItems: [
                this.$tc('sw-migration.wizard.pathSettings'),
                this.$tc('sw-migration.wizard.pathUserManagement'),
                this.$tc('sw-migration.wizard.pathEditUser')
            ],
            breadcrumbDescription: this.$tc('sw-migration.wizard.pathTarget')
        };
    },

    created() {
        if (this.$route.query.show) {
            this.showModal = this.$route.query.show;
        }
    },

    beforeRouteUpdate(to, from, next) {
        this.showModal = true;
        next();
    },

    methods: {
        onConnect() {
            console.log('Insert method, please');
        },

        onCloseModal() {
            this.showModal = false;
            this.$route.query.show = this.showModal;
        }
    }
});
