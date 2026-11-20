<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue'
import InformationPanel from './InformationPanel.vue'
import { useYoutubePlayer } from '../../../composables/useYoutubePlayer'

/**
 * Panel kanan TV: video YouTube dan panel informasi bergantian.
 * Alur: video selesai -> informasi (durasi jeda) -> video berikutnya. Tidak pernah menampilkan player kosong.
 */
const props = defineProps({ data: Object })

const school = computed(() => props.data.school)
const yt = computed(() => props.data.youtube.settings)
const display = computed(() => props.data.display)

// ------------------------------------------------------------------ informasi
const infoItems = computed(() => {
    const d = props.data
    const modes = d.display.panel_modes || []
    const urgent = []
    const base = []
    if (modes.includes('announcement')) d.announcements.forEach((a) => (a.priority === 'urgent' ? urgent : base).push({ ...a, kind: 'announcement' }))
    if (modes.includes('activity')) d.activities.forEach((a) => base.push({ ...a, kind: 'activity' }))
    if (modes.includes('school_info') && d.display.school_info) {
        d.display.school_info.split('\n').map((t) => t.trim()).filter(Boolean).forEach((t, i) => base.push({ kind: 'school_info', id: 'si' + i, text: t }))
    }
    if (modes.includes('weather') && d.weather) base.push({ ...d.weather, kind: 'weather', id: 'weather' })
    if (!urgent.length) return base
    if (!base.length) return urgent
    // Informasi mendesak disisipkan di antara konten lain sehingga tayang lebih sering.
    const out = [urgent[0]]
    base.forEach((b, i) => out.push(b, urgent[i % urgent.length]))
    return out
})
const infoIdx = ref(0)
const infoItem = computed(() => (infoItems.value.length ? infoItems.value[infoIdx.value % infoItems.value.length] : { kind: 'welcome', id: 'welcome' }))
const infoKey = computed(() => `${infoItem.value.kind}-${infoItem.value.id ?? ''}-${infoItem.value.title ?? infoItem.value.text ?? ''}`)

// ------------------------------------------------------------------ antrean video
const mode = ref('info') // 'info' | 'video'
const queue = ref([])
const cursor = ref(0)
const current = ref(null)
const cooldown = ref(false)
const lastError = ref('-')
const debug = typeof window !== 'undefined' && new URLSearchParams(window.location.search).has('debug')
const finished = ref(false)
const resolved = reactive({})
let errorStreak = 0
let intermissionTimer = null
let cooldownTimer = null
let rotateTimer = null
let initState = 0 // 0 belum, 1 sedang membuat pemutar, 2 siap, 3 gagal

const shuffle = (arr) => {
    const a = [...arr]
    for (let i = a.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1))
        ;[a[i], a[j]] = [a[j], a[i]]
    }
    return a
}

function buildQueue() {
    const sources = props.data.youtube.sources
    const m = yt.value.mode
    let list = sources
    if (m === 'queue') list = sources.filter((s) => s.type === 'video')
    else if (m === 'playlist') list = sources.filter((s) => s.type === 'playlist')

    const q = []
    for (const s of list) {
        if (s.type === 'video' && s.video_id) q.push({ id: s.video_id, title: s.title })
        else if (s.type === 'playlist' && s.playlist_id) {
            const ids = s.videos?.length ? s.videos : resolved[s.playlist_id]
            if (ids?.length) ids.forEach((v) => q.push({ id: v, title: s.title }))
            else q.push({ playlist: s.playlist_id, title: s.title })
        }
    }
    const out = m === 'single' ? q.slice(0, 1) : q
    return yt.value.shuffle ? shuffle(out) : out
}

// Hanya susun ulang bila sumber/mode benar-benar berubah; pembaruan agenda tidak menyentuh pemutar.
const queueSig = computed(() => JSON.stringify([yt.value.mode, yt.value.shuffle, props.data.youtube.sources.map((s) => [s.id, s.type, s.video_id, s.playlist_id, s.videos?.length])]))
function rebuild() {
    const curId = current.value?.id
    queue.value = buildQueue()
    const k = curId ? queue.value.findIndex((x) => x.id === curId) : -1
    cursor.value = k >= 0 ? k : Math.min(cursor.value, Math.max(0, queue.value.length - 1))
    finished.value = false
}

const videoWanted = computed(() => display.value.video_panel && yt.value.enabled && yt.value.autoplay && queue.value.length > 0)

const player = useYoutubePlayer('tv-yt-player', {
    playing: () => {
        errorStreak = 0
        mode.value = 'video'
    },
    ended: () => handleEnded(),
    failed: (code) => handleFailed(code),
})
const audio = () => ({ muted: yt.value.muted, volume: yt.value.volume })

