<script setup>
import { computed } from 'vue'

const props = defineProps({ item: Object, number: Number, nowMinutes: Number })

const toMin = (hm) => (hm ? Number(hm.slice(0, 2)) * 60 + Number(hm.slice(3, 5)) : null)

// Status waktu dihitung dari jam yang berjalan; urutan kartu tidak pernah diubah.
const timing = computed(() => {
    const s = toMin(props.item.start_time)
    const e = toMin(props.item.end_time)
    if (s === null) return null
    if (e !== null && props.nowMinutes > e) return 'done'
    if (props.nowMinutes >= s) return 'now'
    if (s - props.nowMinutes <= 30) return 'soon'
    return null
})
const timingLabel = { now: 'Sedang berlangsung', soon: 'Segera dimulai', done: 'Selesai' }
const priorityLabel = { important: 'Penting', urgent: 'Mendesak' }

const rows = computed(() => {
    const i = props.item
    const r = []
    if (i.responsible_person) r.push([i.responsible_label || 'Penanggung jawab', i.responsible_person])
    if (i.officers) r.push(['Petugas', i.officers])
    if (i.participants) r.push(['Peserta', i.participants])
    if (i.description) r.push(['Keterangan', i.description])
    if (i.additional_notes) r.push(['Catatan', i.additional_notes])
    return r
})
</script>

<template>
    <article class="tv-card" :class="{ 'tv-card--now': timing === 'now', 'tv-card--done': timing === 'done' }">
        <div class="tv-card__top">
            <span class="tv-card__no">{{ String(number).padStart(2, '0') }}</span>
            <span class="row" style="display: flex; gap: 8px">
                <span v-if="item.priority !== 'normal'" class="tv-status" :class="`tv-status--${item.priority}`">{{ priorityLabel[item.priority] }}</span>
                <span v-if="timing" class="tv-status" :class="`tv-status--${timing}`">{{ timingLabel[timing] }}</span>
            </span>
        </div>
        <div v-if="item.category" class="tv-card__cat">{{ item.category.name }}</div>
        <h3 class="tv-card__title">{{ item.title }}</h3>
        <div class="tv-card__time">{{ item.time_label }} WIB</div>
        <div v-if="item.location" class="tv-card__loc">{{ item.location }}</div>
        <dl v-if="rows.length" class="tv-card__rows" style="margin-bottom: 0">
            <div v-for="[label, value] in rows" :key="label" class="tv-row">
                <dt>{{ label }}</dt>
                <dd class="tv-clamp" style="white-space: pre-line">{{ value }}</dd>
            </div>
        </dl>
    </article>
</template>
