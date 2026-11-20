<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import AgendaDisplayCard from './AgendaDisplayCard.vue'

const props = defineProps({ agenda: Object, settings: Object, clock: Object })

const GAP = 18
const items = computed(() => props.agenda?.items ?? [])
const cap = computed(() => props.settings.cards_per_page)
const viewport = ref(null)
const measure = ref(null)
const pages = ref([]) // [{ items, full }]
const page = ref(0)
let timer = null
let ro = null
let raf = 0

const numberOf = (it) => items.value.findIndex((x) => x.id === it.id) + 1

// Halaman disusun dari tinggi kartu yang sebenarnya, sehingga isi panjang tidak pernah terpotong
// dan kartu pendek muat lebih banyak (maksimum = "kartu per halaman" di pengaturan).
function pack() {
    const vp = viewport.value
    const m = measure.value
    if (!vp || !m || !items.value.length) {
        pages.value = []
        return
    }
    const budget = vp.clientHeight
    m.style.width = vp.clientWidth + 'px'
    const els = Array.from(m.children)
    const out = []
    let cur = []
    let sum = 0
    els.forEach((el, i) => {
        const h = el.offsetHeight
        const next = cur.length ? sum + GAP + h : h
        if (cur.length && (next > budget || cur.length >= cap.value)) {
            out.push({ items: cur, sum })
            cur = []
            sum = 0
        }
        cur.push(items.value[i])
        sum = cur.length === 1 ? h : sum + GAP + h
    })
    if (cur.length) out.push({ items: cur, sum })

    pages.value = out.map((p) => ({ items: p.items, full: p.items.length >= cap.value || p.sum >= budget * 0.78 }))
    if (page.value >= pages.value.length) page.value = 0
    arm()
}
const schedulePack = () => {
    cancelAnimationFrame(raf)
    raf = requestAnimationFrame(pack)
}

// Durasi dasar; bila adaptif, halaman dengan teks lebih panjang tampil lebih lama (dalam batas min/maks).
function duration(idx) {
    const base = props.settings.interval
    if (!props.settings.adaptive) return base
    const chars = (pages.value[idx]?.items || []).reduce((n, it) => n + [it.title, it.location, it.description, it.officers, it.participants, it.responsible_person, it.additional_notes].filter(Boolean).join(' ').length, 0)
    const extra = Math.max(0, Math.ceil((chars - 220) / 40))
    return Math.min(props.settings.max_interval, Math.max(props.settings.min_interval, base + extra))
}
function arm() {
    clearTimeout(timer)
    if (pages.value.length < 2) return
    timer = setTimeout(() => {
        page.value = (page.value + 1) % pages.value.length
        arm()
    }, duration(page.value) * 1000)
}

// Susun ulang hanya bila isi atau pengaturan benar-benar berubah; polling biasa tidak mengganggu.
const signature = computed(() => JSON.stringify(items.value) + '|' + cap.value)
watch(signature, () => nextTick(schedulePack))

onMounted(() => {
    ro = new ResizeObserver(schedulePack)
    ro.observe(viewport.value)
    document.fonts?.ready.then(schedulePack)
    nextTick(schedulePack)
})
onBeforeUnmount(() => {
    clearTimeout(timer)
    cancelAnimationFrame(raf)
    ro?.disconnect()
})
</script>

<template>
    <section class="tv-agenda" aria-label="Agenda kegiatan">
        <div class="tv-agenda__head">
            <div>
                <h1 class="tv-heading">{{ settings.title }}</h1>
                <p class="tv-subheading">{{ clock.date }}</p>
            </div>
            <dl v-if="agenda?.uniform_information" class="tv-uniform">
                <dt>Pakaian dinas</dt>
                <dd>{{ agenda.uniform_information }}</dd>
            </dl>
        </div>

        <div ref="viewport" class="tv-viewport-pages">
            <div v-if="pages.length" class="tv-track" :style="{ transform: `translateX(calc(${-page} * (100% + 48px)))` }">
                <div v-for="(p, i) in pages" :key="i" class="tv-page" :class="{ 'tv-page--full': p.full }" :aria-hidden="i !== page">
                    <AgendaDisplayCard v-for="it in p.items" :key="it.id" :item="it" :number="numberOf(it)" :now-minutes="clock.minutes" />
                </div>
            </div>
            <div v-else-if="!items.length" class="tv-empty">
                <h2>BELUM ADA AGENDA KEGIATAN</h2>
                <p>Informasi kegiatan sekolah akan ditampilkan di sini setelah agenda hari ini diterbitkan.</p>
            </div>
        </div>

        <div class="tv-pager">
            <template v-if="pages.length > 1">
                <span class="tv-num">Halaman {{ page + 1 }} dari {{ pages.length }}</span>
                <span class="tv-pager__bars"><span v-for="(p, i) in pages" :key="i" class="tv-pager__bar" :class="{ 'tv-pager__bar--on': i === page }" /></span>
            </template>
            <span v-if="items.length" class="tv-pager__count tv-num">{{ items.length }} kegiatan</span>
        </div>

        <div ref="measure" class="tv-measure" aria-hidden="true">
            <AgendaDisplayCard v-for="it in items" :key="it.id" :item="it" :number="numberOf(it)" :now-minutes="clock.minutes" />
        </div>
    </section>
</template>
