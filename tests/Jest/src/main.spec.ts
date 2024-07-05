import Criteria from 'src/core/data/criteria.data';

describe('jest-infrastructure-test', () => {
    it('should have access to shopware imports', async () => {
        const myCriteria = new Criteria();

        expect(myCriteria).toBeTruthy();
    });
});
