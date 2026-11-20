<script setup>
import { Head, Link } from '@inertiajs/vue3'
import PublicLayout from '../../../../components/sia/layout/PublicLayout.vue'
import AnnouncementNotice from '../../../../components/sia/announcement/AnnouncementNotice.vue'
import Pagination from '../../../../components/sia/ui/Pagination.vue'
import EmptyState from '../../../../components/sia/ui/EmptyState.vue'

defineOptions({ layout: PublicLayout })
defineProps({ announcements: Object, archive: Boolean })
</script>

<template>
    <Head title="Pengumuman" />
    <div class="container" style="padding-top: 48px; max-width: 800px">
        <h1 class="masthead__title" style="font-size: clamp(28px, 4vw, 40px)">Pengumuman</h1>
        <div class="tabs" style="margin: 24px 0">
            <Link href="/pengumuman" class="tab" :class="{ 'tab--active': !archive }">Aktif</Link>
            <Link href="/pengumuman?arsip=1" class="tab" :class="{ 'tab--active': archive }">Arsip</Link>
        </div>
        <template v-if="announcements.data.length">
            <AnnouncementNotice v-for="a in announcements.data" :key="a.id" :item="a" />
            <Pagination :links="announcements.links" />
        </template>
        <EmptyState v-else :title="archive ? 'Arsip masih kosong' : 'Tidak ada pengumuman aktif'" description="Pengumuman yang diterbitkan sekolah akan muncul di sini." />
    </div>
</template>
