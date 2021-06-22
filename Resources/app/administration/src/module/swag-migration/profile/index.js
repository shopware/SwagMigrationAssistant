const context = require.context('./', true, /\.\/[a-z0-9-]+\/[a-z0-9-]+\/[a-z0-9-]+\/index\.js$/);
context.keys().forEach((key) => {
    context(key);
});
