<script setup>
import { Head, Link } from '@inertiajs/vue3'
import PublicLayout from '../../../components/sia/layout/PublicLayout.vue'
import AgendaSchedule from '../../../components/sia/agenda/AgendaSchedule.vue'
import AnnouncementNotice from '../../../components/sia/announcement/AnnouncementNotice.vue'
import Button from '../../../components/sia/ui/Button.vue'

defineOptions({ layout: PublicLayout })
defineProps({ todayLabel: String, agenda: Object, upcoming: Array, announcements: Array, activities: Array })
</script>

<template>
    <Head title="Agenda dan Informasi Sekolah" />
    <section class="masthead">
        <div class="container">
            <p class="masthead__kicker">Informasi kegiatan sekolah</p>
            <h1 class="masthead__title">{{ todayLabel }}</h1>
            <dl v-if="agenda" class="masthead__meta">
                <div><dt>Kegiatan hari ini</dt><dd>{{ agenda.items.length }} kegiatan</dd></div>
                <div v-if="agenda.uniform_information"><dt>Pakaian dinas</dt><dd>{{ agenda.uniform_information }}</dd></div>
            </dl>
            <div class="row" style="margin-top: 24px">
                <Button variant="primary" href="/agenda">Lihat semua agenda</Button>
                <Button href="/agenda/kalender">Kalender agenda</Button>
            </div>
        </div>
    </section>

    <div class="container home-grid">
        <div>
            <section class="section">
                <div class="section__head">
                    <h2 class="section__title">Agenda hari ini</h2>
                    <Link v-if="agenda" :href="`/agenda/${agenda.id}`" class="small" style="font-weight: 600">Detail lengkap</Link>
                </div>
                <AgendaSchedule v-if="agenda && agenda.items.length" :items="agenda.items" />
                <p v-else class="muted">Belum ada agenda yang diterbitkan untuk hari ini. Informasi kegiatan sekolah akan ditampilkan di sini.</p>
                <p v-if="agenda?.general_notes" class="pre-line muted" style="margin-top: 16px">{{ agenda.general_notes }}</p>
            </section>

            <section v-if="activities.length" class="section">
                <div class="section__head"><h2 class="section__title">Kegiatan berlangsung</h2><Link href="/kegiatan" class="small" style="font-weight: 600">Semua kegiatan</Link></div>
                <ol class="schedule">
                    <li v-for="a in activities" :key="a.id" class="schedule__item">
                        <div class="schedule__time">{{ a.time_label }}</div>
                        <div>
                            <h3 class="schedule__title">{{ a.title }}</h3>
                            <div class="schedule__meta">
                                <span v-if="a.location"><b>Lokasi</b> {{ a.location }}</span>
                                <span v-if="a.responsible_person"><b>Penanggung jawab</b> {{ a.responsible_person }}</span>
                            </div>
                        </div>
                    </li>
                </ol>
            </section>
        </div>

        <div>
            <section class="section">
                <div class="section__head"><h2 class="section__title">Pengumuman</h2><Link href="/pengumuman" class="small" style="font-weight: 600">Semua</Link></div>
                <template v-if="announcements.length"><AnnouncementNotice v-for="a in announcements" :key="a.id" :item="a" /></template>
                <p v-else class="muted">Tidak ada pengumuman aktif.</p>
            </section>
            <section class="section">
                <div class="section__head"><h2 class="section__title">Agenda mendatang</h2></div>
                <template v-if="upcoming.length">
                    <Link v-for="u in upcoming" :key="u.id" :href="`/agenda/${u.id}`" class="list-row" style="grid-template-columns: 1fr; gap: 2px; text-decoration: none">
                        <span class="list-row__date">{{ u.long_date }}</span>
                        <span class="muted small">{{ u.title }}, {{ u.items_count }} kegiatan</span>
                    </Link>
                </template>
                <p v-else class="muted">Belum ada agenda mendatang.</p>
            </section>
        </div>
    </div>
</template>
