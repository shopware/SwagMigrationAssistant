import template from './swag-migration-wizard-page-connection-select.html.twig';

const { Component } = Shopware;
const { Criteria } = Shopware.Data;

/**
 * @private
 * @package services-settings
 */
Component.register('swag-migration-wizard-page-connection-select', {
    template,

    inject: {
        repositoryFactory: 'repositoryFactory',
    },

    props: {
        currentConnectionId: {
            type: String,
            default: '',
        },
    },

    data() {
        return {
            selectedConnectionId: null,
            connections: [],
            context: Shopware.Context.api,
        };
    },

    metaInfo() {
        return {
            title: this.$createTitle(),
        };
    },

    computed: {
        migrationConnectionRepository() {
            return this.repositoryFactory.create('swag_migration_connection');
        },
    },

    watch: {
        currentConnectionId: {
            immediate: true,
            handler(newConnectionId) {
                this.selectedConnectionId = newConnectionId;
                this.onConnectionSelected();
            },
        },
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.$emit('onChildRouteReadyChanged', false);
            const criteria = new Criteria(1, 100);

            return this.migrationConnectionRepository.search(criteria, this.context).then((items) => {
                this.connections = items;
                this.onConnectionSelected();
            });
        },

        onConnectionSelected() {
            const connection = this.connections.find((con) => {
                return con.id === this.selectedConnectionId;
            });

            if (connection) {
                this.$emit('onChildRouteReadyChanged', true);
                this.$emit('onConnectionSelected', connection);
            } else {
                this.$emit('onChildRouteReadyChanged', false);
            }
        },
    },
});
