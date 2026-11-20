<script setup>
defineProps({ clock: Object })
</script>

<template>
    <div class="tv-clockbox">
        <div class="tv-date">{{ clock.date }}</div>
        <div class="tv-clock tv-num">{{ clock.hm }}<small>{{ clock.ss }} WIB</small></div>
    </div>
</template>
