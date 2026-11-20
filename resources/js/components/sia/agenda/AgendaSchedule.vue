<script setup>
import Badge from '../ui/Badge.vue'
import { PRIORITY, priorityVariant } from '../../../lib/sia'

defineProps({ items: { type: Array, default: () => [] }, detailed: Boolean })
</script>

<template>
    <ol class="schedule">
        <li v-for="item in items" :key="item.id" class="schedule__item">
            <div class="schedule__time">{{ item.time_label }}</div>
            <div>
                <h3 class="schedule__title">{{ item.title }}</h3>
                <div class="schedule__meta">
                    <span v-if="item.location"><b>Lokasi</b> {{ item.location }}</span>
                    <span v-if="item.responsible_person"><b>{{ item.responsible_label || 'Penanggung jawab' }}</b> {{ item.responsible_person }}</span>
                    <template v-if="detailed">
                        <span v-if="item.officers"><b>Petugas</b> {{ item.officers }}</span>
                        <span v-if="item.participants"><b>Peserta</b> {{ item.participants }}</span>
                    </template>
                </div>
                <p v-if="detailed && item.description" class="pre-line" style="margin-top: 8px">{{ item.description }}</p>
                <p v-if="detailed && item.additional_notes" class="pre-line muted small" style="margin-top: 6px">{{ item.additional_notes }}</p>
                <div v-if="item.category || item.priority !== 'normal'" class="schedule__tags">
                    <Badge v-if="item.category">{{ item.category.name }}</Badge>
                    <Badge v-if="item.priority !== 'normal'" :variant="priorityVariant(item.priority)">{{ PRIORITY[item.priority] }}</Badge>
                </div>
            </div>
        </li>
    </ol>
</template>
