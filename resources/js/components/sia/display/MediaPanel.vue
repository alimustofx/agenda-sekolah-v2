<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue'
import InformationPanel from './InformationPanel.vue'
import { useYoutubePlayer } from '../../../composables/useYoutubePlayer'

/**
 * Rel kanan TV. Jendela video 16:9 di atas, pengumuman/informasi bergantian di bawah.
 * Video selesai -> jendela menampilkan layar identitas sekolah selama jeda -> video berikutnya.
 * Jendela tidak pernah kosong dan informasi selalu terlihat.
 */
const props = defineProps({ data: Object })

const ROTATE_SEC = 15
const school = computed(() => props.data.school)
const yt = computed(() => props.data.youtube.settings)
const display = computed(() => props.data.display)
const initials = computed(() => (school.value.name || 'SMA').split(' ').filter((w) => /^[A-Za-z0-9]/.test(w)).map((w) => (/^\d/.test(w) ? w : w[0])).join('').slice(0, 3).toUpperCase())

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
    if (!urgent.length) return base
    if (!base.length) return urgent
    // Informasi mendesak disisipkan di antara konten lain sehingga tayang lebih sering.
    const out = [urgent[0]]
    base.forEach((b, i) => out.push(b, urgent[i % urgent.length]))
    return out
})
const infoIdx = ref(0)
const infoTotal = computed(() => infoItems.value.length)
const infoPos = computed(() => (infoTotal.value ? infoIdx.value % infoTotal.value : 0))
const infoItem = computed(() => (infoTotal.value ? infoItems.value[infoPos.value] : { kind: 'welcome', id: 'welcome' }))
const infoKey = computed(() => `${infoItem.value.kind}-${infoItem.value.id ?? ''}-${infoItem.value.title ?? infoItem.value.text ?? ''}-${infoIdx.value}`)

// ------------------------------------------------------------------ antrean video
const mode = ref('slate') // 'slate' | 'video'
const queue = ref([])
const cursor = ref(0)
const current = ref(null)
const cooldown = ref(false)
const finished = ref(false)
const lastError = ref('-')
const query = typeof window !== 'undefined' ? new URLSearchParams(window.location.search) : new URLSearchParams()
const debug = query.has('debug')
const preview = query.has('preview') // pratinjau di dashboard: selalu dibisukan
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

// Hanya susun ulang bila sumber/mode berubah; pembaruan agenda tidak menyentuh pemutar.
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
const audio = () => ({ muted: preview || yt.value.muted, volume: yt.value.volume })

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
            mode.value = 'slate'
            return
        }
        cursor.value = 0
        if (yt.value.shuffle) queue.value = shuffle(queue.value)
    }
    startPlayback()
}

function handleEnded() {
    errorStreak = 0
    mode.value = 'slate'
    if (yt.value.intermission) intermissionTimer = setTimeout(advance, yt.value.intermission_duration * 1000)
    else advance()
}

// Video bermasalah: lewati. Jika semua gagal, istirahat 5 menit (jendela menampilkan identitas sekolah).
function handleFailed(code) {
    lastError.value = code ?? 'tidak-diketahui'
    if (code === 'player-belum-siap') return // bukan kegagalan video: tunggu pemutar siap
    errorStreak++
    if (errorStreak >= Math.max(2, queue.value.length)) {
        errorStreak = 0
        cooldown.value = true
        mode.value = 'slate'
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
        mode.value = 'slate'
        player.stop()
    } else if (mode.value !== 'video' && !intermissionTimer) maybeStart()
})
watch(videoWanted, (v) => {
    if (v) maybeStart()
    else {
        mode.value = 'slate'
        player.stop()
    }
})
watch(() => [yt.value.muted, yt.value.volume], () => player.setAudio(preview || yt.value.muted, yt.value.volume))

const host = ref(null)
onMounted(() => {
    const target = document.createElement('div')
    target.id = 'tv-yt-player'
    host.value.appendChild(target)
    rebuild()
    maybeStart()
    rotateTimer = setInterval(() => infoIdx.value++, ROTATE_SEC * 1000)
})
onBeforeUnmount(() => {
    clearTimeout(intermissionTimer)
    clearTimeout(cooldownTimer)
    clearInterval(rotateTimer)
})

const caption = computed(() => {
    if (mode.value === 'video') return ['Sedang diputar', current.value?.title]
    if (queue.value.length) return ['Jeda', 'Video berikutnya segera diputar']
    return ['', `Selamat datang di ${school.value.name}`]
})
</script>

<template>
    <aside class="tv-rail" aria-label="Media dan informasi">
        <div class="tv-media">
            <div ref="host" class="tv-media__video" :data-hidden="mode !== 'video'" />
            <div class="tv-slate" :data-hidden="mode === 'video'">
                <div class="tv-slate__logo"><img v-if="school.logo_url" :src="school.logo_url" alt="" /><template v-else>{{ initials }}</template></div>
                <div class="tv-slate__name">{{ school.name }}</div>
                <div class="tv-slate__sub">{{ school.tagline || 'Pusat Informasi Sekolah' }}</div>
            </div>
        </div>
        <div class="tv-caption"><span v-if="caption[0]">{{ caption[0] }}</span><b>{{ caption[1] }}</b></div>

        <Transition name="tv-fade" mode="out-in">
            <InformationPanel :key="infoKey" :item="infoItem" :school="school" :index="infoPos" :total="infoTotal" />
        </Transition>
        <div :key="'bar' + infoIdx" class="tv-rail__bar" :style="{ '--tv-dur': ROTATE_SEC + 's' }" />

        <div v-if="debug" style="position: absolute; left: 36px; top: 36px; z-index: 5; background: rgba(0, 0, 0, 0.8); color: #fff; font: 15px/1.4 monospace; padding: 6px 10px; border-radius: 6px">
            antrean {{ queue.length }} | mode {{ mode }} | error {{ lastError }} | api {{ player.available.value ? 'ok' : 'gagal' }} | siap {{ player.ready.value ? 'ya' : 'tidak' }}
        </div>
    </aside>
</template>
