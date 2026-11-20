<script setup>
import { Head, router, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'
import AdminLayout from '../../../../components/sia/layout/AdminLayout.vue'
import PageHeader from '../../../../components/sia/layout/PageHeader.vue'
import Button from '../../../../components/sia/ui/Button.vue'
import Badge from '../../../../components/sia/ui/Badge.vue'
import Input from '../../../../components/sia/ui/Input.vue'
import Check from '../../../../components/sia/ui/Check.vue'
import Modal from '../../../../components/sia/ui/Modal.vue'
import ConfirmModal from '../../../../components/sia/ui/ConfirmModal.vue'

defineOptions({ layout: AdminLayout })
defineProps({ categories: Array })

const open = ref(false)
const editing = ref(null)
const target = ref(null)
const form = useForm({ name: '', description: '', color: '#286A91', is_active: true })

function openForm(c = null) {
    editing.value = c
    form.reset()
    form.clearErrors()
    if (c) Object.assign(form, { name: c.name, description: c.description || '', color: c.color, is_active: !!c.is_active })
    open.value = true
}
function save() {
    const opts = { preserveScroll: true, onSuccess: () => (open.value = false) }
    editing.value ? form.put(`/admin/agenda/kategori/${editing.value.id}`, opts) : form.post('/admin/agenda/kategori', opts)
}
const destroy = () => router.delete(`/admin/agenda/kategori/${target.value.id}`, { onFinish: () => (target.value = null) })
</script>

<template>
    <Head title="Kategori Agenda" />
    <PageHeader title="Kategori agenda" description="Kelompokkan kegiatan agar mudah dicari dan dibaca.">
        <Button variant="primary" @click="openForm()">Tambah kategori</Button>
    </PageHeader>
    <div class="panel table-wrap">
        <table class="table">
            <thead><tr><th>Kategori</th><th>Kegiatan</th><th>Status</th><th><span class="sr-only">Aksi</span></th></tr></thead>
            <tbody>
                <tr v-for="c in categories" :key="c.id">
                    <td>
                        <div class="row" style="gap: 10px; flex-wrap: nowrap"><span :style="{ background: c.color, width: '4px', height: '28px', borderRadius: '2px' }" aria-hidden="true" /><div><div class="cell-title">{{ c.name }}</div><div v-if="c.description" class="muted small">{{ c.description }}</div></div></div>
                    </td>
                    <td class="tnum">{{ c.items_count }}</td>
                    <td><Badge :variant="c.is_active ? 'success' : 'neutral'">{{ c.is_active ? 'Aktif' : 'Nonaktif' }}</Badge></td>
                    <td><div class="actions"><Button size="sm" @click="openForm(c)">Edit</Button><Button size="sm" variant="ghost" @click="target = c">Hapus</Button></div></td>
                </tr>
            </tbody>
        </table>
    </div>

    <Modal :open="open" :title="editing ? 'Edit kategori' : 'Tambah kategori'" @close="open = false">
        <Input v-model="form.name" label="Nama kategori" required :error="form.errors.name" />
        <Input v-model="form.description" as="textarea" :rows="2" label="Deskripsi" :error="form.errors.description" />
        <div class="field"><label class="field__label" for="cat-color">Warna penanda</label><input id="cat-color" v-model="form.color" type="color" class="input" /></div>
        <Check v-model="form.is_active" label="Kategori aktif" description="Kategori nonaktif tidak muncul sebagai pilihan baru." />
        <template #footer><Button @click="open = false">Batal</Button><Button variant="primary" :disabled="form.processing" @click="save">Simpan</Button></template>
    </Modal>
    <ConfirmModal :open="!!target" danger title="Hapus kategori?" :message="`Kategori “${target?.name}” akan dihapus. Kegiatan yang memakainya menjadi tanpa kategori.`" confirm-label="Hapus" @close="target = null" @confirm="destroy" />
</template>
