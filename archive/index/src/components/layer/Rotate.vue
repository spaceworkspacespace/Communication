<template>
    <div
        :class="'x-absolute-center ' + (this.background? 'x-layer-rotate-background': 'x-layer-rotate')"
    >
        <span class="fa fa-cog fa-spin"></span>
        {{text}}
    </div>
</template>

<script>
export default {
    props: ["text", "background"]
}
</script>

<style >
.x-layer-rotate {
    font-size: 32px;
    background-color: transparent;
}
.x-layer-rotate-background {
    width: 360px;
    line-height: 240px;
    text-align: center;
    font-size: 32px;
    border: 1px solid var(--gray);
    border-radius: 0.25rem;
    background-color: white;
}
</style>