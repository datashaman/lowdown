<template>
    <div>
        <h1>Namespaces</h1>

        <ul>
            <li v-for="name in Object.keys(namespaces)" :key="name">
                <b-link :to="{ name: 'namespace', params: { name: name }}">{{ name }}</b-link>
            </li>
        </ul>
    </div>
</template>

<script>
import { mapState } from 'vuex'

export default {
    computed: {
        ...mapState([
            'namespaces'
        ])
    }
}
</script>
