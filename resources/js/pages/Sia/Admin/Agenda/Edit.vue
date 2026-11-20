<script setup>
import { Head } from '@inertiajs/vue3'
import AdminLayout from '../../../../components/sia/layout/AdminLayout.vue'
import PageHeader from '../../../../components/sia/layout/PageHeader.vue'
import AgendaEditor from '../../../../components/sia/agenda/AgendaEditor.vue'
import { longDate } from '../../../../lib/sia'

defineOptions({ layout: AdminLayout })
defineProps({ agenda: Object, categories: Array })
</script>

<template>
    <Head :title="`Edit ${agenda.title}`" />
    <PageHeader :title="agenda.title" :description="longDate(agenda.agenda_date)">
        <template #crumbs><a href="/admin/agenda">Agenda</a> / Edit</template>
    </PageHeader>
    <details v-if="agenda.original_source_text" class="panel" style="margin-bottom: 24px">
        <summary class="panel__head" style="cursor: pointer"><span class="panel__title" style="font-size: 15px">Teks WhatsApp asli</span></summary>
        <div class="panel__body"><pre class="source-text">{{ agenda.original_source_text }}</pre></div>
    </details>
    <AgendaEditor :key="agenda.id" :agenda="agenda" :categories="categories" :action="`/admin/agenda/${agenda.id}`" method="put" />
</template>
