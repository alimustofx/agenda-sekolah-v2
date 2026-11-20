<script setup>
import { Head, router, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'
import { ArrowDown, ArrowUp } from 'lucide-vue-next'
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

defineOptions({ layout: AdminLayout })
const props = defineProps({ sources: Array, settings: Object, hasApiKey: Boolean })

const open = ref(false)
const target = ref(null)
const add = useForm({ url: '', type: 'video', title: '' })
const s = useForm({
    playback_enabled: !!props.settings.playback_enabled, autoplay: !!props.settings.autoplay, muted: !!props.settings.muted,
    volume: props.settings.volume, shuffle: !!props.settings.shuffle, loop: !!props.settings.loop,
    intermission_enabled: !!props.settings.intermission_enabled, intermission_duration: props.settings.intermission_duration,
    playback_mode: props.settings.playback_mode,
})

function openAdd(type) {
    add.reset()
    add.clearErrors()
    add.type = type
    open.value = true
}
const saveSource = () => add.post('/admin/youtube', { preserveScroll: true, onSuccess: () => (open.value = false) })
const toggle = (src) => router.patch(`/admin/youtube/${src.id}`, { is_active: !src.is_active }, { preserveScroll: true })
const destroy = () => router.delete(`/admin/youtube/${target.value.id}`, { preserveScroll: true, onFinish: () => (target.value = null) })
function move(i, dir) {
    const ids = props.sources.map((x) => x.id)
    const j = i + dir
    if (j < 0 || j >= ids.length) return
    ;[ids[i], ids[j]] = [ids[j], ids[i]]
    router.post('/admin/youtube/urutan', { ids }, { preserveScroll: true })
}
const saveSettings = () => s.put('/admin/youtube/pengaturan', { preserveScroll: true })
const modes = [
    { value: 'mixed', label: 'Mixed queue — video dan playlist sesuai urutan' },
    { value: 'queue', label: 'Video queue — hanya video' },
    { value: 'playlist', label: 'Playlist — hanya playlist' },
    { value: 'single', label: 'Single video — video aktif pertama' },
]
const dur = (sec) => (sec ? `${Math.floor(sec / 60)}:${String(sec % 60).padStart(2, '0')}` : '')
</script>

<template>
    <Head title="Media YouTube" />
    <PageHeader title="Media YouTube" description="Kelola video dan playlist yang diputar di TV.">
        <Button variant="primary" @click="openAdd('video')">Tambah video</Button>
        <Button @click="openAdd('playlist')">Tambah playlist</Button>
    </PageHeader>

    <div class="stack stack--lg">
        <section class="panel">
            <div class="panel__head"><div><h2 class="panel__title">Sumber pemutaran</h2><p class="panel__desc">Urutan di sini menjadi urutan putar. Sumber nonaktif dilewati.</p></div></div>
            <div v-if="sources.length">
                <div v-for="(src, i) in sources" :key="src.id" class="media-row">
                    <span class="tnum muted">{{ i + 1 }}</span>
                    <img v-if="src.thumbnail_url" :src="src.thumbnail_url" :alt="`Thumbnail ${src.title}`" loading="lazy" />
                    <div v-else class="thumb-empty" />
                    <div style="min-width: 0">
                        <div class="cell-title">{{ src.title }}</div>
                        <div class="muted small">{{ src.source_type === 'playlist' ? 'Playlist' : 'Video' }}<template v-if="src.channel"> — {{ src.channel }}</template><template v-if="src.duration"> — {{ dur(src.duration) }}</template></div>
                        <Badge :variant="src.is_active ? 'success' : 'neutral'" style="margin-top: 6px">{{ src.is_active ? 'Aktif' : 'Nonaktif' }}</Badge>
                    </div>
                    <div class="row" style="gap: 4px; flex-wrap: nowrap">
                        <Button size="sm" class="btn--icon" aria-label="Naikkan" :disabled="i === 0" @click="move(i, -1)"><ArrowUp :size="16" /></Button>
                        <Button size="sm" class="btn--icon" aria-label="Turunkan" :disabled="i === sources.length - 1" @click="move(i, 1)"><ArrowDown :size="16" /></Button>
                        <Button size="sm" @click="toggle(src)">{{ src.is_active ? 'Nonaktifkan' : 'Aktifkan' }}</Button>
                        <Button size="sm" variant="ghost" @click="target = src">Hapus</Button>
                    </div>
                </div>
            </div>
            <EmptyState v-else title="Belum ada video" description="Tambahkan video atau playlist YouTube. Selama kosong, TV menampilkan panel informasi sekolah.">
                <Button variant="primary" @click="openAdd('video')">Tambah video</Button>
            </EmptyState>
        </section>

        <form class="panel" @submit.prevent="saveSettings">
            <div class="panel__head"><div><h2 class="panel__title">Pengaturan pemutaran</h2><p class="panel__desc">Autoplay di browser hanya berjalan bila video dibisukan.</p></div></div>
            <div class="panel__body stack stack--lg">
                <fieldset class="stack stack--sm">
                    <legend>Pemutaran</legend>
                    <Check v-model="s.playback_enabled" label="Pemutaran video aktif" />
                    <Check v-model="s.autoplay" label="Autoplay" />
                    <Check v-model="s.muted" label="Bisukan suara (disarankan)" />
                    <Check v-model="s.shuffle" label="Acak urutan" />
                    <Check v-model="s.loop" label="Ulangi dari awal setelah semua selesai" />
                    <Input v-model="s.volume" type="number" min="0" max="100" label="Volume (0–100)" :error="s.errors.volume" style="max-width: 180px" />
                </fieldset>
                <fieldset class="stack stack--sm">
                    <legend>Jeda informasi</legend>
                    <Check v-model="s.intermission_enabled" label="Tampilkan panel informasi setelah video selesai" />
                    <Input v-model="s.intermission_duration" type="number" min="5" max="300" label="Durasi jeda (detik)" :error="s.errors.intermission_duration" style="max-width: 180px" />
                </fieldset>
                <fieldset class="stack stack--sm">
                    <legend>Antrean</legend>
                    <Select v-model="s.playback_mode" label="Mode pemutaran" :options="modes" />
                    <p class="muted small">Playlist dibaca sebagai daftar video, lalu tiap video diselingi informasi sekolah.
                        <template v-if="!hasApiKey"> YouTube API key belum diisi; daftar video playlist dibaca langsung oleh pemutar di TV.</template></p>
                </fieldset>
            </div>
            <div class="panel__foot"><Button type="submit" variant="primary" :disabled="s.processing">Simpan pengaturan</Button></div>
        </form>
    </div>

    <Modal :open="open" :title="add.type === 'video' ? 'Tambah video' : 'Tambah playlist'" @close="open = false">
        <Input v-model="add.url" label="URL YouTube" required :placeholder="add.type === 'video' ? 'https://www.youtube.com/watch?v=...' : 'https://www.youtube.com/playlist?list=...'" :error="add.errors.url" hint="Judul dan thumbnail diambil otomatis bila tersedia. Playlist harus publik." />
        <Input v-model="add.title" label="Judul (opsional)" hint="Kosongkan untuk memakai judul dari YouTube." />
        <template #footer><Button @click="open = false">Batal</Button><Button variant="primary" :disabled="add.processing" @click="saveSource">Tambahkan</Button></template>
    </Modal>
    <ConfirmModal :open="!!target" danger title="Hapus sumber?" :message="`“${target?.title}” akan dihapus dari antrean TV.`" confirm-label="Hapus" @close="target = null" @confirm="destroy" />
</template>
