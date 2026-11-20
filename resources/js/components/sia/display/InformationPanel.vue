<script setup>
const props = defineProps({ item: Object, school: Object })
const flag = { important: 'Penting', urgent: 'Mendesak' }
</script>

<template>
    <div class="tv-info">
        <template v-if="item.kind === 'announcement'">
            <div class="tv-info__kind">Pengumuman</div>
            <span v-if="flag[item.priority]" class="tv-status tv-info__flag" :class="`tv-status--${item.priority}`">{{ flag[item.priority] }}</span>
            <h2 class="tv-info__title">{{ item.title }}</h2>
            <p class="tv-info__body" style="white-space: pre-line">{{ item.content }}</p>
            <div class="tv-info__meta">{{ item.date }}</div>
        </template>

        <template v-else-if="item.kind === 'activity'">
            <div class="tv-info__kind">Kegiatan berlangsung</div>
            <h2 class="tv-info__title">{{ item.title }}</h2>
            <p v-if="item.description" class="tv-info__body" style="-webkit-line-clamp: 5">{{ item.description }}</p>
            <div class="tv-info__meta">
                <div><b>{{ item.time_label }}</b></div>
                <div v-if="item.location" style="margin-top: 6px">{{ item.location }}</div>
                <div v-if="item.responsible_person" style="margin-top: 6px">Penanggung jawab: {{ item.responsible_person }}</div>
            </div>
        </template>

        <template v-else-if="item.kind === 'weather'">
            <div class="tv-info__kind">Cuaca</div>
            <h2 class="tv-info__title" style="font-size: 30px">{{ item.location }}</h2>
            <div class="tv-weather"><span class="tv-weather__temp tv-num">{{ item.temperature }}°C</span><span class="tv-weather__cond">{{ item.condition }}</span></div>
            <div class="tv-info__meta"><span v-if="item.humidity !== null && item.humidity !== undefined">Kelembapan {{ item.humidity }}%. </span>Diperbarui {{ item.updated_at }}</div>
        </template>

        <template v-else-if="item.kind === 'school_info'">
            <div class="tv-info__kind">Informasi sekolah</div>
            <p class="tv-info__title" style="font-weight: 600">{{ item.text }}</p>
        </template>

        <template v-else>
            <div class="tv-info__kind">{{ school.name }}</div>
            <h2 class="tv-info__title">Selamat datang</h2>
            <p class="tv-info__body">{{ school.tagline || 'Informasi kegiatan sekolah ditampilkan di layar ini.' }}</p>
        </template>
    </div>
</template>
