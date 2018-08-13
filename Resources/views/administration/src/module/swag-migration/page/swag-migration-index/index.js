import { Component, State } from 'src/core/shopware';
import template from './swag-migration-index.html.twig';
import './swag-migration-index.less';

Component.register('swag-migration-index', {
    template,

    mixins: [
        'notification'
    ],

    inject: ['migrationProfileService', 'migrationService'],

    data() {
        return {
            profile: {},
            catalogues: [
                {
                    id: 1,
                    name: this.$tc('swag-migration.index.selectDataCard.defaultCatalogue')
                }
            ],
        };
    },

    created() {
        if (this.$route.params.profileId) {
            this.migrationProfileService.getById(this.$route.params.profileId).then((response) => {
                this.profile = response.data;
            });
        }
    },

    methods: {
        onMigrate() {
            this.profile.entity = 'category';
            this.migrationService.fetchData(this.profile).then((response) => {
                this.createNotificationSuccess({message: 'yeah'})
            }).catch((response) => {
                if (response.response.data && response.response.data.errors) {
                    response.response.data.errors.forEach((error) => {
                        this.addError(error);
                    });
                }
            });
        },

        addError(error) {
            State.getStore('error').addError({
                type: 'migration-error',
                error
            });
        },

        editSettings() {
            this.$router.push({ name: 'swag.migration.wizard' });
        }
    }
});
