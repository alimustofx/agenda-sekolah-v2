<script setup>
import { Head } from '@inertiajs/vue3'
import AdminLayout from '../../../../components/sia/layout/AdminLayout.vue'
import PageHeader from '../../../../components/sia/layout/PageHeader.vue'
import AgendaEditor from '../../../../components/sia/agenda/AgendaEditor.vue'
import ParsingFieldStatus from '../../../../components/sia/parser/ParsingFieldStatus.vue'
import Button from '../../../../components/sia/ui/Button.vue'

defineOptions({ layout: AdminLayout })
const props = defineProps({ logId: Number, original: String, parserType: String, aiError: String, greeting: String, agenda: Object, statuses: Object, summary: Object, existing: Array, categories: Array })
</script>

<template>
    <Head title="Periksa Hasil Import" />
    <PageHeader title="Periksa hasil import" description="Bandingkan teks asli dengan hasil ekstraksi, koreksi bila perlu, lalu simpan sebagai draft.">
        <template #crumbs><a href="/admin/agenda">Agenda</a> / <a href="/admin/agenda/import">Import</a> / Periksa</template>
        <Button href="/admin/agenda/import">Ulangi import</Button>
    </PageHeader>

    <div class="row" style="margin-bottom: 20px; gap: 8px">
        <ParsingFieldStatus state="ok" :label="`${summary.ok} dikenali`" />
        <ParsingFieldStatus state="warn" :label="`${summary.warn} perlu diperiksa`" />
        <ParsingFieldStatus state="missing" :label="`${summary.missing} belum lengkap`" />
        <ParsingFieldStatus v-if="summary.invalid" state="invalid" :label="`${summary.invalid} tidak valid`" />
        <ParsingFieldStatus v-if="parserType === 'hybrid'" state="ok" label="Dibantu AI" />
        <span class="muted small">Penanda hanya membantu validasi, bukan jaminan benar atau salah.</span>
    </div>

    <p v-if="aiError" class="alert alert--warn" style="margin-bottom: 20px">Pemeriksaan AI tidak dapat dipakai ({{ aiError }}). Hasil di bawah memakai parser aturan.</p>

    <p v-if="existing.length" class="alert alert--warn" style="margin-bottom: 20px">
        Sudah ada {{ existing.length }} agenda pada tanggal ini:
        <template v-for="(e, i) in existing" :key="e.id"><a :href="`/admin/agenda/${e.id}/edit`">{{ e.title }}</a> ({{ e.status }})<template v-if="i < existing.length - 1">, </template></template>.
        Pastikan tidak terjadi duplikasi sebelum menerbitkan.
    </p>

    <div class="split">
        <section class="panel" style="position: sticky; top: 24px">
            <div class="panel__head"><h2 class="panel__title">Teks asli</h2></div>
            <div class="panel__body"><pre class="source-text">{{ original }}</pre></div>
        </section>
        <div>
            <AgendaEditor :agenda="agenda" :categories="categories" :statuses="statuses" :action="`/admin/agenda/import/${logId}`" method="post" draft-only />
        </div>
    </div>
</template>
