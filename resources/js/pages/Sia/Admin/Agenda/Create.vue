<script setup>
import { Head } from '@inertiajs/vue3'
import AdminLayout from '../../../../components/sia/layout/AdminLayout.vue'
import PageHeader from '../../../../components/sia/layout/PageHeader.vue'
import AgendaEditor from '../../../../components/sia/agenda/AgendaEditor.vue'

defineOptions({ layout: AdminLayout })
defineProps({ agenda: Object, categories: Array })
</script>

<template>
    <Head title="Tambah Agenda" />
    <PageHeader title="Tambah agenda" description="Isi informasi umum dan daftar kegiatan, lalu simpan sebagai draft atau terbitkan.">
        <template #crumbs><a href="/admin/agenda">Agenda</a> / Baru</template>
    </PageHeader>
    <AgendaEditor :agenda="agenda" :categories="categories" action="/admin/agenda" method="post" />
</template>
