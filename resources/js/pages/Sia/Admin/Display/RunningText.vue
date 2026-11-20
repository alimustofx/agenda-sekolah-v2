<script setup>
import { Head, router, useForm } from '@inertiajs/vue3'
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
import { priorityOptions } from '../../../../lib/sia'

defineOptions({ layout: AdminLayout })
defineProps({ texts: Array })

const open = ref(false)
const editing = ref(null)
const target = ref(null)
const blank = { content: '', speed: 90, text_color: '#FFFFFF', background_color: '#17324D', priority: 'normal', start_at: '', end_at: '', display_order: '', is_active: true }
const form = useForm({ ...blank })

function openForm(t = null) {
    editing.value = t
    form.clearErrors()
    form.defaults({ ...blank })
    form.reset()
    if (t) Object.assign(form, { ...blank, ...t, start_at: t.start_at || '', end_at: t.end_at || '' })
    open.value = true
}
function save() {
    const opts = { preserveScroll: true, onSuccess: () => (open.value = false) }
    editing.value ? form.put(`/admin/tv/running-text/${editing.value.id}`, opts) : form.post('/admin/tv/running-text', opts)
}
const destroy = () => router.delete(`/admin/tv/running-text/${target.value.id}`, { preserveScroll: true, onFinish: () => (target.value = null) })
</script>

<template>
    <Head title="Running Text" />
    <PageHeader title="Running text" description="Pesan singkat satu baris di bagian bawah TV. Teks aktif tampil bergantian sesuai urutan.">
        <Button variant="primary" @click="openForm()">Tambah running text</Button>
    </PageHeader>
    <div class="panel">
        <div v-if="texts.length" class="table-wrap">
            <table class="table">
                <thead><tr><th>Urutan</th><th>Isi</th><th>Periode</th><th>Status</th><th><span class="sr-only">Aksi</span></th></tr></thead>
                <tbody>
                    <tr v-for="t in texts" :key="t.id">
                        <td class="tnum">{{ t.display_order }}</td>
                        <td style="max-width: 460px">
                            <div class="small" :style="{ background: t.background_color, color: t.text_color, padding: '8px 12px', borderRadius: '6px' }">{{ t.content }}</div>
                        </td>
                        <td class="small tnum">{{ t.start_at ? t.start_at.replace('T', ' ') : 'Langsung' }}<br />s/d {{ t.end_at ? t.end_at.replace('T', ' ') : 'tanpa batas' }}</td>
                        <td><Badge :variant="t.is_active ? 'success' : 'neutral'">{{ t.is_active ? 'Aktif' : 'Nonaktif' }}</Badge></td>
                        <td><div class="actions"><Button size="sm" @click="openForm(t)">Edit</Button><Button size="sm" variant="ghost" @click="target = t">Hapus</Button></div></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <EmptyState v-else title="Belum ada running text" description="Tambahkan pesan singkat untuk ditampilkan di footer TV."><Button variant="primary" @click="openForm()">Tambah running text</Button></EmptyState>
    </div>

    <Modal :open="open" :title="editing ? 'Edit running text' : 'Tambah running text'" @close="open = false">
        <Input v-model="form.content" as="textarea" :rows="2" label="Isi teks" required hint="Maksimal 300 karakter. Gunakan kalimat singkat." :error="form.errors.content" />
        <div class="grid-2">
            <Input v-model="form.speed" type="number" min="30" max="300" label="Kecepatan (px/detik)" :error="form.errors.speed" />
            <Select v-model="form.priority" label="Prioritas" :options="priorityOptions" />
        </div>
        <div class="row" style="gap: 24px">
            <div class="field"><label class="field__label" for="rt-c">Warna teks</label><input id="rt-c" v-model="form.text_color" type="color" class="input" /></div>
            <div class="field"><label class="field__label" for="rt-b">Warna latar</label><input id="rt-b" v-model="form.background_color" type="color" class="input" /></div>
            <div class="small" :style="{ background: form.background_color, color: form.text_color, padding: '10px 14px', borderRadius: '6px', alignSelf: 'flex-end', flex: 1 }">Contoh tampilan</div>
        </div>
        <div class="grid-2">
            <Input v-model="form.start_at" type="datetime-local" label="Mulai tayang" :error="form.errors.start_at" />
            <Input v-model="form.end_at" type="datetime-local" label="Berhenti tayang" :error="form.errors.end_at" />
        </div>
        <Input v-model="form.display_order" type="number" min="0" label="Urutan" hint="Kosong = ditempatkan paling akhir." style="max-width: 180px" />
        <Check v-model="form.is_active" label="Aktif" />
        <template #footer><Button @click="open = false">Batal</Button><Button variant="primary" :disabled="form.processing" @click="save">Simpan</Button></template>
    </Modal>
    <ConfirmModal :open="!!target" danger title="Hapus running text?" message="Teks ini akan dihapus dari footer TV." confirm-label="Hapus" @close="target = null" @confirm="destroy" />
</template>
