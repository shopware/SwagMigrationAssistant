import { Application } from 'src/core/shopware';
import deDeSnippets from '../../src/app/snippets/de-DE.json';
import enGBSnippets from '../../src/app/snippets/en-GB.json';

Application.addInitializerDecorator('locale', (localeFactory) => {
    localeFactory.extend('de-DE', deDeSnippets);
    localeFactory.extend('en-GB', enGBSnippets);

    return localeFactory;
});