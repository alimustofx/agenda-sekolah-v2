<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { reactive, ref } from 'vue'
import { Copy, Eye, MoreHorizontal, Pencil, Trash2 } from 'lucide-vue-next'
import AdminLayout from '../../../../components/sia/layout/AdminLayout.vue'
import PageHeader from '../../../../components/sia/layout/PageHeader.vue'
import Button from '../../../../components/sia/ui/Button.vue'
import Badge from '../../../../components/sia/ui/Badge.vue'
import Input from '../../../../components/sia/ui/Input.vue'
import Select from '../../../../components/sia/ui/Select.vue'
import Dropdown from '../../../../components/sia/ui/Dropdown.vue'
import Pagination from '../../../../components/sia/ui/Pagination.vue'
import EmptyState from '../../../../components/sia/ui/EmptyState.vue'
import ConfirmModal from '../../../../components/sia/ui/ConfirmModal.vue'
import { AGENDA_STATUS, agendaStatusVariant } from '../../../../lib/sia'

defineOptions({ layout: AdminLayout })
const props = defineProps({ agendas: Object, filters: Object })
const f = reactive({ q: props.filters.q || '', status: props.filters.status || '', date: props.filters.date || '' })
const target = ref(null)
const busy = ref(false)

const search = () => router.get('/admin/agenda', Object.fromEntries(Object.entries(f).filter(([, v]) => v)), { preserveState: true })
const setStatus = (a, status) => router.patch(`/admin/agenda/${a.id}/status`, { status }, { preserveScroll: true })
const duplicate = (a) => router.post(`/admin/agenda/${a.id}/duplicate`)
function destroy() {
    busy.value = true
    router.delete(`/admin/agenda/${target.value.id}`, { onFinish: () => { busy.value = false; target.value = null } })
}
</script>

<template>
    <Head title="Agenda" />
    <PageHeader title="Agenda harian" description="Kelola agenda dan kegiatan sekolah berdasarkan tanggal.">
        <Button href="/admin/agenda/import">Import WhatsApp</Button>
        <Button href="/admin/agenda/create" variant="primary">Tambah agenda</Button>
    </PageHeader>

    <form class="grid-4" style="grid-template-columns: 1fr 200px 200px auto; align-items: end; margin-bottom: 20px" @submit.prevent="search">
        <Input v-model="f.q" label="Cari agenda" placeholder="Judul agenda" />
        <Input v-model="f.date" type="date" label="Tanggal" />
        <Select v-model="f.status" label="Status" placeholder="Semua status" :options="[{ value: 'draft', label: 'Draft' }, { value: 'published', label: 'Terbit' }, { value: 'archived', label: 'Diarsipkan' }]" />
        <Button type="submit">Terapkan</Button>
    </form>

    <div class="panel">
        <div v-if="agendas.data.length" class="table-wrap">
            <table class="table">
                <thead><tr><th>Tanggal</th><th>Agenda</th><th>Kegiatan</th><th>Status</th><th><span class="sr-only">Aksi</span></th></tr></thead>
                <tbody>
                    <tr v-for="a in agendas.data" :key="a.id">
                        <td style="white-space: nowrap"><div class="cell-title tnum">{{ a.short_date }}</div><div class="muted small">{{ a.day_name }}</div></td>
                        <td>
                            <Link :href="`/admin/agenda/${a.id}/edit`" class="cell-title">{{ a.title }}</Link>
                            <div class="muted small">{{ a.show_on_website ? 'Website' : '' }}{{ a.show_on_website && a.show_on_tv ? ' dan ' : '' }}{{ a.show_on_tv ? 'TV' : '' }}{{ !a.show_on_website && !a.show_on_tv ? 'Tidak ditayangkan' : '' }}</div>
                        </td>
                        <td class="tnum">{{ a.items_count }}</td>
                        <td><Badge :variant="agendaStatusVariant(a.status)">{{ AGENDA_STATUS[a.status] }}</Badge></td>
                        <td>
                            <div class="actions">
                                <Button size="sm" :href="`/admin/agenda/${a.id}/edit`"><Pencil :size="14" />Edit</Button>
                                <Button v-if="a.status === 'published'" size="sm" :href="`/agenda/${a.id}`"><Eye :size="14" />Lihat</Button>
                                <Dropdown>
                                    <template #trigger><Button size="sm" class="btn--icon" aria-label="Aksi lainnya"><MoreHorizontal :size="16" /></Button></template>
                                    <button type="button" class="dropdown__item" @click="duplicate(a)"><Copy :size="16" />Duplikat</button>
                                    <button v-if="a.status !== 'published'" type="button" class="dropdown__item" @click="setStatus(a, 'published')">Terbitkan</button>
                                    <button v-if="a.status === 'published'" type="button" class="dropdown__item" @click="setStatus(a, 'draft')">Jadikan draft</button>
                                    <button v-if="a.status !== 'archived'" type="button" class="dropdown__item" @click="setStatus(a, 'archived')">Arsipkan</button>
                                    <button type="button" class="dropdown__item dropdown__item--danger" @click="target = a"><Trash2 :size="16" />Hapus</button>
                                </Dropdown>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
            <Pagination :links="agendas.links" />
        </div>
        <EmptyState v-else title="Belum ada agenda" description="Buat agenda harian untuk mulai mengelola kegiatan sekolah, atau ubah teks WhatsApp menjadi agenda.">
            <Button variant="primary" href="/admin/agenda/create">Tambah agenda</Button>
            <Button href="/admin/agenda/import">Import WhatsApp</Button>
        </EmptyState>
    </div>

    <ConfirmModal :open="!!target" danger title="Hapus agenda?" :message="`Agenda “${target?.title}” beserta seluruh kegiatannya akan dihapus permanen. Tindakan ini dicatat di audit log.`" confirm-label="Hapus agenda" :processing="busy" @close="target = null" @confirm="destroy" />
</template>
