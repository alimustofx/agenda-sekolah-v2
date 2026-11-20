<script setup>
import { Head, Link } from '@inertiajs/vue3'
import PublicLayout from '../../../../components/sia/layout/PublicLayout.vue'
import AgendaSchedule from '../../../../components/sia/agenda/AgendaSchedule.vue'

defineOptions({ layout: PublicLayout })
defineProps({ agenda: Object })
</script>

<template>
    <Head :title="`${agenda.title} — ${agenda.long_date}`" />
    <div class="container" style="padding-top: 40px">
        <p class="crumbs"><Link href="/agenda">Agenda</Link> / {{ agenda.short_date }}</p>
        <article class="doc">
            <p class="masthead__kicker">{{ agenda.long_date }}</p>
            <h1 class="doc__title">{{ agenda.title }}</h1>
            <p v-if="agenda.introduction" class="pre-line" style="margin-top: 16px">{{ agenda.introduction }}</p>

            <section class="doc__block" v-if="agenda.uniform_information || agenda.recipients">
                <h2 class="section__title">Informasi umum</h2>
                <dl class="doc__facts">
                    <template v-if="agenda.uniform_information"><dt>Pakaian dinas</dt><dd>{{ agenda.uniform_information }}</dd></template>
                    <template v-if="agenda.recipients"><dt>Ditujukan kepada</dt><dd class="pre-line">{{ agenda.recipients }}</dd></template>
                </dl>
            </section>

            <section class="doc__block">
                <h2 class="section__title" style="margin-bottom: 12px">Daftar kegiatan <span class="muted" style="font-weight: 400">({{ agenda.items.length }})</span></h2>
                <AgendaSchedule v-if="agenda.items.length" :items="agenda.items" detailed />
                <p v-else class="muted">Belum ada kegiatan pada agenda ini.</p>
            </section>

            <section v-if="agenda.general_notes" class="doc__block">
                <h2 class="section__title">Catatan</h2>
                <p class="pre-line" style="margin-top: 8px">{{ agenda.general_notes }}</p>
            </section>
        </article>
    </div>
</template>
