<template>
    <div>
        <h1>{{ name }}</h1>

        <ul>
            <li>
                <h2>Classes</h2>

                <b-table borderless width="100%" :fields="tableFields.classes" :items="classes" thead-class="hide" tbody-tr-class="tr-underline">
                    <template slot="table-colgroup">
                        <col width="25%"></col>
                    </template>

                    <template slot="shortName" slot-scope="data">
                        <b-link :to="{ name: 'class', params: { ns: name, shortName: data.value }}">{{ data.value }}</b-link>
                    </template>
                </b-table>
            </li>

            <li v-if="interfaces.length">
                <h2>Interfaces</h2>

                <b-table borderless :fields="tableFields.interfaces" :items="interfaces" thead-class="hide" tbody-tr-class="tr-underline">
                    <template slot="table-colgroup">
                        <col width="25%"></col>
                    </template>
                    <template slot="shortName" slot-scope="data">
                        <b-link :to="{ name: 'class', params: { ns: name, shortName: data.value }}">{{ data.value }}</b-link>
                    </template>
                </b-table>
            </li>

            <li v-if="traits.length">
                <h2>Traits</h2>

                <b-table borderless :fields="tableFields.traits" :items="traits" thead-class="hide" tbody-tr-class="tr-underline">
                    <template slot="table-colgroup">
                        <col width="25%"></col>
                    </template>
                    <template slot="shortName" slot-scope="data">
                        <b-link :to="{ name: 'class', params: { ns: name, shortName: data.value }}">{{ data.value }}</b-link>
                    </template>
                </b-table>
            </li>

            <li v-if="functions.length">
                <h2>Functions</h2>

                <b-table borderless :fields="tableFields.functions" :items="functions" thead-class="hide" tbody-tr-class="tr-underline">
                    <template slot="table-colgroup">
                        <col width="25%"></col>
                    </template>
                    <template slot="name" slot-scope="data">
                        <div v-html="generateSignature(data.item, true)"></div>
                        <div v-html="data.item.summary"></div>
                    </template>
                </b-table>

                <h2 class="class_header p-2">Details</h2>

                <div class="details">
                    <div v-for="f in functions">
                        <div :id="f.shortName" class="details_signature p-2 lolight">
                            {{ f.returnType }}
                            {{ generateSignature(f) }}
                        </div>

                        <div v-if="f.summary" class="px-2 py-4" v-html="f.summary"></div>
                        <div v-if="f.description" class="px-2 py-2" v-html="f.description"></div>

                        <div v-if="f.parameters.length" class="px-4 pt-2">
                            <h3 class="details_header">Parameters</h3>

                            <b-table borderless small :fields="tableFields.parameters" :items="f.parameters" thead-class="hide" tbody-tr-class="tr-underline">
                                <template slot="table-colgroup">
                                    <col width="25%"></col>
                                </template>
                                <template slot="name" slot-scope="data">
                                    <div class="lolight">${{ data.value }}</div>
                                    <div v-if="data.item.description" v-html="data.item.description"></div>
                                </template>
                            </b-table>
                        </div>

                        <div v-if="f.returnType" class="px-4">
                            <h3 class="details_header">Return Value</h3>

                            <b-table borderless small :items="[{type: f.returnType}]" thead-class="hide">
                            </b-table>
                        </div>

                        <div v-if="f.example" class="px-2">
                            <h3 class="details_block_header">Example</h3>

                            <pre class="lolight">{{ f.example }}</pre>
                        </div>

                        <div v-if="f.output" class="px-2">
                            <h3 class="details_block_header">Output</h3>

                            <pre>{{ f.output }}</pre>
                        </div>

                        <div v-if="f.gist" class="px-2 pb-4">
                            <b-input-group>
                                <b-form-input class="flex-grow-1" type="text" :value="'melody run ' + f.gist" readonly />
                                <b-input-group-append>
                                    <b-button variant="success" class="btn-copy" v-clipboard:copy="'melody run ' + f.gist" @click="confirmCopy">
                                        <font-awesome-icon icon="copy"></font-awesome-icon>
                                    </b-button>
                                    <b-button variant="outline-info" href="http://melody.sensiolabs.org">
                                        <font-awesome-icon icon="question"></font-awesome-icon>
                                    </b-button>
                                </b-input-group-append>
                            </b-input-group>
                        </div>
                    </div>
                </div>
            </li>

        </ul>
    </div>
</template>

<script>
import lolight from 'lolight'
import { mapGetters, mapState } from 'vuex'
import { functionsMixin } from '../mixins/functions'

export default {
    mixins: [
        functionsMixin
    ],
    props: [
        'name'
    ],
    data() {
        return {
            fields: [
                'returnType',
                'name',
            ]
        }
    },
    computed: {
        ...mapState([
            'tableFields',
        ]),
        classes: function () {
            return this.$store.getters.nsClasses(this.name)
        },
        functions: function () {
            return this.$store.getters.nsFunctions(this.name)
        },
        interfaces: function () {
            return this.$store.getters.nsInterfaces(this.name)
        },
        traits: function () {
            return this.$store.getters.nsTraits(this.name)
        }
    },
    methods: {
        confirmCopy() {
            this.$bvToast.toast('Copied command-line!', {
                title: 'Documentation',
                autoHideDelay: 500,
                isStatus: true
            })
        }
    },
    mounted() {
        lolight()
    }
}
</script>
