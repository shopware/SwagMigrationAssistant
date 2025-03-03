const modules = import.meta.glob('./[a-z0-9-]*/[a-z0-9-]*/[a-z0-9-]*/index.js', { eager: true });
Object.values(modules);
