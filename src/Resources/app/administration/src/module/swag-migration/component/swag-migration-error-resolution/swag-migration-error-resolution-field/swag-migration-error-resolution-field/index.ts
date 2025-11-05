import type { Property } from '@administration/src/core/data/entity-definition.data';
import template from './swag-migration-error-resolution-field.html.twig';
import './swag-migration-error-resolution-field.scss';
import type { ErrorResolutionTableData } from '../../swag-migration-error-resolution-step';
import { MIGRATION_ERROR_RESOLUTION_SERVICE } from '../../../../service/swag-migration-error-resolution.service';

/**
 * @private
 * @sw-package fundamentals@after-sales
 */
export default Shopware.Component.wrapComponentConfig({
    template,

    inject: [
        MIGRATION_ERROR_RESOLUTION_SERVICE,
    ],

    props: {
        log: {
            type: Object as PropType<ErrorResolutionTableData>,
            required: true,
        },
        disabled: {
            type: Boolean,
            required: false,
            default: false,
        },
    },

    computed: {
        entityField(): Property | null {
            return this.swagMigrationErrorResolutionService.getEntityField(this.log?.entityName, this.log?.fieldName);
        },

        isScalarField(): boolean {
            return this.swagMigrationErrorResolutionService.isScalarField(this.log?.entityName, this.log?.fieldName);
        },

        effectiveEntityField(): Property | null {
            return this.swagMigrationErrorResolutionService.getEffectiveEntityField(
                this.log?.entityName,
                this.log?.fieldName,
            );
        },

        fieldType(): string | null {
            return this.swagMigrationErrorResolutionService.getFieldType(this.log?.entityName, this.log?.fieldName);
        },
    },

    methods: {
        onScalarFieldValueChanged(newValue: string | number | boolean | null) {
            console.log({
                scalarField: newValue,
            });
        },

        onRelationFieldValueChanged(newValue: string | string[] | null) {
            console.log({
                relationField: newValue,
            });
        },
    },
});
