<script setup>
import { computed } from 'vue'
import { MapPin, UserCheck, UserRound, Users } from 'lucide-vue-next'
import { dot } from '../../../lib/sia'

const props = defineProps({ item: Object, number: Number, nowMinutes: Number })

const toMin = (hm) => (hm ? Number(hm.slice(0, 2)) * 60 + Number(hm.slice(3, 5)) : null)

// Status waktu dihitung dari jam yang berjalan; urutan kartu tidak pernah berubah.
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

const start = computed(() => dot(props.item.start_time))
const sub = computed(() => {
    const i = props.item
    if (!i.start_time) return ''
    if (i.end_time) return `s/d ${dot(i.end_time)}`
    return i.time_description ? `s/d ${i.time_description}` : ''
})

const facts = computed(() => {
    const i = props.item
    const f = []
    if (i.location) f.push({ icon: MapPin, label: 'Lokasi', value: i.location })
    if (i.responsible_person) f.push({ icon: UserRound, label: i.responsible_label || 'Penanggung jawab', value: i.responsible_person })
    if (i.officers) f.push({ icon: UserCheck, label: 'Petugas', value: i.officers })
    if (i.participants) f.push({ icon: Users, label: 'Peserta', value: i.participants })
    return f.map((x) => ({ ...x, wide: x.value.length > 64 }))
})
</script>

<template>
    <article class="tv-card" :class="{ 'tv-card--now': timing === 'now', 'tv-card--done': timing === 'done' }">
        <div class="tv-card__time">
            <div class="tv-card__start" :class="{ 'tv-card__start--text': !start }">{{ start || item.time_label }}</div>
            <div v-if="sub" class="tv-card__end">{{ sub }}</div>
            <div v-if="start" class="tv-card__zone">WIB</div>
            <div v-for="(t, i) in item.extra_times || []" :key="i" class="tv-card__extra">{{ t }}</div>
        </div>
        <div class="tv-card__body">
            <div class="tv-card__head">
                <span class="tv-no">{{ String(number).padStart(2, '0') }}</span>
                <span v-if="item.category" class="tv-cat"><i :style="{ background: item.category.color }" />{{ item.category.name }}</span>
                <span class="tv-chips">
                    <span v-if="item.priority !== 'normal'" class="tv-chip" :class="`tv-chip--${item.priority}`">{{ priorityLabel[item.priority] }}</span>
                    <span v-if="timing" class="tv-chip" :class="`tv-chip--${timing}`">{{ timingLabel[timing] }}</span>
                </span>
            </div>
            <h3 class="tv-card__title">{{ item.title }}</h3>
            <dl v-if="facts.length" class="tv-facts">
                <div v-for="f in facts" :key="f.label + f.value" class="tv-fact" :class="{ 'tv-fact--wide': f.wide }">
                    <component :is="f.icon" :size="22" :stroke-width="2" />
                    <div><dt>{{ f.label }}</dt><dd>{{ f.value }}</dd></div>
                </div>
            </dl>
            <p v-if="item.description" class="tv-note">{{ item.description }}</p>
            <p v-if="item.additional_notes" class="tv-note">{{ item.additional_notes }}</p>
        </div>
    </article>
</template>
