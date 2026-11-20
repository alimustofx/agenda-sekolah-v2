<script setup>
import { Head, router, useForm, usePage } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import AdminLayout from '../../../../components/sia/layout/AdminLayout.vue'
import PageHeader from '../../../../components/sia/layout/PageHeader.vue'
import Button from '../../../../components/sia/ui/Button.vue'
import Badge from '../../../../components/sia/ui/Badge.vue'
import Input from '../../../../components/sia/ui/Input.vue'
import Select from '../../../../components/sia/ui/Select.vue'
import Check from '../../../../components/sia/ui/Check.vue'
import Modal from '../../../../components/sia/ui/Modal.vue'
import ConfirmModal from '../../../../components/sia/ui/ConfirmModal.vue'

defineOptions({ layout: AdminLayout })
defineProps({ users: Array })
const me = computed(() => usePage().props.sia.user)

const open = ref(false)
const editing = ref(null)
const target = ref(null)
const blank = { name: '', email: '', role: 'admin_humas', is_active: true, password: '' }
const form = useForm({ ...blank })
const roles = [{ value: 'admin_humas', label: 'Admin Humas' }, { value: 'super_admin', label: 'Super Admin' }]

function openForm(u = null) {
    editing.value = u
    form.clearErrors()
    form.defaults({ ...blank })
    form.reset()
    if (u) Object.assign(form, { name: u.name, email: u.email, role: u.role, is_active: u.is_active, password: '' })
    open.value = true
}
function save() {
    const opts = { preserveScroll: true, onSuccess: () => (open.value = false) }
    editing.value ? form.put(`/admin/administrator/${editing.value.id}`, opts) : form.post('/admin/administrator', opts)
}
const destroy = () => router.delete(`/admin/administrator/${target.value.id}`, { preserveScroll: true, onFinish: () => (target.value = null) })
</script>

<template>
    <Head title="Administrator" />
    <PageHeader title="Administrator" description="Kelola akun yang dapat masuk ke dashboard.">
        <Button variant="primary" @click="openForm()">Tambah akun</Button>
    </PageHeader>
    <div class="panel table-wrap">
        <table class="table">
            <thead><tr><th>Nama</th><th>Peran</th><th>Terakhir masuk</th><th>Status</th><th><span class="sr-only">Aksi</span></th></tr></thead>
            <tbody>
                <tr v-for="u in users" :key="u.id">
                    <td><div class="cell-title">{{ u.name }}<span v-if="u.id === me.id" class="muted small"> (Anda)</span></div><div class="muted small">{{ u.email }}</div></td>
                    <td>{{ u.role === 'super_admin' ? 'Super Admin' : 'Admin Humas' }}</td>
                    <td class="small tnum">{{ u.last_login_at || 'Belum pernah' }}</td>
                    <td><Badge :variant="u.is_active ? 'success' : 'neutral'">{{ u.is_active ? 'Aktif' : 'Nonaktif' }}</Badge></td>
                    <td><div class="actions"><Button size="sm" @click="openForm(u)">Edit</Button><Button v-if="u.id !== me.id" size="sm" variant="ghost" @click="target = u">Hapus</Button></div></td>
                </tr>
            </tbody>
        </table>
    </div>

    <Modal :open="open" :title="editing ? 'Edit akun' : 'Tambah akun'" @close="open = false">
        <Input v-model="form.name" label="Nama" required :error="form.errors.name" />
        <Input v-model="form.email" type="email" label="Email" required :error="form.errors.email" />
        <Select v-model="form.role" label="Peran" :options="roles" :error="form.errors.role" />
        <Input v-model="form.password" type="password" autocomplete="new-password" :label="editing ? 'Kata sandi baru' : 'Kata sandi'" :required="!editing" :hint="editing ? 'Kosongkan bila tidak diubah.' : 'Minimal 8 karakter.'" :error="form.errors.password" />
        <Check v-if="editing" v-model="form.is_active" label="Akun aktif" description="Akun nonaktif tidak dapat masuk." />
        <template #footer><Button @click="open = false">Batal</Button><Button variant="primary" :disabled="form.processing" @click="save">Simpan</Button></template>
    </Modal>
    <ConfirmModal :open="!!target" danger title="Hapus akun?" :message="`Akun ${target?.email} akan dihapus permanen. Untuk menahan akses sementara, nonaktifkan akun saja.`" confirm-label="Hapus akun" @close="target = null" @confirm="destroy" />
</template>
