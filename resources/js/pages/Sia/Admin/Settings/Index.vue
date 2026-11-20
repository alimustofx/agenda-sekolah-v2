<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import AdminLayout from '../../../../components/sia/layout/AdminLayout.vue'
import PageHeader from '../../../../components/sia/layout/PageHeader.vue'
import Button from '../../../../components/sia/ui/Button.vue'
import Input from '../../../../components/sia/ui/Input.vue'
import Check from '../../../../components/sia/ui/Check.vue'

defineOptions({ layout: AdminLayout })
const props = defineProps({ school: Object, weather: Object, ai: Object })

const school = useForm({
    school_name: props.school.school_name, tagline: props.school.tagline || '', school_address: props.school.school_address || '',
    website_url: props.school.website_url || '', primary_color: props.school.primary_color, secondary_color: props.school.secondary_color,
    logo: null, remove_logo: false,
})
const weather = useForm({
    location: props.weather.location, latitude: props.weather.latitude, longitude: props.weather.longitude,
    is_enabled: !!props.weather.is_enabled, cache_duration: props.weather.cache_duration,
})
const saveSchool = () => school.post('/admin/pengaturan/sekolah', { forceFormData: true, preserveScroll: true, onSuccess: () => { school.logo = null; school.remove_logo = false } })
const saveWeather = () => weather.put('/admin/pengaturan/cuaca', { preserveScroll: true })
</script>

<template>
    <Head title="Pengaturan" />
    <PageHeader title="Pengaturan" description="Identitas sekolah dan integrasi eksternal. Hanya dapat diubah oleh Super Admin." />
    <div class="stack stack--lg" style="max-width: 860px">
        <form class="panel" @submit.prevent="saveSchool">
            <div class="panel__head"><h2 class="panel__title">Identitas sekolah</h2></div>
            <div class="panel__body stack">
                <Input v-model="school.school_name" label="Nama sekolah" required :error="school.errors.school_name" />
                <Input v-model="school.tagline" label="Subjudul" :error="school.errors.tagline" />
                <Input v-model="school.school_address" as="textarea" :rows="2" label="Alamat" :error="school.errors.school_address" />
                <Input v-model="school.website_url" type="url" label="Alamat website" placeholder="https://" :error="school.errors.website_url" />
                <div class="field">
                    <span class="field__label">Logo</span>
                    <div class="row"><img v-if="props.school.logo_url" :src="props.school.logo_url" alt="Logo saat ini" style="height: 56px; background: #fff; border: 1px solid var(--sia-stone-200); border-radius: 6px; padding: 4px" />
                        <input type="file" accept="image/png,image/jpeg,image/webp,image/svg+xml" class="input" style="max-width: 320px" @change="school.logo = $event.target.files[0] || null" /></div>
                    <Check v-if="props.school.logo_url" v-model="school.remove_logo" label="Hapus logo saat ini" />
                    <p v-if="school.errors.logo" class="field__error">{{ school.errors.logo }}</p>
                    <p class="field__hint">PNG, JPG, WebP, atau SVG, maksimal 2 MB. Jalankan <code>php artisan storage:link</code> bila logo tidak tampil.</p>
                </div>
                <div class="row" style="gap: 24px">
                    <div class="field"><label class="field__label" for="pc">Warna utama</label><input id="pc" v-model="school.primary_color" type="color" class="input" /></div>
                    <div class="field"><label class="field__label" for="sc">Warna aksen</label><input id="sc" v-model="school.secondary_color" type="color" class="input" /></div>
                </div>
            </div>
            <div class="panel__foot"><Button type="submit" variant="primary" :disabled="school.processing">Simpan identitas</Button></div>
        </form>

        <form class="panel" @submit.prevent="saveWeather">
            <div class="panel__head"><div><h2 class="panel__title">Cuaca</h2><p class="panel__desc">Data dari Open-Meteo (tanpa API key), disimpan sementara di server.</p></div></div>
            <div class="panel__body stack">
                <Check v-model="weather.is_enabled" label="Aktifkan integrasi cuaca" description="Cuaca tampil di TV bila juga diaktifkan di menu TV Display." />
                <Input v-model="weather.location" label="Nama lokasi" :error="weather.errors.location" />
                <div class="grid-3">
                    <Input v-model="weather.latitude" type="number" step="0.000001" label="Lintang" :error="weather.errors.latitude" />
                    <Input v-model="weather.longitude" type="number" step="0.000001" label="Bujur" :error="weather.errors.longitude" />
                    <Input v-model="weather.cache_duration" type="number" min="5" label="Pembaruan (menit)" :error="weather.errors.cache_duration" />
                </div>
            </div>
            <div class="panel__foot"><Button type="submit" variant="primary" :disabled="weather.processing">Simpan cuaca</Button></div>
        </form>

        <section class="panel">
            <div class="panel__head"><h2 class="panel__title">Parser AI</h2></div>
            <div class="panel__body"><p>Status: <strong>{{ ai.enabled ? 'Aktif' : 'Belum diaktifkan' }}</strong>. Import WhatsApp memakai parser berbasis aturan. Lapisan AI dapat ditambahkan nanti melalui <code>config/sia.php</code> tanpa mengubah alur kerja admin.</p></div>
        </section>
    </div>
</template>
