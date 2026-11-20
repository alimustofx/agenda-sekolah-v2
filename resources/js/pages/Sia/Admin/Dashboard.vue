<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AdminLayout from '../../../components/sia/layout/AdminLayout.vue'
import PageHeader from '../../../components/sia/layout/PageHeader.vue'
import Button from '../../../components/sia/ui/Button.vue'
import Badge from '../../../components/sia/ui/Badge.vue'
import { AGENDA_STATUS, agendaStatusVariant } from '../../../lib/sia'

defineOptions({ layout: AdminLayout })
defineProps({ todayLabel: String, todayAgenda: Object, items: Array, overview: Object, logs: Array })
</script>

<template>
    <Head title="Dashboard" />
    <PageHeader title="Selamat datang" description="Ringkasan aktivitas sekolah hari ini.">
        <Button href="/admin/agenda/create" variant="primary">Buat agenda</Button>
        <Button href="/admin/agenda/import">Import WhatsApp</Button>
    </PageHeader>

    <div class="stack stack--lg">
        <section class="panel">
            <div class="panel__head">
                <div>
                    <h2 class="panel__title">Agenda hari ini</h2>
                    <p class="panel__desc">{{ todayLabel }}</p>
                </div>
                <Badge v-if="todayAgenda" :variant="agendaStatusVariant(todayAgenda.status)">{{ AGENDA_STATUS[todayAgenda.status] }}</Badge>
            </div>
            <div class="panel__body">
                <template v-if="todayAgenda">
                    <p><strong style="font-size: 22px; color: var(--sia-stone-900)">{{ todayAgenda.items_count }} kegiatan</strong>
                        <span class="muted"> · {{ todayAgenda.ongoing_count }} sedang berlangsung</span></p>
                    <p v-if="todayAgenda.status === 'draft'" class="alert alert--warn" style="margin-top: 12px">Agenda hari ini masih berstatus draft, sehingga belum tampil di website dan TV.</p>
                    <div style="margin-top: 12px">
                        <div v-for="i in items" :key="i.id" class="timeline-row">
                            <time>{{ i.time_label }}</time>
                            <span>{{ i.title }}</span>
                            <Badge v-if="i.ongoing" variant="info">Berlangsung</Badge>
                        </div>
                    </div>
                    <div style="margin-top: 12px"><Button size="sm" :href="`/admin/agenda/${todayAgenda.id}/edit`">Buka agenda</Button></div>
                </template>
                <template v-else>
                    <p>Belum ada agenda untuk hari ini. TV Display akan menampilkan pesan kosong sampai agenda diterbitkan.</p>
                    <div style="margin-top: 16px" class="row">
                        <Button variant="primary" href="/admin/agenda/create">Buat agenda</Button>
                        <Button href="/admin/agenda/import">Import dari WhatsApp</Button>
                    </div>
                </template>
            </div>
        </section>

        <dl class="facts">
            <div><dt>Agenda mendatang</dt><dd>{{ overview.upcoming }}</dd></div>
            <div><dt>Draft menunggu</dt><dd>{{ overview.drafts }}</dd></div>
            <div><dt>Pengumuman aktif</dt><dd>{{ overview.announcements }}</dd></div>
            <div><dt>Kegiatan berlangsung</dt><dd>{{ overview.ongoing_activities }}</dd></div>
            <div><dt>Video aktif</dt><dd>{{ overview.videos }}</dd></div>
        </dl>

        <section class="panel">
            <div class="panel__head"><h2 class="panel__title">Aktivitas administrasi</h2><Link href="/admin/audit-log" class="small" style="font-weight: 600">Audit log</Link></div>
            <div class="panel__body">
                <p v-if="!logs.length" class="muted">Belum ada aktivitas tercatat.</p>
                <div v-for="l in logs" :key="l.id" class="timeline-row" style="grid-template-columns: 90px 1fr auto">
                    <time class="muted small">{{ l.time }}</time><span>{{ l.description }}</span><span class="muted small">{{ l.user }}</span>
                </div>
            </div>
        </section>
    </div>
</template>
