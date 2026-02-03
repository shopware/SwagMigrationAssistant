/**
 * @sw-package after-sales
 */
import { mount } from '@vue/test-utils';
import SwagMigrationDataGridExtended from 'SwagMigrationAssistant/module/swag-migration/component/swag-migration-data-grid-extended';

const defaultProps = {
    columns: [
        { property: 'name', label: 'Name' },
    ],
    dataSource: [
        { id: '1', name: 'Item 1' },
        { id: '2', name: 'Item 2' },
    ],
    showSelection: true,
};

async function createWrapper(props = {}) {
    await wrapTestComponent('sw-data-grid');
    
    Shopware.Component.extend('swag-migration-data-grid-extended', 'sw-data-grid', SwagMigrationDataGridExtended);

    return mount(await Shopware.Component.build('swag-migration-data-grid-extended'), {
        props: {
            ...defaultProps,
            ...props,
        },
        global: {
            stubs: {
                'sw-context-menu-item': true,
                'sw-context-button': true,
                'sw-context-menu': true,
                'sw-data-grid-column-boolean': true,
                'sw-data-grid-inline-edit': true,
                'sw-data-grid-settings': true,
                'sw-data-grid-skeleton': true,
                'sw-checkbox-field': true,
                'sw-popover': true,
                'sw-provide': true,
                'router-link': true,
                'sw-icon': true,
            },
        },
    });
}

describe('module/swag-migration/component/swag-migration-data-grid-extended', () => {
    describe('selectionCount', () => {
        it('should use customSelectionCount when provided', async () => {
            const wrapper = await createWrapper({
                customSelectionCount: 100,
            });

            expect(wrapper.vm.selectionCount).toBe(100);
        });

        it('should fall back to selection length when customSelectionCount is null', async () => {
            const wrapper = await createWrapper({
                customSelectionCount: null,
            });

            expect(wrapper.vm.selectionCount).toBe(0);

            wrapper.vm.selection = { item1: {}, item2: {} };
            await wrapper.vm.$nextTick();

            expect(wrapper.vm.selectionCount).toBe(2);
        });

        it('should preserve previous count during loading', async () => {
            const wrapper = await createWrapper({
                customSelectionCount: 50,
                isLoading: false,
            });

            expect(wrapper.vm.selectionCount).toBe(50);

            await wrapper.setProps({
                isLoading: true,
                customSelectionCount: 0,
            });

            expect(wrapper.vm.selectionCount).toBe(50);
        });

        it('should update count after loading completes', async () => {
            const wrapper = await createWrapper({
                customSelectionCount: 50,
                isLoading: false,
            });

            expect(wrapper.vm.selectionCount).toBe(50);

            await wrapper.setProps({
                isLoading: true,
                customSelectionCount: 0,
            });

            expect(wrapper.vm.selectionCount).toBe(50);

            await wrapper.setProps({
                isLoading: false,
                customSelectionCount: 75,
            });

            expect(wrapper.vm.selectionCount).toBe(75);
        });

        it('should not preserve count if previous count was zero', async () => {
            const wrapper = await createWrapper({
                customSelectionCount: 0,
                isLoading: false,
            });

            expect(wrapper.vm.selectionCount).toBe(0);

            await wrapper.setProps({
                isLoading: true,
            });

            expect(wrapper.vm.selectionCount).toBe(0);
        });
    });
});
