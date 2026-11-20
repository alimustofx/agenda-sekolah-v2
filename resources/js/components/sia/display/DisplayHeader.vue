<script setup>
import { computed } from 'vue'
import { Cloud, CloudDrizzle, CloudFog, CloudLightning, CloudRain, CloudSun, Sun } from 'lucide-vue-next'
import DigitalClock from './DigitalClock.vue'

const props = defineProps({ school: Object, clock: Object, weather: Object })

const initials = computed(() => (props.school.name || 'SMA').split(' ').filter((w) => /^[A-Za-z0-9]/.test(w)).map((w) => (/^\d/.test(w) ? w : w[0])).join('').slice(0, 3).toUpperCase())
const icon = computed(() => {
    const c = props.weather?.code
    if (c === 0) return Sun
    if (c === 1 || c === 2) return CloudSun
    if (c === 45 || c === 48) return CloudFog
    if (c >= 51 && c <= 57) return CloudDrizzle
    if ((c >= 61 && c <= 67) || (c >= 80 && c <= 82)) return CloudRain
    if (c >= 95) return CloudLightning
    return Cloud
})
</script>

<template>
    <header class="tv-header">
        <div class="tv-brand">
            <div class="tv-logo"><img v-if="school.logo_url" :src="school.logo_url" alt="" /><template v-else>{{ initials }}</template></div>
            <div>
                <div class="tv-school">{{ school.name }}</div>
                <div class="tv-tagline">{{ school.tagline || 'Pusat Informasi Sekolah' }}</div>
            </div>
        </div>
        <div v-if="weather" class="tv-weather">
            <component :is="icon" :size="44" :stroke-width="1.75" />
            <div>
                <div class="tv-weather__temp tv-num">{{ weather.temperature }}°C</div>
                <div class="tv-weather__cond">{{ weather.condition }}</div>
            </div>
        </div>
        <DigitalClock :clock="clock" />
    </header>
</template>
