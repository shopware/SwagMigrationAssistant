const { Mixin } = Shopware;
const { debug } = Shopware.Utils;

/**
 * Mixin for the navigation logic inside a wizard.
 * See swag-migration-wizard as an example.
 *
 * @private
 * @package services-settings
 */
Mixin.register('swag-wizard', {
    inject: [
        'feature',
    ],
    data() {
        return {
            routes: {},
            /* Example routes
            routes: {
                introduction: {
                    name: 'swag.migration.wizard.introduction',
                    index: 0 // the index defines the order of the navigation (it can be changed a runtime)
                },
                profile: {
                    name: 'swag.migration.wizard.profile',
                    index: 1
                },
                profileCreate: {
                    name: 'swag.migration.wizard.profileCreate',
                    index: 1.1 // not available through nextRoute (child from profile)
                },
                profileSelect: {
                    name: 'swag.migration.wizard.profileSelect',
                    index: 1.1 // not available through nextRoute (child from profile)
                },
                profileInformation: {
                    name: 'swag.migration.wizard.profileInformation',
                    index: 2
                }
            } */
            currentRoute: {
                name: '',
                index: 0,
            },
        };
    },

    computed: {
        /**
         * Current route index (wizard step).
         * This does not include child routes like 5.1
         *
         * @returns {number}
         */
        routeIndex() {
            return Math.floor(this.currentRoute.index);
        },

        /**
         * Get the number of steps that the wizard runs through.
         * This includes all routes except for the child routes like 5.1
         *
         * @returns {number}
         */
        routeCount() {
            const routeIndices = [];
            Object.keys(this.routes).forEach((routeIndex) => {
                if (!routeIndices.includes(Math.floor(this.routes[routeIndex].index))) {
                    routeIndices.push(Math.floor(this.routes[routeIndex].index));
                }
            });

            return routeIndices.length;
        },

        /**
         * Returns the previous route (next round number that is smaller than the current index).
         * If the user is on a child route for example 5.1 it will return the route with index of 5.
         *
         * @returns {Object|boolean<false>}
         */
        routePrevious() {
            let previousRoute;

            Object.keys(this.routes).forEach((route) => {
                if (this.routes[route].index < this.currentRoute.index) {
                    if (previousRoute === undefined ||
                        Math.floor(this.routes[route].index) > previousRoute.index
                    ) {
                        previousRoute = this.routes[route];
                    }
                }
            });

            return previousRoute !== undefined ? previousRoute : false;
        },

        /**
         * Returns the next route (next round number that is bigger than the current index).
         * (except for child routes which are excluded like 5.1).
         *
         * @returns {Object|boolean<false>}
         */
        routeNext() {
            let nextRoute;
            Object.keys(this.routes).forEach((route) => {
                if (Math.floor(this.routes[route].index) > this.routeIndex) {
                    if (nextRoute === undefined ||
                        Math.floor(this.routes[route].index) < nextRoute.index
                    ) {
                        nextRoute = this.routes[route];
                    }
                }
            });

            return nextRoute !== undefined ? nextRoute : false;
        },

        /**
         * Checks if there is a previous route we can navigate to.
         *
         * @returns {boolean}
         */
        navigateToPreviousPossible() {
            return this.routePrevious !== false;
        },

        /**
         * Checks if there is a next route we can navigate to.
         *
         * @returns {boolean}
         */
        navigateToNextPossible() {
            return this.routeNext !== false;
        },
    },

    /**
     * Match the current route when the component gets created.
     */
    created() {
        // don't trigger the callback when the wizard is created.
        // but set the right current route.
        this.matchCurrentRoute(false);
    },

    updated() {
        this.matchCurrentRoute(true);
    },

    methods: {
        /**
         * Logic for matching the current route.
         * It searches for the route inside this.routes.
         */
        matchCurrentRoute(notifyCallback = true) {
            const routerCurrentRoute = this.$router.currentRoute.value;

            // check for current child route
            let currentRoute;
            const currentRouteFound = Object.keys(this.routes).some((routeIndex) => {
                if (this.routes[routeIndex].name === routerCurrentRoute.name) {
                    currentRoute = this.routes[routeIndex];
                    return true;
                }

                return false;
            });

            if (currentRouteFound) {
                this.currentRoute = currentRoute;
                if (notifyCallback) {
                    this.onChildRouteChanged();
                }
            }
        },

        /**
         * Gets called when a route change has happened. This is useful to update texts inside the modal but
         * outside of the router view (for example headlines, buttons, ...)
         * Note: does not get called on created (to allow loading things from the api first)
         */
        onChildRouteChanged() {
            debug.warn(
                'swag-wizard Mixin',
                'When using the wizard mixin you have to implement your custom "onChildRouteChanged()" method.',
            );
        },

        /**
         * Navigate to the specified route. You can get the route from this.routes
         *
         * @param {Object} route
         */
        navigateToRoute(route) {
            this.$router.push({ name: route.name }).catch((error) => {
                console.error(error.message);
            });
        },

        /**
         * Navigate to the previous route inside this.routes.
         * It will skip route child indices like '3.1'
         * It will return false if there is nothing to navigate to.
         *
         * @returns {boolean}
         */
        navigateToPrevious() {
            if (this.navigateToPreviousPossible) {
                this.navigateToRoute(this.routePrevious);
                return true;
            }

            return false;
        },

        /**
         * Navigate to the next route inside this.routes.
         * It will skip route child indices like '3.1'.
         * It will return false if there is nothing to navigate to.
         *
         * @returns {boolean}
         */
        navigateToNext() {
            if (this.navigateToNextPossible) {
                this.navigateToRoute(this.routeNext);
                return true;
            }

            return false;
        },
    },
});
