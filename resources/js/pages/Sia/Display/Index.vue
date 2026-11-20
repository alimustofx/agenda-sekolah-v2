<script setup>
import '../../../../css/sia.css'
import '../../../../css/tv.css'
import { Head } from '@inertiajs/vue3'
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import BareLayout from '../../../components/sia/layout/BareLayout.vue'
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

// Lebar acuan 1920 diskalakan ke lebar layar; tinggi kanvas mengikuti rasio layar sehingga
// tidak ada sisa ruang di atas/bawah. Pada layar sangat lebar, skala mengikuti tinggi (min. 880).
const view = ref({ s: 1, w: 1920, h: 1080 })
function fit() {
    let s = window.innerWidth / 1920
    let w = 1920
    let h = window.innerHeight / s
    if (h < 880) {
        s = window.innerHeight / 880
        w = window.innerWidth / s
        h = 880
    }
    view.value = { s, w, h }
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
        <div class="tv-stage" :style="{ width: view.w + 'px', height: view.h + 'px', transform: `scale(${view.s})`, gridTemplateRows: showTicker ? '104px minmax(0, 1fr) 64px' : '104px minmax(0, 1fr)' }">
            <DisplayHeader :school="data.school" :clock="clock" :weather="data.weather" />
            <main class="tv-main" :class="{ 'tv-main--solo': !showPanel }">
                <AgendaDisplay :agenda="data.agenda" :settings="{ ...data.display, title: data.display.title }" :clock="clock" />
                <MediaPanel v-if="showPanel" :data="data" />
            </main>
            <RunningText v-if="showTicker" :texts="data.running_texts" :school="data.school" />
            <p v-if="stale" class="tv-offline">Koneksi terputus. Menampilkan data terakhir.</p>
        </div>
    </div>
</template>
