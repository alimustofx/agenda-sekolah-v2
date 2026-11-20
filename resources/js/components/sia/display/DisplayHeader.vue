<script setup>
import { computed } from 'vue'
import DigitalClock from './DigitalClock.vue'

const props = defineProps({ school: Object, clock: Object })
const initials = computed(() => (props.school.name || 'SMA').split(' ').filter((w) => /^[A-Za-z0-9]/.test(w)).map((w) => (/^\d/.test(w) ? w : w[0])).join('').slice(0, 3).toUpperCase())
</script>

<template>
    <header class="tv-header">
        <div class="tv-brand">
            <div class="tv-logo"><img v-if="school.logo_url" :src="school.logo_url" alt="" /><template v-else>{{ initials }}</template></div>
            <div>
                <div class="tv-school">{{ school.name }}</div>
                <div class="tv-tagline">{{ school.tagline || 'School Information Center' }}</div>
            </div>
        </div>
        <DigitalClock :clock="clock" />
    </header>
</template>
