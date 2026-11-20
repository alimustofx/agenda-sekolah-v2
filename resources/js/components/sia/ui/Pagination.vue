<script setup>
import { Link } from '@inertiajs/vue3'

defineProps({ links: { type: Array, default: () => [] } })

const label = (l) => (/Previous/i.test(l) ? 'Sebelumnya' : /Next/i.test(l) ? 'Berikutnya' : l)
</script>

<template>
    <nav v-if="links.length > 3" class="pager" aria-label="Halaman">
        <template v-for="(l, i) in links" :key="i">
            <Link v-if="l.url && !l.active" :href="l.url" preserve-scroll>{{ label(l.label) }}</Link>
            <span v-else :class="l.active ? 'is-current' : 'is-disabled'" :aria-current="l.active ? 'page' : undefined">{{ label(l.label) }}</span>
        </template>
    </nav>
</template>
