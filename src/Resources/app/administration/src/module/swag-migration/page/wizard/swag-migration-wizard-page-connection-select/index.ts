import template from './swag-migration-wizard-page-connection-select.html.twig';
import type { MigrationConnection, TRepository } from '../../../../../type/types';

const { Criteria } = Shopware.Data;

/**
 * @private
 */
export interface SwagMigrationWizardPageConnectionSelectData {
    selectedConnectionId: string | null;
    connections: MigrationConnection[];
    context: unknown;
}

/**
 * @private
 * @sw-package fundamentals@after-sales
 */
export default Shopware.Component.wrapComponentConfig({
    template,

    inject: [
        'repositoryFactory',
    ],

    emits: [
        'onChildRouteReadyChanged',
        'onConnectionSelected',
    ],

    props: {
        currentConnectionId: {
            type: String,
            default: '',
        },
    },

    data(): SwagMigrationWizardPageConnectionSelectData {
        return {
            selectedConnectionId: null,
            connections: [] as MigrationConnection[],
            context: Shopware.Context.api,
        };
    },

    metaInfo() {
        return {
            title: this.$createTitle(),
        };
    },

    computed: {
        migrationConnectionRepository(): TRepository<'swag_migration_connection'> {
            return this.repositoryFactory.create('swag_migration_connection');
        },
    },

    watch: {
        currentConnectionId: {
            immediate: true,
            handler(newConnectionId: string) {
                this.selectedConnectionId = newConnectionId;
                this.onConnectionSelected(newConnectionId);
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
                this.onConnectionSelected(items[0]?.id ?? '');
            });
        },

        onConnectionSelected(newId: string) {
            const connection = this.connections.find((con) => {
                return con.id === newId;
            });

            this.selectedConnectionId = newId;
            this.$emit('onChildRouteReadyChanged', !!connection);
            this.$emit('onConnectionSelected', connection);
        },
    },
});
