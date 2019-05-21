<template>
    <div id="app">
        <router-view/>
        <layer/>
    </div>
</template>

<script>
import Layer from '@/components/Layer'
export default {
    name: 'App',
    // beforeMount: function () {
    //     this.$store.dispatch("getUser");
    // },
    components: {
        Layer
    }
}
</script>

<style>
#app {
    /* font-family: 'Avenir', Helvetica, Arial, sans-serif; */
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
    /* text-align: center; */
    color: #2c3e50;
    /* margin-top: 60px; */
    background-color: "white";
    display: block;
    height: 100vh;
    width: 100vw;
    overflow: auto;
}
</style>
