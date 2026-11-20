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
import EmptyState from '../../../../components/sia/ui/EmptyState.vue'
import { ACTIVITY_STATUS } from '../../../../lib/sia'

defineOptions({ layout: AdminLayout })
defineProps({ activities: Array, tab: String })

const open = ref(false)
const editing = ref(null)
const target = ref(null)
const blank = { title: '', description: '', location: '', responsible_person: '', start_at: '', end_at: '', time_description: '', status: 'scheduled', is_status_manual: false, show_on_website: true, show_on_tv: true }
const form = useForm({ ...blank })
const variant = { ongoing: 'info', scheduled: 'warning', completed: 'neutral' }

function openForm(a = null) {
    editing.value = a
    form.clearErrors()
    form.defaults({ ...blank })
    form.reset()
    if (a) Object.assign(form, { ...blank, ...a, description: a.description || '', location: a.location || '', responsible_person: a.responsible_person || '', start_at: a.start_at || '', end_at: a.end_at || '', time_description: a.time_description || '' })
    open.value = true
}
function save() {
    const opts = { preserveScroll: true, onSuccess: () => (open.value = false) }
    editing.value ? form.put(`/admin/kegiatan/${editing.value.id}`, opts) : form.post('/admin/kegiatan', opts)
}
const destroy = () => router.delete(`/admin/kegiatan/${target.value.id}`, { preserveScroll: true, onFinish: () => (target.value = null) })
</script>

<template>
    <Head title="Kegiatan Sekolah" />
    <PageHeader title="Kegiatan sekolah" description="Kegiatan yang sedang berlangsung atau berskala lebih luas dari agenda harian. Status mengikuti waktu secara otomatis.">
        <Button variant="primary" @click="openForm()">Tambah kegiatan</Button>
    </PageHeader>
    <div class="tabs" style="margin-bottom: 20px">
        <Link href="/admin/kegiatan?tab=berlangsung" class="tab" :class="{ 'tab--active': tab !== 'riwayat' }">Berlangsung &amp; terjadwal</Link>
        <Link href="/admin/kegiatan?tab=riwayat" class="tab" :class="{ 'tab--active': tab === 'riwayat' }">Riwayat</Link>
    </div>
    <div class="panel">
        <div v-if="activities.length" class="table-wrap">
            <table class="table">
                <thead><tr><th>Kegiatan</th><th>Waktu</th><th>Lokasi</th><th>Status</th><th><span class="sr-only">Aksi</span></th></tr></thead>
                <tbody>
                    <tr v-for="a in activities" :key="a.id">
                        <td><div class="cell-title">{{ a.title }}</div><div class="muted small">{{ a.responsible_person }}</div></td>
                        <td class="small tnum" style="white-space: nowrap">{{ a.start_at ? a.start_at.replace('T', ' ') : '-' }}<br />{{ a.end_at ? 's/d ' + a.end_at.replace('T', ' ') : a.time_description || '' }}</td>
                        <td>{{ a.location }}</td>
                        <td><Badge :variant="variant[a.effective_status]">{{ ACTIVITY_STATUS[a.effective_status] }}</Badge><div v-if="a.is_status_manual" class="muted small">diatur manual</div></td>
                        <td><div class="actions"><Button size="sm" @click="openForm(a)">Edit</Button><Button size="sm" variant="ghost" @click="target = a">Hapus</Button></div></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <EmptyState v-else title="Belum ada kegiatan" description="Tambahkan kegiatan sekolah agar tampil pada panel “Kegiatan berlangsung” di TV dan website.">
            <Button variant="primary" @click="openForm()">Tambah kegiatan</Button>
        </EmptyState>
    </div>

    <Modal :open="open" wide :title="editing ? 'Edit kegiatan' : 'Tambah kegiatan'" @close="open = false">
        <Input v-model="form.title" label="Nama kegiatan" required :error="form.errors.title" />
        <Input v-model="form.description" as="textarea" :rows="3" label="Deskripsi" />
        <div class="grid-2">
            <Input v-model="form.start_at" type="datetime-local" label="Waktu mulai" :error="form.errors.start_at" />
            <Input v-model="form.end_at" type="datetime-local" label="Waktu selesai" hint="Kosong = berlangsung sampai akhir hari." :error="form.errors.end_at" />
        </div>
        <Input v-model="form.time_description" label="Keterangan waktu" placeholder="Contoh: Selesai" />
        <div class="grid-2">
            <Input v-model="form.location" label="Lokasi" />
            <Input v-model="form.responsible_person" label="Penanggung jawab" />
        </div>
        <Check v-model="form.is_status_manual" label="Atur status secara manual" description="Bila dimatikan, status ditentukan otomatis dari waktu mulai dan selesai." />
        <Select v-if="form.is_status_manual" v-model="form.status" label="Status" :options="Object.entries(ACTIVITY_STATUS).map(([value, label]) => ({ value, label }))" />
        <fieldset class="stack stack--sm"><legend>Penayangan</legend><Check v-model="form.show_on_website" label="Tampil di website" /><Check v-model="form.show_on_tv" label="Tampil di TV Display" /></fieldset>
        <template #footer><Button @click="open = false">Batal</Button><Button variant="primary" :disabled="form.processing" @click="save">Simpan</Button></template>
    </Modal>
    <ConfirmModal :open="!!target" danger title="Hapus kegiatan?" :message="`Kegiatan “${target?.title}” akan dihapus permanen.`" confirm-label="Hapus" @close="target = null" @confirm="destroy" />
</template>
