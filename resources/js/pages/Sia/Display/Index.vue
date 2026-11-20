<script setup>
import '../../../../css/sia.css'
import '../../../../css/tv.css'
import { Head } from '@inertiajs/vue3'
import BareLayout from '../../../components/sia/layout/BareLayout.vue'
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { useDisplayData } from '../../../composables/useDisplayData'
import { useClock } from '../../../composables/useClock'
import DisplayHeader from '../../../components/sia/display/DisplayHeader.vue'
import AgendaDisplay from '../../../components/sia/display/AgendaDisplay.vue'
import MediaPanel from '../../../components/sia/display/MediaPanel.vue'
import RunningText from '../../../components/sia/display/RunningText.vue'

defineOptions({ layout: BareLayout })
const props = defineProps({ initial: Object })
const { data, offset, stale } = useDisplayData(props.initial)
const clock = useClock(offset)

// Kanvas acuan 1920×1080 diskalakan proporsional ke layar mana pun.
const view = ref({ s: 1, x: 0, y: 0 })
function fit() {
    const s = Math.min(window.innerWidth / 1920, window.innerHeight / 1080)
    view.value = { s, x: (window.innerWidth - 1920 * s) / 2, y: (window.innerHeight - 1080 * s) / 2 }
}

// Jaga layar tetap menyala dan muat ulang berkala (hanya bila koneksi sehat) agar stabil berhari-hari.
let wake = null
let reloadTimer = null
async function requestWake() {
    try { wake = await navigator.wakeLock?.request('screen') } catch (e) { /* tidak didukung */ }
}
function scheduleReload() {
    reloadTimer = setTimeout(() => (stale.value ? scheduleReload() : window.location.reload()), 6 * 3600 * 1000)
}
const onVisible = () => document.visibilityState === 'visible' && requestWake()

onMounted(() => {
    fit()
    window.addEventListener('resize', fit)
    document.addEventListener('visibilitychange', onVisible)
    requestWake()
    scheduleReload()
})
onBeforeUnmount(() => {
    window.removeEventListener('resize', fit)
    document.removeEventListener('visibilitychange', onVisible)
    clearTimeout(reloadTimer)
    wake?.release?.()
})

const showPanel = computed(() => data.value.display.video_panel)
const showTicker = computed(() => data.value.display.running_text)
</script>

<template>
    <Head title="TV Display" />
    <div class="sia tv-viewport">
        <div class="tv-stage" :style="{ transform: `translate(${view.x}px, ${view.y}px) scale(${view.s})`, gridTemplateRows: showTicker ? '100px 1fr 65px' : '100px 1fr' }">
            <DisplayHeader :school="data.school" :clock="clock" />
            <main class="tv-main" :class="{ 'tv-main--solo': !showPanel }">
                <AgendaDisplay :agenda="data.agenda" :settings="{ ...data.display, title: data.display.title }" :clock="clock" />
                <MediaPanel v-if="showPanel" :data="data" />
            </main>
            <RunningText v-if="showTicker" :texts="data.running_texts" :school="data.school" />
            <p v-if="stale" class="tv-offline">Koneksi terputus. Menampilkan data terakhir.</p>
        </div>
    </div>
</template>
