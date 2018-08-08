import { Component } from 'src/core/shopware';
import template from './swag-migration-wizard.html.twig';
import './swag-migration-wizard.less';

Component.register('swag-migration-wizard', {
    template,

    data() {
        return {
            showModal: true,
            buttonPreviousVisible: true,
            buttonNextVisible: true,
            buttonPreviousText: this.$tc('sw-migration.wizard.buttonPrev'),
            buttonNextText: this.$tc('sw-migration.wizard.buttonNext'),
            routes: [
                'sw.migration.index.wizard1',
                'sw.migration.index.wizard2',
                'sw.migration.index.wizard3',
                'sw.migration.index.wizard4'
            ],
            routeIndex: 0
        };
    },

    computed: {
        routeCount() {
            return this.routes.length;
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
        },

        onCloseModal() {
            this.showModal = false;
            this.$route.query.show = this.showModal;
            this.routeIndex = 0;
        },

        matchRouteWithIndex() {
            //check for current child route
            let currentRouteIndex = this.routes.findIndex((r) => {
                return r === this.$router.currentRoute.name;
            });

            if (currentRouteIndex !== -1) {
                this.routeIndex = currentRouteIndex;
                this.onChildRouteChanged();
            }
        },

        onChildRouteChanged() {
            this.buttonPreviousVisible = this.routeIndex !== 0;
            this.buttonPreviousText = this.$tc('sw-migration.wizard.buttonPrev');

            if (this.routeIndex === this.routeCount - 1) {
                this.buttonNextText = this.$tc('sw-migration.wizard.buttonConnect');
            }else{
                this.buttonNextText = this.$tc('sw-migration.wizard.buttonNext');
            }
        },

        updateChildRoute() {
            this.$router.push({name: this.routes[this.routeIndex]});
            this.onChildRouteChanged();
        },

        onPrevious() {
            if (this.routeIndex > 0) {
                this.routeIndex--;
                this.updateChildRoute();
            }
        },

        onNext() {
            if (this.routeIndex === this.routeCount - 1) {
                //we clicked connect.
                this.onConnect();
                return;
            }

            if (this.routeIndex < this.routeCount - 1) {
                this.routeIndex++;
                this.updateChildRoute();
            }
        }
    }
});
