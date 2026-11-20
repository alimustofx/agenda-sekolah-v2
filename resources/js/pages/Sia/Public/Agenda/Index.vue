<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { reactive } from 'vue'
import PublicLayout from '../../../../components/sia/layout/PublicLayout.vue'
import Input from '../../../../components/sia/ui/Input.vue'
import Select from '../../../../components/sia/ui/Select.vue'
import Button from '../../../../components/sia/ui/Button.vue'
import Pagination from '../../../../components/sia/ui/Pagination.vue'
import EmptyState from '../../../../components/sia/ui/EmptyState.vue'

defineOptions({ layout: PublicLayout })
const props = defineProps({ agendas: Object, filters: Object, categories: Array })
const f = reactive({ q: props.filters.q || '', date: props.filters.date || '', category: props.filters.category || '', scope: props.filters.scope })

function apply(scope) {
    if (scope) f.scope = scope
    router.get('/agenda', Object.fromEntries(Object.entries(f).filter(([, v]) => v)), { preserveState: true, preserveScroll: true })
}
const scopes = [['mendatang', 'Mendatang'], ['lalu', 'Sebelumnya'], ['semua', 'Semua']]
</script>

<template>
    <Head title="Agenda" />
    <div class="container">
        <section class="masthead" style="padding-bottom: 8px; border-bottom: 0">
            <h1 class="masthead__title" style="font-size: clamp(28px, 4vw, 40px)">Agenda sekolah</h1>
            <p class="muted" style="margin-top: 8px">Cari agenda berdasarkan judul kegiatan, tanggal, atau kategori.</p>
        </section>
        <div class="tabs" role="tablist">
            <button v-for="[k, l] in scopes" :key="k" type="button" class="tab" :class="{ 'tab--active': f.scope === k }" @click="apply(k)">{{ l }}</button>
            <Link href="/agenda/kalender" class="tab">Kalender</Link>
        </div>
        <form class="filters" @submit.prevent="apply()">
            <Input v-model="f.q" label="Cari" placeholder="Judul agenda atau kegiatan" />
            <Input v-model="f.date" type="date" label="Tanggal" />
            <Select v-model="f.category" label="Kategori" placeholder="Semua kategori" :options="categories.map((c) => ({ value: c.id, label: c.name }))" />
            <Button type="submit" variant="primary">Terapkan</Button>
        </form>

        <template v-if="agendas.data.length">
            <Link v-for="a in agendas.data" :key="a.id" :href="`/agenda/${a.id}`" class="list-row" style="text-decoration: none">
                <span class="list-row__date">{{ a.long_date }}</span>
                <span class="list-row__title">{{ a.title }}</span>
                <span class="muted small">{{ a.items_count }} kegiatan</span>
            </Link>
            <Pagination :links="agendas.links" />
        </template>
        <EmptyState v-else title="Agenda tidak ditemukan" description="Coba ubah kata kunci, tanggal, atau kategori pencarian.">
            <Button href="/agenda">Atur ulang pencarian</Button>
        </EmptyState>
    </div>
</template>