async function startPlayback() {
    clearTimeout(intermissionTimer)
    if (!videoWanted.value || cooldown.value || finished.value || !player.available.value) return
    let entry = queue.value[cursor.value]
    if (!entry) return
    if (entry.playlist) {
        const ids = await player.resolvePlaylist(entry.playlist)
        if (!ids.length) return handleFailed('playlist-kosong')
        resolved[entry.playlist] = ids
        queue.value = buildQueue()
        entry = queue.value[cursor.value]
        if (!entry || entry.playlist) return handleFailed('playlist-gagal')
    }
    current.value = entry
    player.play(entry.id, audio())
}

function advance() {
    cursor.value++
    if (cursor.value >= queue.value.length) {
        if (!yt.value.loop) {
            finished.value = true
            mode.value = 'info'
            return
        }
        cursor.value = 0
        if (yt.value.shuffle) queue.value = shuffle(queue.value)
    }
    startPlayback()
}

function handleEnded() {
    errorStreak = 0
    if (yt.value.intermission && display.value.information_panel) {
        infoIdx.value++
        mode.value = 'info'
        intermissionTimer = setTimeout(advance, yt.value.intermission_duration * 1000)
    } else {
        advance()
    }
}

// Video bermasalah: lewati. Jika semua gagal, istirahat 5 menit dan tampilkan informasi.
function handleFailed(code) {
    lastError.value = code ?? 'tidak-diketahui'
    if (code === 'player-belum-siap') return // bukan kegagalan video: tunggu pemutar siap
    errorStreak++
    if (errorStreak >= Math.max(2, queue.value.length)) {
        errorStreak = 0
        cooldown.value = true
        mode.value = 'info'
        clearTimeout(cooldownTimer)
        cooldownTimer = setTimeout(() => {
            cooldown.value = false
            startPlayback()
        }, 5 * 60 * 1000)
        return
    }
    advance()
}

async function maybeStart() {
    if (!videoWanted.value || initState === 1 || initState === 3) return
    if (initState === 2) return startPlayback()
    initState = 1
    const ok = await player.init()
    initState = ok ? 2 : 3
    if (ok) startPlayback()
}

// ------------------------------------------------------------------ siklus hidup
watch(queueSig, () => {
    rebuild()
    if (!videoWanted.value) {
        mode.value = 'info'
        player.stop()
    } else if (mode.value !== 'video' && !intermissionTimer) maybeStart()
})
watch(videoWanted, (v) => {
    if (v) maybeStart()
    else {
        mode.value = 'info'
        player.stop()
    }
})
watch(() => [yt.value.muted, yt.value.volume], () => player.setAudio(yt.value.muted, yt.value.volume))
watch(mode, (m) => {
    clearInterval(rotateTimer)
    // Selama informasi tampil lama, rotasi konten tiap 25 detik.
    if (m === 'info') rotateTimer = setInterval(() => infoIdx.value++, 25000)
}, { immediate: true })

const host = ref(null)
onMounted(() => {
    const target = document.createElement('div')
    target.id = 'tv-yt-player'
    host.value.appendChild(target)
    rebuild()
    maybeStart()
})
onBeforeUnmount(() => {
    clearTimeout(intermissionTimer)
    clearTimeout(cooldownTimer)
    clearInterval(rotateTimer)
})
</script>

<template>
    <section class="tv-panel" aria-label="Media dan informasi">
        <div class="tv-panel__layer" :data-hidden="mode !== 'video'">
            <div class="tv-video">
                <div ref="host" class="tv-video__frame" />
                <div class="tv-video__caption">
                    <div class="tv-video__label">Sedang diputar</div>
                    <div class="tv-video__title">{{ current?.title }}</div>
                </div>
            </div>
        </div>
        <div class="tv-panel__layer" :data-hidden="mode !== 'info'">
            <Transition name="tv-fade" mode="out-in">
                <InformationPanel :key="infoKey" :item="infoItem" :school="school" />
            </Transition>
        </div>
        <div v-if="debug" style="position: absolute; left: 12px; top: 12px; z-index: 5; background: rgba(0, 0, 0, 0.8); color: #fff; font: 15px/1.4 monospace; padding: 6px 10px; border-radius: 6px">
            antrean {{ queue.length }} | mode {{ mode }} | error {{ lastError }} | api {{ player.available.value ? 'ok' : 'gagal' }} | siap {{ player.ready.value ? 'ya' : 'tidak' }}
        </div>
    </section>
</template>
