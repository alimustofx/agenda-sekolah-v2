<script setup>
import { Head, router } from '@inertiajs/vue3'
import AdminLayout from '../../../../components/sia/layout/AdminLayout.vue'
import PageHeader from '../../../../components/sia/layout/PageHeader.vue'
import Select from '../../../../components/sia/ui/Select.vue'
import Pagination from '../../../../components/sia/ui/Pagination.vue'
import EmptyState from '../../../../components/sia/ui/EmptyState.vue'

defineOptions({ layout: AdminLayout })
const props = defineProps({ logs: Object, modules: Array, filters: Object, limited: Boolean })
const filter = (m) => router.get('/admin/audit-log', m ? { module: m } : {}, { preserveState: true })
</script>

<template>
    <Head title="Audit Log" />
    <PageHeader title="Audit log" :description="limited ? 'Aktivitas administrasi Anda sendiri.' : 'Seluruh aktivitas administrasi, dari yang terbaru.'" />
    <div style="max-width: 260px; margin-bottom: 20px">
        <Select :model-value="filters.module" label="Modul" placeholder="Semua modul" :options="modules.map((m) => ({ value: m, label: m }))" @update:model-value="filter" />
    </div>
    <div class="panel">
        <div v-if="logs.data.length" class="table-wrap">
            <table class="table">
                <thead><tr><th>Waktu</th><th>Pengguna</th><th>Modul</th><th>Aktivitas</th><th>IP</th></tr></thead>
                <tbody>
                    <tr v-for="l in logs.data" :key="l.id">
                        <td class="small tnum" style="white-space: nowrap">{{ l.time }}</td>
                        <td>{{ l.user }}</td>
                        <td class="small">{{ l.module }}</td>
                        <td>{{ l.description }}</td>
                        <td class="small muted tnum">{{ l.ip }}</td>
                    </tr>
                </tbody>
            </table>
            <Pagination :links="logs.links" />
        </div>
        <EmptyState v-else title="Belum ada aktivitas" description="Tindakan administratif seperti membuat, mengubah, dan menghapus akan tercatat di sini." />
    </div>
</template>
