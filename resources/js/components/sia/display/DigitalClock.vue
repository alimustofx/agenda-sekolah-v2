<script setup>
defineProps({ clock: Object })
</script>

<template>
    <div class="tv-clockbox">
        <div class="tv-date">{{ clock.date }}</div>
        <div class="tv-clock tv-num">{{ clock.time }} WIB</div>
    </div>
</template>
