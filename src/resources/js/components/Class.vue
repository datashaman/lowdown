<template>
    <div class="class">
        <h1>{{ shortName }}</h1>

        <p>
            {{ nsClass._type }} <strong>{{ shortName }}</strong>
            <span v-if="nsClass.parentClass || nsClass.parentClassName">
                extends
                <template v-if="nsClass.parentClass">
                    <b-link :to="{ name: 'class', params: { ns: nsClass.parentClass.ns, name: nsClass.parentClass.name }}">
                        {{ nsClass.parentClass.shortName }}
                    </b-link>
                </template>
                <template v-else>
                    <code>{{ nsClass.parentClassName }}</code>
                </template>
            </span>
            <span v-if="nsClass.interfaces.length">
                implements
                <template v-for="i in nsClass.interfaces">
                    <template v-if="nsClass.ns === i.ns">
                        <b-link :to="{ name: 'class', params: { ns: i.ns, name: i.name }}">
                            {{ i.shortName }}
                        </b-link>
                    </template>
                    <template v-else>
                        <code>{{ i.name }}</code>
                    </template>
                </template>
            </span>
        </p>

        <div v-if="nsClass.traits.length">
            <h2 class="class_header">Traits</h2>

            <b-table borderless :fields="fields.traits" :items="nsClass.traits" thead-class="hide">
            </b-table>
        </div>

        <div v-if="nsClass.properties.length">
            <h2 class="class_header p-2">Properties</h2>

            <b-table borderless small :fields="fields.properties" :items="nsClass.properties" thead-class="hide">
                <template slot="table-colgroup">
                    <col width="25%"></col>
                </template>
                <template slot="meta" slot-scope="data">
                    {{ abstract(data.item, nsClass._type) }}
                    {{ scope(data.item) }}
                    {{ visibility(data.item) }}
                    {{ data.item.type }}
                </template>
                <template slot="name" slot-scope="data">
                    <div class="lolight">${{ data.value }}</div>
                    <div v-html="data.item.summary"></div>
                </template>
            </b-table>
        </div>

        <div v-if="nsClass.methods.length">
            <h2 class="class_header p-2">Methods</h2>

            <b-table borderless :fields="fields.methods" :items="nsClass.methods" thead-class="hide" tbody-tr-class="tr-underline">
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
                <div v-for="m in nsClass.methods">
                    <div :id="m.name" class="details_signature p-2 lolight">
                        {{ abstract(m, nsClass._type) }}
                        {{ scope(m) }}
                        {{ visibility(m) }}
                        {{ m.returnType }}
                        {{ generateSignature(m) }}
                    </div>

                    <div v-if="m.summary" class="px-4 pt-4" v-html="m.summary"></div>
                    <div v-if="m.description" class="px-4 pt-4" v-html="m.description"></div>

                    <div class="px-4 pt-2">
                        <h3 class="details_header">Parameters</h3>

                        <b-table borderless small :fields="fields.parameters" :items="m.parameters" thead-class="hide" tbody-tr-class="tr-underline">
                            <template slot="table-colgroup">
                                <col width="25%"></col>
                            </template>
                            <template slot="name" slot-scope="data">
                                <span class="lolight">${{ data.value }}</span>
                            </template>
                        </b-table>
                    </div>

                    <div v-if="m.returnType" class="px-4">
                        <h3 class="details_header">Return Value</h3>

                        <b-table borderless small :items="[{type: m.returnType}]" thead-class="hide">
                        </b-table>
                    </div>

                    <template v-if="m.example">
                        <h3>Example</h3>

                        <pre class="lolight">{{ m.example }}</pre>
                    </template>

                    <template v-if="m.output">
                        <h3>Output</h3>

                        <pre>{{ m.output }}</pre>
                    </template>

                    <div v-if="m.gist">
                        <b-input-group>
                            <b-form-input class="flex-grow-1" type="text" :value="'melody run ' + m.gist" readonly />
                            <b-input-group-append>
                                <b-button variant="success" class="btn-copy" v-clipboard:copy="'melody run ' + m.gist" @click="confirmCopy">
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
        </div>
    </div>
</template>

<script>
import { functionsMixin } from '../mixins/functions'
import lolight from 'lolight'

export default {
    mixins: [
        functionsMixin
    ],
    props: [
        'ns',
        'shortName'
    ],
    data() {
        return {
            fields: {
                methods: [
                    'returnType',
                    'name',
                ],
                parameters: [
                    'type',
                    'name',
                ],
                properties: [
                    'meta',
                    'name',
                ],
                traits: [
                    'name',
                    'summary',
                ]
            }
        }
    },
    computed: {
        nsClass: function () {
            return this.$store.getters.nsClass(this.ns, this.shortName)
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
