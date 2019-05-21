<template>
    <div class="x-mask-container">
        <slot></slot>
    </div>
</template>
<script>
export default {
    
}
</script>


<style>
.x-mask-container {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(3, 3, 3, 0.2);
}
</style>
