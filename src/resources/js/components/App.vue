<template>
    <div>
        <b-navbar toggleable="lg" type="dark" variant="logic">
            <b-navbar-brand :to="{ name: 'home' }">Logic</b-navbar-brand>
        </b-navbar>

        <router-view class="p-3"></router-view>
    </div>
</template>
