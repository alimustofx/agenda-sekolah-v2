<script setup>
import { Link } from '@inertiajs/vue3'
import { PRIORITY } from '../../../lib/sia'

defineProps({ item: Object, truncate: { type: Boolean, default: true } })
</script>

<template>
    <article class="notice" :class="item.priority !== 'normal' && `notice--${item.priority}`">
        <span class="notice__flag" :class="`notice__flag--${item.priority}`">{{ item.priority === 'normal' ? 'Pengumuman' : PRIORITY[item.priority] }}</span>
        <h3 class="notice__title">{{ item.title }}</h3>
        <p class="notice__date">{{ item.date }}<template v-if="item.category"> — {{ item.category }}</template></p>
        <p class="pre-line" :style="truncate ? 'display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden' : ''">{{ item.content }}</p>
        <Link v-if="truncate" :href="`/pengumuman/${item.id}`" class="small" style="display: inline-block; margin-top: 8px; font-weight: 600">Baca selengkapnya</Link>
    </article>
</template>
