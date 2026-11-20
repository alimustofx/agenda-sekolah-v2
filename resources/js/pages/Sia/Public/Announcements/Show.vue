<script setup>
import { Head, Link } from '@inertiajs/vue3'
import PublicLayout from '../../../../components/sia/layout/PublicLayout.vue'
import AnnouncementNotice from '../../../../components/sia/announcement/AnnouncementNotice.vue'

defineOptions({ layout: PublicLayout })
defineProps({ announcement: Object })
</script>

<template>
    <Head :title="announcement.title" />
    <div class="container" style="padding-top: 40px; max-width: 760px">
        <p class="crumbs"><Link href="/pengumuman">Pengumuman</Link></p>
        <p v-if="announcement.state === 'berakhir'" class="alert alert--info" style="margin-bottom: 16px">Pengumuman ini sudah berakhir dan disimpan sebagai arsip.</p>
        <AnnouncementNotice :item="announcement" :truncate="false" />
    </div>
</template>
