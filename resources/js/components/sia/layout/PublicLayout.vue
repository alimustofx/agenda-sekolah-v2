<script setup>
import '../../../../css/sia.css'
import { computed, ref, watch } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import { Menu, X } from 'lucide-vue-next'

const page = usePage()
const school = computed(() => page.props.sia?.school ?? {})
const open = ref(false)
watch(() => page.url, () => (open.value = false))

const links = [
    { href: '/', label: 'Beranda', match: (p) => p === '/' },
    { href: '/agenda', label: 'Agenda', match: (p) => p.startsWith('/agenda') },
    { href: '/pengumuman', label: 'Pengumuman', match: (p) => p.startsWith('/pengumuman') },
    { href: '/kegiatan', label: 'Kegiatan Sekolah', match: (p) => p.startsWith('/kegiatan') },
]
const path = computed(() => page.url.split('?')[0])
const initials = computed(() => (school.value.name || 'SMA').split(' ').filter((w) => /^[A-Za-z0-9]/.test(w)).map((w) => (/^\d/.test(w) ? w : w[0])).join('').slice(0, 3).toUpperCase())
</script>

<template>
    <div class="sia">
        <header class="pub__header">
            <div class="container pub__bar">
                <Link href="/" class="pub__brand">
                    <span class="pub__brand-mark"><img v-if="school.logo_url" :src="school.logo_url" alt="" /><template v-else>{{ initials }}</template></span>
                    <span>
                        <span class="pub__brand-name" style="display: block">{{ school.name }}</span>
                        <span class="pub__brand-sub">{{ school.tagline || 'Agenda dan Informasi Sekolah' }}</span>
                    </span>
                </Link>
                <button type="button" class="btn btn--ghost btn--icon pub__toggle" :aria-expanded="open" aria-label="Menu" @click="open = !open">
                    <component :is="open ? X : Menu" :size="22" />
                </button>
                <nav class="pub__nav" :class="{ 'pub__nav--open': open }" aria-label="Navigasi">
                    <Link v-for="l in links" :key="l.href" :href="l.href" :class="{ 'is-active': l.match(path) }">{{ l.label }}</Link>
                    <a href="/display">TV Display</a>
                </nav>
            </div>
        </header>
        <main class="pub__main"><slot /></main>
        <footer class="pub__footer">
            <div class="container row row--between">
                <span>{{ school.name }}<template v-if="school.address"> · {{ school.address }}</template></span>
                <Link href="/admin">Masuk admin</Link>
            </div>
        </footer>
    </div>
</template>
