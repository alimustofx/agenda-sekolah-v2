<script setup>
import { Head } from '@inertiajs/vue3'
import PublicLayout from '../../../../components/sia/layout/PublicLayout.vue'

defineOptions({ layout: PublicLayout })
defineProps({ ongoing: Array, scheduled: Array, completed: Array })
const groups = (p) => [
    ['Sedang berlangsung', p.ongoing, 'Tidak ada kegiatan yang sedang berlangsung.'],
    ['Akan datang', p.scheduled, 'Belum ada kegiatan terjadwal.'],
    ['Baru selesai', p.completed, 'Belum ada riwayat kegiatan.'],
]
</script>

<template>
    <Head title="Kegiatan Sekolah" />
    <div class="container" style="padding-top: 48px">
        <h1 class="masthead__title" style="font-size: clamp(28px, 4vw, 40px)">Kegiatan sekolah</h1>
        <section v-for="[title, list, empty] in groups($props)" :key="title" class="section">
            <h2 class="section__title" style="margin-bottom: 12px">{{ title }}</h2>
            <ol v-if="list.length" class="schedule">
                <li v-for="a in list" :key="a.id" class="schedule__item">
                    <div class="schedule__time">{{ a.date }}<br />{{ a.time_label }}</div>
                    <div>
                        <h3 class="schedule__title">{{ a.title }}</h3>
                        <div class="schedule__meta">
                            <span v-if="a.location"><b>Lokasi</b> {{ a.location }}</span>
                            <span v-if="a.responsible_person"><b>Penanggung jawab</b> {{ a.responsible_person }}</span>
                        </div>
                        <p v-if="a.description" style="margin-top: 6px">{{ a.description }}</p>
                    </div>
                </li>
            </ol>
            <p v-else class="muted">{{ empty }}</p>
        </section>
    </div>
</template>
