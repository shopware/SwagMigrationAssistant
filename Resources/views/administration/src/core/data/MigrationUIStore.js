import { object } from 'src/core/service/util.service';
import SimpleStateManagementStore from './SimpleStateManagementStore';

/**
 * To get this store in the browser console execute:
 * migrationProcessStore = Shopware.Application.getContainer('factory').state.getStore('migrationUI')
 */
export const UI_COMPONENT_INDEX = Object.freeze({
    DATA_SELECTOR: 0,
    PREMAPPING: 1,
    LOADING_SCREEN: 2,
    RESULT_SUCCESS: 3,
    RESULT_WARNING: 4,
    RESULT_FAILURE: 5,
    PAUSE_SCREEN: 6,
    TAKEOVER: 7
});

class MigrationUIStore extends SimpleStateManagementStore {
    constructor() {
        super();
        this.state = {
            componentIndex: UI_COMPONENT_INDEX.DATA_SELECTOR,
            isLoading: true,
            isPaused: false,
            isMigrationAllowed: false,
            isPremappingValid: false,
            dataSelectionIds: [],
            premapping: []
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
    setIsMigrationAllowed(value) {
        this._checkDebugging(this.state.isMigrationAllowed, value);
        this.state.isMigrationAllowed = value;
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
     * @param {Object[]} newPremapping
     */
    setPremapping(newPremapping) {
        const newPremappingCopy = object.deepCopyObject(newPremapping);
        this._checkDebugging(this.state.premapping, newPremappingCopy);
        this.state.premapping = newPremappingCopy;
    }
}

export default MigrationUIStore;
