import {Component} from 'src/core/shopware';
import template from './swag-migration-wizard.html.twig';
import './swag-migration-wizard.less';

Component.register('swag-migration-wizard', {
    template,

    data() {
        return {
            showModal: true,
            buttonPreviousVisible: true,
            buttonNextVisible: true,
            buttonPreviousText: this.$tc('swag-migration.wizard.buttonPrev'),
            buttonNextText: this.$tc('swag-migration.wizard.buttonNext'),
            routes: [
                'swag.migration.wizard.wizard1',
                'swag.migration.wizard.wizard2',
                'swag.migration.wizard.wizard3',
                'swag.migration.wizard.wizard4',
                'swag.migration.wizard.wizard4_success',
                'swag.migration.wizard.wizard4_error'
            ],
            routeCountVisible: 4,  //only show 4 dots and allow navigation between them.
            routeIndex: 0,
            routeIndexVisible: 0,   //only count up to 3
            apiKey: '',
            apiUser: '',
            shopDomain: ''
        };
    },

    computed: {
        routeCount() {
            return this.routes.length;
        },
        routeApiCredentialsIndex() {
            return 3;
        },
        routeSuccessIndex() {
            return 4;
        },
        routeErrorIndex() {
            return 5;
        }
    },

    created() {
        if (this.$route.query.show) {
            this.showModal = this.$route.query.show;
        }

        this.matchRouteWithIndex();
    },

    beforeRouteUpdate(to, from, next) {
        this.showModal = true;
        next();
        this.matchRouteWithIndex();
    },

    methods: {
        onConnect() {
            console.log('we want to connect here...');
            console.log('API-Key:', this.apiKey);
            console.log('API-User:', this.apiUser);
            console.log('Shop-Domain:', this.shopDomain);

            //this.navigateToRoute(this.routes[this.routeSuccessIndex]);
            this.navigateToRoute(this.routes[this.routeErrorIndex]);
        },

        onCloseModal() {
            this.showModal = false;
            this.$route.query.show = this.showModal;
            this.routeIndex = 0;
            this.routeIndexVisible = 0;
            this.apiKey = '';
            this.apiUser = '';
            this.shopDomain = '';
        },

        matchRouteWithIndex() {
            //check for current child route
            let currentRouteIndex = this.routes.findIndex((r) => {
                return r === this.$router.currentRoute.name;
            });

            if (currentRouteIndex !== -1) {
                if (currentRouteIndex > this.routeCountVisible - 1) {
                    this.routeIndexVisible = this.routeCountVisible -1;
                }else{
                    this.routeIndexVisible = currentRouteIndex;
                }

                this.routeIndex = currentRouteIndex;
                this.onChildRouteChanged();
            }
        },

        onChildRouteChanged() {
            this.buttonPreviousText = this.$tc('swag-migration.wizard.buttonPrev');

            //Handle next button text
            if (this.routeIndex === this.routeApiCredentialsIndex) {
                this.buttonNextText = this.$tc('swag-migration.wizard.buttonConnect');
            }else if(this.routeIndex === this.routeSuccessIndex) {
                this.buttonNextText = this.$tc('swag-migration.wizard.buttonFinish');
            }else if(this.routeIndex === this.routeErrorIndex) {
                this.buttonNextText = this.$tc('swag-migration.wizard.buttonPrev');
            }else{
                this.buttonNextText = this.$tc('swag-migration.wizard.buttonNext');
            }

            //Handle back button
            if (this.routeIndex === this.routeSuccessIndex || this.routeIndex === this.routeErrorIndex) {
                this.buttonPreviousVisible = false;
            }else{
                this.buttonPreviousVisible = this.routeIndex !== 0;
            }
        },

        navigateToRoute(routeName) {
            this.$router.push({name: routeName});
        },

        updateChildRoute() {
            this.navigateToRoute(this.routes[this.routeIndex]);
            this.onChildRouteChanged();
        },

        onPrevious() {
            if (this.routeIndex > 0) {
                this.routeIndex--;
                this.routeIndexVisible--;
                this.updateChildRoute();
            }
        },

        onNext() {
            if (this.routeIndex === this.routeApiCredentialsIndex) {
                //we clicked connect.
                this.onConnect();
                return;
            }else if(this.routeIndex === this.routeSuccessIndex) {
                //we clicked finish.
                this.onCloseModal();
                return;
            }else if(this.routeIndex === this.routeErrorIndex) {
                //we clicked Back
                this.navigateToRoute(this.routes[this.routeApiCredentialsIndex]);
                return;
            }

            if (this.routeIndex < this.routeCount - 1) {
                this.routeIndex++;
                this.routeIndexVisible++;
                this.updateChildRoute();
            }
        },

        onApiKeyChanged(value) {
            this.apiKey = value;
        },

        onApiUserChanged(value) {
            this.apiUser = value;
        },

        onShopDomainChanged(value) {
            this.shopDomain = value;
        }
    }
});
