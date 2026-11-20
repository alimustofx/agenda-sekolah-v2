<script setup>
import { computed } from 'vue'

const props = defineProps({ item: Object, school: Object, index: Number, total: Number })
const flag = { important: 'Penting', urgent: 'Mendesak' }

// Teks pendek dibuat lebih besar, teks panjang sedikit lebih kecil agar ruang terisi seimbang.
const size = computed(() => {
    const i = props.item
    const len = [i.title, i.content, i.description, i.text].filter(Boolean).join(' ').length
    return len < 130 ? 'tv-notice--lg' : len > 340 ? 'tv-notice--sm' : ''
})
</script>

<template>
    <div class="tv-notice" :class="size">
        <div class="tv-notice__top">
            <span class="tv-notice__kind">{{ { announcement: 'Pengumuman', activity: 'Kegiatan berlangsung', school_info: 'Informasi sekolah' }[item.kind] || 'Selamat datang' }}</span>
            <span v-if="total > 1" class="tv-notice__count">{{ index + 1 }} dari {{ total }}</span>
        </div>

        <template v-if="item.kind === 'announcement'">
            <span v-if="flag[item.priority]" class="tv-flag" :class="`tv-flag--${item.priority}`">{{ flag[item.priority] }}</span>
            <h2 class="tv-notice__title">{{ item.title }}</h2>
            <p class="tv-notice__body">{{ item.content }}</p>
            <div class="tv-notice__meta">{{ item.date }}<template v-if="item.category">, {{ item.category }}</template></div>
        </template>

        <template v-else-if="item.kind === 'activity'">
            <h2 class="tv-notice__title">{{ item.title }}</h2>
            <p v-if="item.description" class="tv-notice__body">{{ item.description }}</p>
            <div class="tv-notice__meta">
                <div><b>{{ item.time_label }}</b></div>
                <div v-if="item.location">{{ item.location }}</div>
                <div v-if="item.responsible_person">Penanggung jawab: {{ item.responsible_person }}</div>
            </div>
        </template>

        <template v-else-if="item.kind === 'school_info'">
            <p class="tv-notice__quote">{{ item.text }}</p>
        </template>

        <template v-else>
            <h2 class="tv-notice__title">{{ school.name }}</h2>
            <p class="tv-notice__body">{{ school.tagline || 'Informasi kegiatan sekolah ditampilkan di layar ini.' }}</p>
        </template>
    </div>
</template>
