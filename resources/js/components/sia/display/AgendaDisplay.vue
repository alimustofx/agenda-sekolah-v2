<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue'
import AgendaDisplayCard from './AgendaDisplayCard.vue'

const props = defineProps({ agenda: Object, settings: Object, clock: Object })

const perPage = computed(() => props.settings.cards_per_page)
const items = computed(() => props.agenda?.items ?? [])
const pages = computed(() => {
    const out = []
    for (let i = 0; i < items.value.length; i += perPage.value) out.push(items.value.slice(i, i + perPage.value))
    return out
})
const page = ref(0)
let timer = null

// Durasi dasar; bila adaptif, halaman dengan teks lebih panjang tampil lebih lama (dalam batas min/maks).
function duration(idx) {
    const base = props.settings.interval
    if (!props.settings.adaptive) return base
    const chars = (pages.value[idx] || []).reduce((n, it) => n + [it.title, it.location, it.description, it.officers, it.participants, it.responsible_person, it.additional_notes].filter(Boolean).join(' ').length, 0)
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

// Hanya mulai ulang bila isi/jumlah halaman benar-benar berubah; polling biasa tidak mengganggu.
watch(
    () => JSON.stringify(items.value.map((i) => i.id)) + perPage.value,
    () => {
        if (page.value >= pages.value.length) page.value = 0
        arm()
    },
    { immediate: true },
)
onBeforeUnmount(() => clearTimeout(timer))
</script>

<template>
    <section class="tv-agenda" aria-label="Agenda kegiatan">
        <div class="tv-agenda__head">
            <div>
                <h1 class="tv-heading">{{ settings.title }}</h1>
                <p class="tv-subheading">{{ clock.date }}</p>
            </div>
            <p v-if="agenda?.uniform_information" class="tv-uniform"><b>Pakaian dinas</b>{{ agenda.uniform_information }}</p>
        </div>

        <template v-if="pages.length">
            <div class="tv-viewport-pages">
                <div class="tv-track" :style="{ transform: `translateX(-${page * 100}%)` }">
                    <div v-for="(group, p) in pages" :key="p" class="tv-page" :style="{ gridTemplateColumns: `repeat(${perPage}, minmax(0, 1fr))` }" :aria-hidden="p !== page">
                        <AgendaDisplayCard v-for="(item, n) in group" :key="item.id" :item="item" :number="p * perPage + n + 1" :now-minutes="clock.minutes" />
                    </div>
                </div>
            </div>
            <div class="tv-pager">
                <template v-if="pages.length > 1">
                    <span class="tv-num">Halaman {{ page + 1 }} dari {{ pages.length }}</span>
                    <span class="tv-pager__bars"><span v-for="(g, p) in pages" :key="p" class="tv-pager__bar" :class="{ 'tv-pager__bar--on': p === page }" /></span>
                </template>
            </div>
        </template>

        <div v-else class="tv-empty">
            <h2>BELUM ADA AGENDA KEGIATAN</h2>
            <p>Informasi kegiatan sekolah<br />akan ditampilkan di sini.</p>
        </div>
    </section>
</template>
