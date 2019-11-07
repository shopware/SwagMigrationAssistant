import template from './swag-migration-wizard-page-connection-select.html.twig';

const { Component } = Shopware;
const { Criteria } = Shopware.Data;

Component.register('swag-migration-wizard-page-connection-select', {
    template,

    inject: {
        context: 'apiContext',
        repositoryFactory: 'repositoryFactory'
    },

    props: {
        currentConnectionId: {
            type: String
        }
    },

    data() {
        return {
            selectedConnectionId: null,
            connections: []
        };
    },

    metaInfo() {
        return {
            title: this.$createTitle()
        };
    },

    computed: {
        migrationConnectionRepository() {
            return this.repositoryFactory.create('swag_migration_connection');
        }
    },

    created() {
        this.$emit('onChildRouteReadyChanged', false);
        const criteria = new Criteria(1, 100);

        this.migrationConnectionRepository.search(criteria, this.context).then((items) => {
            this.connections = items;
            this.onConnectionSelected();
        });
    },

    watch: {
        currentConnectionId: {
            immediate: true,
            handler(newConnectionId) {
                this.selectedConnectionId = newConnectionId;
                this.onConnectionSelected();
            }
        }
    },

    methods: {
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
        }
    }
});
