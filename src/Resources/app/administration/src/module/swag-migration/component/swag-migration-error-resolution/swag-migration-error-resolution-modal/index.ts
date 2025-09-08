import template from './swag-migration-error-resolution-modal.html.twig';
import './swag-migration-error-resolution-modal.scss';

/**
 * @private
 */
export interface SwagMigrationErrorResolutionModalData {
    openDetailsModal: boolean;
    tablePage: number;
    tableLimit: number;
    tableTotal: number;
}

/**
 * @private
 * @sw-package fundamentals@after-sales
 */
export default Shopware.Component.wrapComponentConfig({
    template,

    data(): SwagMigrationErrorResolutionModalData {
        return {
            openDetailsModal: false,
            tablePage: 1,
            tableLimit: 15,
            tableTotal: 145,
        };
    },

    computed: {
        log() {
            return {
                entityName: 'product',
                fieldName: 'name',
            };
        },

        modalTitle() {
            return this.$tc('swag-migration.index.error-resolution.modal.title', {
                code: 'empty_required_field',
                entityName: 'product',
                fieldName: 'name',
            });
        },

        tableDataSource() {
            return Array.from({ length: 50 }, (_, i) => ({
                id: `test-id-${i + 1}`,
                status: i % 2 === 0,
                salutation: i % 2 === 0 ? 'Mr' : 'Ms',
                firstname: `firstname${i + 1}`,
                lastname: `lastname${i + 1}`,
            }));
        },

        tableColumns() {
            return [
                {
                    label: 'Status',
                    property: 'status',
                    sortable: true,
                    position: 1,
                },
                {
                    label: 'Salutation',
                    property: 'salutation',
                    sortable: true,
                    position: 2,
                },
                {
                    label: 'First name',
                    property: 'firstname',
                    sortable: true,
                    position: 3,
                },
                {
                    label: 'Last name',
                    property: 'lastname',
                    sortable: true,
                    position: 4,
                },
                {
                    label: 'Email',
                    property: 'email',
                    sortable: true,
                    position: 5,
                },
            ];
        },
    },

    methods: {
        statusBadgeClass(status: boolean) {
            return status
                ? 'swag-migration-error-resolution-modal__left-status--unresolved'
                : 'swag-migration-error-resolution-modal__left-status--resolved';
        },

        statusBadgeText(status: boolean) {
            return status
                ? this.$tc('swag-migration.index.error-resolution.modals.error.left.status.resolved')
                : this.$tc('swag-migration.index.error-resolution.modals.error.left.status.unresolved');
        },
    },
});
