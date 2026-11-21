<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import { computed } from 'vue'
import { Check, Loader } from 'lucide-vue-next'
import AdminLayout from '../../../../components/sia/layout/AdminLayout.vue'
import PageHeader from '../../../../components/sia/layout/PageHeader.vue'
import Button from '../../../../components/sia/ui/Button.vue'
import Input from '../../../../components/sia/ui/Input.vue'
import CheckField from '../../../../components/sia/ui/Check.vue'

defineOptions({ layout: AdminLayout })
defineProps({ aiEnabled: Boolean, aiDriver: String })
const form = useForm({ text: '', use_ai: true })
const submit = () => form.post('/admin/agenda/import')
const steps = ['Membaca teks', 'Mengidentifikasi tanggal', 'Memisahkan kegiatan', 'Menyusun informasi']
const chars = computed(() => form.text.length)
</script>

<template>
    <Head title="Import WhatsApp" />
    <PageHeader title="Import agenda" description="Ubah teks WhatsApp menjadi agenda terstruktur. Hasilnya selalu disimpan sebagai draft untuk Anda periksa." />

    <form v-if="!form.processing" class="stack" style="max-width: 860px" @submit.prevent="submit">
        <Input v-model="form.text" as="textarea" :rows="18" label="Teks agenda dari WhatsApp" placeholder="Tempelkan teks agenda di sini" :error="form.errors.text" style="font-family: inherit" />
        <CheckField v-if="aiEnabled" v-model="form.use_ai" label="Izinkan AI memeriksa ulang bagian yang meragukan" :description="`Bila ada tanggal, judul, atau waktu yang tidak jelas, teks dikirim ke penyedia AI (${aiDriver}) untuk dibantu. Bagian yang diubah AI tetap ditandai untuk Anda periksa.`" />
        <p class="muted small">Sistem akan mengenali tanggal, kegiatan, waktu, lokasi, dan informasi pendukung. Teks asli tetap disimpan sebagai referensi.</p>
        <div class="row row--between">
            <span class="muted small tnum">{{ chars }} karakter</span>
            <Button type="submit" variant="primary" :disabled="chars < 20">Proses teks</Button>
        </div>
    </form>

    <section v-else class="panel" style="max-width: 560px">
        <div class="panel__body stack">
            <h2 class="panel__title">Memproses agenda...</h2>
            <ol class="steps">
                <li v-for="(s, i) in steps" :key="s" class="is-done"><Loader v-if="i === steps.length - 1" :size="16" /><Check v-else :size="16" />{{ i + 1 }}. {{ s }}</li>
            </ol>
        </div>
    </section>
</template>
