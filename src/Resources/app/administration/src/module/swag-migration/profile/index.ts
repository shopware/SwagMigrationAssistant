export const MODULE_COUNT = 17;

// @ts-ignore
const rawModules = import.meta.glob<any>('./[a-z0-9-]*/[a-z0-9-]*/[a-z0-9-]*/index.{js,ts}', {
    eager: true,
});

const modules: { path: string; content: unknown }[] = Object.entries(rawModules).map(
    ([
        path,
        content,
    ]) => ({
        path: path.replace(/^\.\//, ''),
        content,
    }),
);

if (modules.length !== MODULE_COUNT) {
    console.error(`[swag-migration-assistant]: Expected ${MODULE_COUNT} modules, but found ${modules.length}.`);
}

export default modules;
