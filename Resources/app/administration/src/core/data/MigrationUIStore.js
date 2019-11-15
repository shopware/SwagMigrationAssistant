import SimpleStateManagementStore from './SimpleStateManagementStore';

const { object } = Shopware.Utils;

/**
 * To get this store in the browser console execute:
 * migrationProcessStore = Shopware.Application.getContainer('factory').state.getStore('migrationUI')
 */
export const UI_COMPONENT_INDEX = Object.freeze({
    WARNING_CONFIRM: -1,
    DATA_SELECTOR: 0,
    PREMAPPING: 1,
    LOADING_SCREEN: 2,
    MEDIA_SCREEN: 3,
    RESULT_SUCCESS: 4,
    PAUSE_SCREEN: 5,
    TAKEOVER: 6,
    CONNECTION_LOST: 7
});

class MigrationUIStore extends SimpleStateManagementStore {
    constructor() {
        super();
        this.state = {
            componentIndex: UI_COMPONENT_INDEX.DATA_SELECTOR,
            isLoading: false,
            isPaused: false,
            isPremappingValid: false,
            dataSelectionIds: [],
            dataSelectionTableData: [],
            premapping: [],
            unfilledPremapping: [],
            filledPremapping: []
        };
    }

    /**
     * @param {number} newIndex
     */
    setComponentIndex(newIndex) {
        this._checkDebugging(this.state.componentIndex, newIndex);
        this.state.componentIndex = newIndex;
    }

    /**
     * @param {boolean} value
     */
    setIsLoading(value) {
        this._checkDebugging(this.state.isLoading, value);
        this.state.isLoading = value;
    }

    /**
     * @param {boolean} value
     */
    setIsPaused(value) {
        this._checkDebugging(this.state.isPaused, value);
        this.state.isPaused = value;
    }

    /**
     * @param {boolean} value
     */
    setIsPremappingValid(value) {
        this._checkDebugging(this.state.isPremappingValid, value);
        this.state.isPremappingValid = value;
    }

    /**
     * @param {string[]} newIds
     */
    setDataSelectionIds(newIds) {
        const newIdsCopy = object.deepCopyObject(newIds);
        this._checkDebugging(this.state.dataSelectionIds, newIdsCopy);
        this.state.dataSelectionIds = newIdsCopy;
    }

    /**
     * @param {Object[]} newTableData
     */
    setDataSelectionTableData(newTableData) {
        const newTableDataCopy = object.deepCopyObject(newTableData);
        this._checkDebugging(this.state.dataSelectionTableData, newTableDataCopy);
        this.state.dataSelectionTableData = newTableDataCopy;
    }

    /**
     * @param {Object[]} newPremapping
     */
    setPremapping(newPremapping) {
        const newPremappingCopy = object.deepCopyObject(newPremapping);
        this._checkDebugging(this.state.premapping, newPremappingCopy);
        this.state.premapping = newPremappingCopy;

        if (this.state.premapping !== undefined && this.state.premapping.length > 0) {
            this._seperatePremapping();
        } else {
            this._setUnfilledPremapping([]);
            this._setFilledPremapping([]);
        }
    }

    getIsMigrationAllowed() {
        const requiredLookup = {};
        this.state.dataSelectionTableData.forEach((data) => {
            requiredLookup[data.id] = data.requiredSelection;
        });

        return this.state.dataSelectionIds.some(id => requiredLookup[id] === false);
    }

    _setUnfilledPremapping(unfilledPremapping) {
        this._checkDebugging(this.state.unfilledPremapping, unfilledPremapping);
        this.state.unfilledPremapping = unfilledPremapping;
    }

    _setFilledPremapping(filledPremapping) {
        this._checkDebugging(this.state.filledPremapping, filledPremapping);
        this.state.filledPremapping = filledPremapping;
    }

    _seperatePremapping() {
        const unfilledMapping = [];
        const filledMapping = [];

        this.state.premapping.forEach((group) => {
            const newFilledGroup = {
                choices: group.choices,
                entity: group.entity,
                mapping: []
            };

            const newUnfilledGroup = {
                choices: group.choices,
                entity: group.entity,
                mapping: []
            };

            group.mapping.forEach((mapping) => {
                if (mapping.destinationUuid.length > 0) {
                    newFilledGroup.mapping.push(mapping);
                } else {
                    newUnfilledGroup.mapping.push(mapping);
                }
            });

            if (newFilledGroup.mapping.length > 0) {
                filledMapping.push(newFilledGroup);
            }

            if (newUnfilledGroup.mapping.length > 0) {
                unfilledMapping.push(newUnfilledGroup);
            }
        });

        this._setUnfilledPremapping(unfilledMapping);
        this._setFilledPremapping(filledMapping);
    }
}

export default MigrationUIStore;
