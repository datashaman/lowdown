import FuzzySearch from 'fuzzy-search'
import { library } from '@fortawesome/fontawesome-svg-core'
import Vue from 'vue'
import VueClipboard from 'vue-clipboard2'
import VueRouter from 'vue-router'
import Vuex from 'vuex'

import {
    faArrowUp,
    faCopy,
    faQuestion
} from '@fortawesome/free-solid-svg-icons'

import {
    FontAwesomeIcon,
    FontAwesomeLayers,
    FontAwesomeLayersText
} from '@fortawesome/vue-fontawesome'

library.add(
    faArrowUp,
    faCopy,
    faQuestion
)

Vue.component('font-awesome-icon', FontAwesomeIcon)
Vue.component('font-awesome-layers', FontAwesomeLayers)
Vue.component('font-awesome-layers-text', FontAwesomeLayersText)

Vue.config.productionTip = false

import BButton from 'bootstrap-vue/es/components/button/button'
import BCard from 'bootstrap-vue/es/components/card/card'
import BCardBody from 'bootstrap-vue/es/components/card/card-body'
import BCardText from 'bootstrap-vue/es/components/card/card-text'
import BCol from 'bootstrap-vue/es/components/layout/col'
import BCollapse from 'bootstrap-vue/es/components/collapse/collapse'
import BContainer from 'bootstrap-vue/es/components/layout/container'
import BFormInput from 'bootstrap-vue/es/components/form-input/form-input'
import BInputGroup from 'bootstrap-vue/es/components/input-group/input-group'
import BInputGroupAppend from 'bootstrap-vue/es/components/input-group/input-group-append'
import BLink from 'bootstrap-vue/es/components/link/link'
import BNav from 'bootstrap-vue/es/components/nav/nav'
import BNavbarBrand from 'bootstrap-vue/es/components/navbar/navbar-brand'
import BNavbarNav from 'bootstrap-vue/es/components/navbar/navbar-nav'
import BNavbarToggle from 'bootstrap-vue/es/components/navbar/navbar-toggle'
import BNavbar from 'bootstrap-vue/es/components/navbar/navbar'
import BNavItem from 'bootstrap-vue/es/components/nav/nav-item'
import BRow from 'bootstrap-vue/es/components/layout/row'
import BTable from 'bootstrap-vue/es/components/table/table'

import Toast from 'bootstrap-vue/es/components/toast'
Vue.use(Toast)

Vue.component('b-button', BButton)
Vue.component('b-card', BCard)
Vue.component('b-card-body', BCardBody)
Vue.component('b-card-text', BCardText)
Vue.component('b-col', BCol)
Vue.component('b-collapse', BCollapse)
Vue.component('b-container', BContainer)
Vue.component('b-form-input', BFormInput)
Vue.component('b-input-group', BInputGroup)
Vue.component('b-input-group-append', BInputGroupAppend)
Vue.component('b-link', BLink)
Vue.component('b-nav', BNav)
Vue.component('b-navbar', BNavbar)
Vue.component('b-navbar-brand', BNavbarBrand)
Vue.component('b-navbar-nav', BNavbarNav)
Vue.component('b-navbar-toggle', BNavbarToggle)
Vue.component('b-nav-item', BNavItem)
Vue.component('b-row', BRow)
Vue.component('b-table', BTable)

require('../sass/app.scss')

import namespaces from '../json/namespaces.json'

Vue.use(VueClipboard)
Vue.use(VueRouter)
Vue.use(Vuex)

const files = require.context('./components', true, /\.vue$/)

files.keys().map(
    key => Vue.component(
        key.split('/').pop().split('.')[0],
        files(key).default
    )
)

const routes = [
    {
        name: 'namespace',
        path: '/ns/:name',
        component: Vue.component('Namespace'),
        props: true,
    },
    {
        name: 'class',
        path: '/cl/:ns/:shortName',
        component: Vue.component('Class'),
        props: true,
    },
    {
        name: 'home',
        path: '/',
        component: Vue.component('Namespaces'),
    }
]

const router = new VueRouter({
    mode: 'history',
    routes
})

function sortByShortName(a, b) {
    if (a.shortName < b.shortName) {
        return -1;
    }
    if (a.shortName > b.shortName) {
        return 1;
    }

    return 0;
}

const store = new Vuex.Store({
    state: {
		namespaces: namespaces,
        query: '',
		tableFields: {
            classes: [
                'shortName',
                'summary',
            ],
            functions: [
                'returnType',
                'name',
            ],
            interfaces: [
                'shortName',
                'summary',
            ],
            parameters: [
                'type',
                'name',
            ],
            traits: [
                'shortName',
                'summary',
            ],
        }
    },
    getters: {
        nsClass: state => (ns, shortName) => {
            const results = state
                .namespaces[ns]
                .filter(e => e.shortName === shortName)

            return results[0]
        },
        nsClasses: state => ns => {
            return state
                .namespaces[ns]
                .filter(e => e._type === 'class')
                .sort(sortByShortName)
        },
        nsFunctions: state => ns => {
            return state
                .namespaces[ns]
                .filter(e => e._type === 'function')
                .sort(sortByShortName)
        },
        nsInterfaces: state => ns => {
            return state
                .namespaces[ns]
                .filter(e => e._type === 'interface')
                .sort(sortByShortName)
        },
        nsTraits: state => ns => {
            return state
                .namespaces[ns]
                .filter(e => e._type === 'trait')
                .sort(sortByShortName)
        }
    },
    mutations: {
        filter (state, query) {
            state.query = query
        }
    }
})

const app = new Vue({
    el: '#app',
    router,
    store,
    render: h => h(Vue.component('App'))
})

if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/sw.js')
}
