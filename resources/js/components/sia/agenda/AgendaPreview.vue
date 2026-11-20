<script setup>
import { computed } from 'vue'
import { dot, longDate, timeLabel } from '../../../lib/sia'

const props = defineProps({ agenda: Object })
const items = computed(() => (props.agenda.items || []).filter((i) => i.title))
</script>

<template>
    <div class="preview">
        <p class="small muted">{{ agenda.agenda_date ? longDate(agenda.agenda_date) : 'Tanggal belum diisi' }}</p>
        <h3>{{ agenda.title || 'Judul agenda' }}</h3>
        <p v-if="agenda.uniform_information" class="small" style="margin-top: 8px"><b>Pakaian dinas:</b> {{ agenda.uniform_information }}</p>
        <ol v-if="items.length">
            <li v-for="(i, n) in items" :key="i._key || n">
                <time>{{ timeLabel(i) }}</time>
                <div>
                    <strong style="color: var(--sia-stone-900)">{{ i.title }}</strong>
                    <div v-if="i.location" class="muted small">{{ i.location }}</div>
                </div>
            </li>
        </ol>
        <p v-else class="muted small" style="margin-top: 12px">Belum ada kegiatan.</p>
    </div>
</template>
