<script setup>
import { Head, Link } from '@inertiajs/vue3'
import { ChevronLeft, ChevronRight } from 'lucide-vue-next'
import PublicLayout from '../../../../components/sia/layout/PublicLayout.vue'
import { DAYS } from '../../../../lib/sia'

defineOptions({ layout: PublicLayout })
const props = defineProps({ month: String, monthLabel: String, prev: String, next: String, startOffset: Number, daysInMonth: Number, today: String, byDate: Object })
const dow = [...DAYS.slice(1), DAYS[0]]
const key = (d) => `${props.month}-${String(d).padStart(2, '0')}`
</script>

<template>
    <Head title="Kalender Agenda" />
    <div class="container" style="padding-top: 40px">
        <div class="row row--between" style="margin-bottom: 20px">
            <h1 class="masthead__title" style="font-size: clamp(26px, 4vw, 38px)">{{ monthLabel }}</h1>
            <div class="row" style="gap: 8px">
                <Link :href="`/agenda/kalender?bulan=${prev}`" class="btn btn--secondary btn--icon" aria-label="Bulan sebelumnya"><ChevronLeft :size="18" /></Link>
                <Link href="/agenda/kalender" class="btn btn--secondary">Bulan ini</Link>
                <Link :href="`/agenda/kalender?bulan=${next}`" class="btn btn--secondary btn--icon" aria-label="Bulan berikutnya"><ChevronRight :size="18" /></Link>
            </div>
        </div>
        <div class="cal" role="grid">
            <div v-for="d in dow" :key="d" class="cal__dow">{{ d.slice(0, 3) }}</div>
            <div v-for="n in startOffset" :key="'b' + n" class="cal__cell cal__cell--blank" />
            <div v-for="d in daysInMonth" :key="d" class="cal__cell" :class="{ 'cal__cell--today': key(d) === today }">
                <span class="cal__num">{{ d }}</span>
                <Link v-for="a in byDate[key(d)] || []" :key="a.id" :href="`/agenda/${a.id}`" class="cal__link">{{ a.title }} ({{ a.items_count }})</Link>
            </div>
        </div>
        <p class="muted small" style="margin-top: 12px">Angka dalam kurung menunjukkan jumlah kegiatan pada agenda tersebut.</p>
    </div>
</template>
