<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import { onBeforeUnmount, onMounted, ref } from 'vue'
import AdminLayout from '../../../../components/sia/layout/AdminLayout.vue'
import PageHeader from '../../../../components/sia/layout/PageHeader.vue'
import Button from '../../../../components/sia/ui/Button.vue'
import Input from '../../../../components/sia/ui/Input.vue'
import Check from '../../../../components/sia/ui/Check.vue'

defineOptions({ layout: AdminLayout })
const props = defineProps({ settings: Object })
const s = props.settings
const form = useForm({
    display_title: s.display_title, agenda_cards_per_page: s.agenda_cards_per_page, pagination_interval: s.pagination_interval,
    pagination_min: s.pagination_min, pagination_max: s.pagination_max, adaptive_pagination: !!s.adaptive_pagination,
    video_panel_enabled: !!s.video_panel_enabled, information_panel_enabled: !!s.information_panel_enabled,
    weather_enabled: !!s.weather_enabled, running_text_enabled: !!s.running_text_enabled,
    panel_modes: [...(s.panel_modes || [])], school_info_text: s.school_info_text || '', refresh_interval: s.refresh_interval,
})
const modeList = [
    ['announcement', 'Pengumuman', 'Pengumuman aktif; yang mendesak tayang lebih sering.'],
    ['activity', 'Kegiatan berlangsung', 'Kegiatan sekolah yang sedang berlangsung.'],
    ['school_info', 'Informasi sekolah', 'Teks informasi tetap yang diisi di bawah.'],
]
function toggleMode(k, on) {
    form.panel_modes = on ? [...new Set([...form.panel_modes, k])] : form.panel_modes.filter((m) => m !== k)
}
const save = () => form.put('/admin/tv', { preserveScroll: true })

// Pratinjau TV: kanvas 1920×1080 diskalakan ke lebar kontainer.
const box = ref(null)
const scale = ref(0.3)
let ro
onMounted(() => {
    ro = new ResizeObserver(() => box.value && (scale.value = box.value.clientWidth / 1920))
    ro.observe(box.value)
})
onBeforeUnmount(() => ro?.disconnect())
</script>

<template>
    <Head title="TV Display" />
    <PageHeader title="TV Display" description="Atur tampilan layar informasi di ruang resepsionis. Perubahan sampai ke TV dalam beberapa detik tanpa memuat ulang halaman.">
        <Button href="/admin/tv/running-text">Running text</Button>
        <a class="btn btn--primary" href="/display" target="_blank" rel="noopener">Buka TV Display</a>
    </PageHeader>

    <div class="editor-grid" style="grid-template-columns: minmax(0, 1fr) 460px">
        <form class="stack stack--lg" @submit.prevent="save">
            <section class="panel">
                <div class="panel__head"><h2 class="panel__title">Agenda &amp; pagination</h2></div>
                <div class="panel__body stack">
                    <Input v-model="form.display_title" label="Judul area agenda" :error="form.errors.display_title" />
                    <div class="grid-3">
                        <Input v-model="form.agenda_cards_per_page" type="number" min="1" max="4" label="Kartu per halaman" :error="form.errors.agenda_cards_per_page" hint="Bawaan 3." />
                        <Input v-model="form.pagination_interval" type="number" min="5" label="Durasi dasar (detik)" :error="form.errors.pagination_interval" />
                        <Input v-model="form.refresh_interval" type="number" min="10" label="Cek data baru (detik)" :error="form.errors.refresh_interval" />
                    </div>
                    <Check v-model="form.adaptive_pagination" label="Durasi adaptif" description="Halaman dengan teks lebih panjang tampil lebih lama, dalam batas minimum dan maksimum." />
                    <div class="grid-2">
                        <Input v-model="form.pagination_min" type="number" min="5" label="Durasi minimum (detik)" :error="form.errors.pagination_min" />
                        <Input v-model="form.pagination_max" type="number" min="5" label="Durasi maksimum (detik)" :error="form.errors.pagination_max" />
                    </div>
                </div>
            </section>

            <section class="panel">
                <div class="panel__head"><h2 class="panel__title">Panel informasi</h2></div>
                <div class="panel__body stack">
                    <Check v-model="form.video_panel_enabled" label="Panel video & informasi aktif" description="Bila dimatikan, agenda tampil selebar layar." />
                    <Check v-model="form.information_panel_enabled" label="Tampilkan informasi di antara video" />
                    <fieldset class="stack stack--sm">
                        <legend>Konten yang bergantian di panel</legend>
                        <Check v-for="[k, l, d] in modeList" :key="k" :model-value="form.panel_modes.includes(k)" :label="l" :description="d" @update:model-value="toggleMode(k, $event)" />
                    </fieldset>
                    <Check v-model="form.weather_enabled" label="Tampilkan cuaca di header TV" description="Perlu integrasi cuaca aktif di menu Pengaturan." />
                    <Input v-model="form.school_info_text" as="textarea" :rows="3" label="Informasi sekolah" hint="Contoh: visi, motto, atau tata tertib singkat. Satu butir per tayang." :error="form.errors.school_info_text" />
                </div>
            </section>

            <section class="panel">
                <div class="panel__head"><h2 class="panel__title">Footer</h2></div>
                <div class="panel__body"><Check v-model="form.running_text_enabled" label="Tampilkan running text" description="Isi dan gaya running text diatur di menu Running Text." /></div>
                <div class="panel__foot"><Button type="submit" variant="primary" :disabled="form.processing">Simpan pengaturan</Button></div>
            </section>
        </form>

        <aside class="editor-side">
            <h2 class="field__label" style="margin-bottom: 8px">Pratinjau (data terbitan saat ini)</h2>
            <div ref="box" style="position: relative; width: 100%; aspect-ratio: 16 / 9; overflow: hidden; border: 1px solid var(--sia-stone-200); border-radius: 10px; background: var(--sia-navy-950)">
                <iframe src="/display?preview" title="Pratinjau TV Display" tabindex="-1" :style="{ width: '1920px', height: '1080px', border: 0, transform: `scale(${scale})`, transformOrigin: 'top left', pointerEvents: 'none' }" />
            </div>
            <p class="muted small" style="margin-top: 8px">Pratinjau memuat ulang setelah halaman ini dimuat ulang. Video dibisukan.</p>
        </aside>
    </div>
</template>
