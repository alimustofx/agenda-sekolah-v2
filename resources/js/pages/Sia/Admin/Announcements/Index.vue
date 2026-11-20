<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'
import AdminLayout from '../../../../components/sia/layout/AdminLayout.vue'
import PageHeader from '../../../../components/sia/layout/PageHeader.vue'
import Button from '../../../../components/sia/ui/Button.vue'
import Badge from '../../../../components/sia/ui/Badge.vue'
import Input from '../../../../components/sia/ui/Input.vue'
import Select from '../../../../components/sia/ui/Select.vue'
import Check from '../../../../components/sia/ui/Check.vue'
import Modal from '../../../../components/sia/ui/Modal.vue'
import ConfirmModal from '../../../../components/sia/ui/ConfirmModal.vue'
import Pagination from '../../../../components/sia/ui/Pagination.vue'
import EmptyState from '../../../../components/sia/ui/EmptyState.vue'
import { PRIORITY, priorityOptions, priorityVariant } from '../../../../lib/sia'

defineOptions({ layout: AdminLayout })
defineProps({ announcements: Object, filter: String })

const tabs = [['aktif', 'Aktif'], ['terjadwal', 'Terjadwal'], ['arsip', 'Arsip'], ['semua', 'Semua']]
const stateVariant = { aktif: 'success', terjadwal: 'info', berakhir: 'neutral', nonaktif: 'neutral' }
const open = ref(false)
const editing = ref(null)
const target = ref(null)
const blank = { title: '', content: '', category: '', priority: 'normal', start_at: '', end_at: '', show_on_website: true, show_on_tv: true, is_active: true }
const form = useForm({ ...blank })

function openForm(a = null) {
    editing.value = a
    form.clearErrors()
    form.defaults({ ...blank })
    form.reset()
    if (a) Object.assign(form, { ...blank, ...a, category: a.category || '', start_at: a.start_at || '', end_at: a.end_at || '' })
    open.value = true
}
function save() {
    const opts = { preserveScroll: true, onSuccess: () => (open.value = false) }
    editing.value ? form.put(`/admin/pengumuman/${editing.value.id}`, opts) : form.post('/admin/pengumuman', opts)
}
const destroy = () => router.delete(`/admin/pengumuman/${target.value.id}`, { preserveScroll: true, onFinish: () => (target.value = null) })
</script>

<template>
    <Head title="Pengumuman" />
    <PageHeader title="Pengumuman" description="Sampaikan informasi yang membutuhkan perhatian khusus dan atur periode tayangnya.">
        <Button variant="primary" @click="openForm()">Tambah pengumuman</Button>
    </PageHeader>

    <div class="tabs" style="margin-bottom: 20px">
        <Link v-for="[k, l] in tabs" :key="k" :href="`/admin/pengumuman?status=${k}`" class="tab" :class="{ 'tab--active': filter === k }">{{ l }}</Link>
    </div>

    <div class="panel">
        <div v-if="announcements.data.length" class="table-wrap">
            <table class="table">
                <thead><tr><th>Pengumuman</th><th>Prioritas</th><th>Periode tayang</th><th>Kanal</th><th>Status</th><th><span class="sr-only">Aksi</span></th></tr></thead>
                <tbody>
                    <tr v-for="a in announcements.data" :key="a.id">
                        <td style="max-width: 360px"><div class="cell-title">{{ a.title }}</div><div class="muted small" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden">{{ a.content }}</div></td>
                        <td><Badge :variant="priorityVariant(a.priority)">{{ PRIORITY[a.priority] }}</Badge></td>
                        <td class="small tnum" style="white-space: nowrap">{{ a.start_at ? a.start_at.replace('T', ' ') : 'Langsung' }}<br />s/d {{ a.end_at ? a.end_at.replace('T', ' ') : 'tanpa batas' }}</td>
                        <td class="small">{{ [a.show_on_website && 'Website', a.show_on_tv && 'TV'].filter(Boolean).join(', ') || '-' }}</td>
                        <td><Badge :variant="stateVariant[a.state]">{{ a.state }}</Badge></td>
                        <td><div class="actions"><Button size="sm" @click="openForm(a)">Edit</Button><Button size="sm" variant="ghost" @click="target = a">Hapus</Button></div></td>
                    </tr>
                </tbody>
            </table>
            <Pagination :links="announcements.links" />
        </div>
        <EmptyState v-else title="Belum ada pengumuman" description="Pengumuman yang Anda buat akan tampil di website dan TV sesuai periode tayangnya.">
            <Button variant="primary" @click="openForm()">Tambah pengumuman</Button>
        </EmptyState>
    </div>

    <Modal :open="open" wide :title="editing ? 'Edit pengumuman' : 'Tambah pengumuman'" @close="open = false">
        <Input v-model="form.title" label="Judul" required :error="form.errors.title" />
        <Input v-model="form.content" as="textarea" :rows="5" label="Isi pengumuman" required :error="form.errors.content" />
        <div class="grid-2">
            <Input v-model="form.category" label="Kategori" placeholder="Opsional" :error="form.errors.category" />
            <Select v-model="form.priority" label="Prioritas" :options="priorityOptions" hint="Mendesak mendapat waktu tayang lebih sering di TV." />
        </div>
        <div class="grid-2">
            <Input v-model="form.start_at" type="datetime-local" label="Mulai tayang" hint="Kosong = langsung tayang." :error="form.errors.start_at" />
            <Input v-model="form.end_at" type="datetime-local" label="Berhenti tayang" hint="Kosong = tanpa batas." :error="form.errors.end_at" />
        </div>
        <fieldset class="stack stack--sm">
            <legend>Penayangan</legend>
            <Check v-model="form.is_active" label="Pengumuman aktif" />
            <Check v-model="form.show_on_website" label="Tampil di website" />
            <Check v-model="form.show_on_tv" label="Tampil di TV Display" />
        </fieldset>
        <template #footer><Button @click="open = false">Batal</Button><Button variant="primary" :disabled="form.processing" @click="save">Simpan</Button></template>
    </Modal>
    <ConfirmModal :open="!!target" danger title="Hapus pengumuman?" :message="`Pengumuman “${target?.title}” akan dihapus permanen.`" confirm-label="Hapus" @close="target = null" @confirm="destroy" />
</template>
