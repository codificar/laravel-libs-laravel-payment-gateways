window.vue = require('vue');

require('lodash');

import Vue from 'vue';

import SettingsGateways from './pages/settings_gateways.vue';

import VueSweetalert2 from 'vue-sweetalert2';

import JunoAddCard from './pages/juno_add_card.vue';

Vue.use(VueSweetalert2);

import Loading from 'vue-loading-overlay';
import 'vue-loading-overlay/dist/vue-loading.css';
Vue.use(Loading);
Vue.component('loading', Loading);

import VueTheMask from 'vue-the-mask';
Vue.use(VueTheMask)

import ToggleButton from 'vue-js-toggle-button'
Vue.use(ToggleButton)

//Allows localization using trans()
Vue.prototype.trans = (key) => {
    return _.get(window.lang, key, key);
};
//Tells if an JSON parsed object is empty
Vue.prototype.isEmpty = (obj) => {
    return _.isEmpty(obj);
};

//Main vue instance
new Vue({
    el: '#payment-gateways',

    data: {
    },

    components: {
        settingsgateways: SettingsGateways,
        junoaddcard: JunoAddCard
    },

    created: function () {
    }
});