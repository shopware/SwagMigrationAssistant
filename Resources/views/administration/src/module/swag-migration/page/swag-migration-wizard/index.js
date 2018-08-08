import { Component } from 'src/core/shopware';
import template from './swag-migration-wizard.html.twig';
import './swag-migration-wizard.less';

Component.register('swag-migration-wizard', {
    template,

    inject: ['migrationProfileService', 'migrationService'],

    mixins: [
        'notification'
    ],

    data() {
        return {
            credentials: {},
            profileId: '',
            showModal: true,
            breadcrumbItems: [
                this.$tc('swag-migration.wizard.pathSettings'),
                this.$tc('swag-migration.wizard.pathUserManagement'),
                this.$tc('swag-migration.wizard.pathEditUser')
            ],
            breadcrumbDescription: this.$tc('swag-migration.wizard.pathTarget')
        };
    },

    created() {
        const params = {
            offset: 0,
            limit: 100,
            additionalParams: {
                term: { gateway: 'api' }
            }
        };
        this.migrationProfileService.getList(params).then((response) => {
            this.credentials = response.data[0].credentialFields;
            this.profileId = response.data[0].id;
        });

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
            // TODO loading indicator?
            this.migrationProfileService.updateById(this.profileId, { credentialFields: this.credentials }).then((response) => {
                if (response.status === 204) {
                    this.migrationService.checkConnection().then((connectionCheckResponse) => {
                        if (connectionCheckResponse.success) {
                            this.createNotificationSuccess({ message: 'Connection Check erfolgreich' });
                            this.$router.push({
                                name: 'swag.migration.index',
                                params: { profileId: this.profileId }
                            });
                        }
                    });
                }
            });
        },

        onCloseModal() {
            this.showModal = false;
            this.$route.query.show = this.showModal;
        }
    }
});
