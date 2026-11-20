<script setup>
import '../../../../css/sia.css'
import { computed, ref, watch } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import { Activity, CalendarDays, ExternalLink, FileUp, LayoutDashboard, Menu, Megaphone, ScrollText, Settings, Tags, Tv, Users, Video, X, Type } from 'lucide-vue-next'
import FlashMessages from '../ui/FlashMessages.vue'

const page = usePage()
const sia = computed(() => page.props.sia ?? {})
const user = computed(() => sia.value.user)
const open = ref(false)

const groups = computed(() => {
    const g = [
        { label: 'Ringkasan', items: [{ href: '/admin', label: 'Dashboard', icon: LayoutDashboard }] },
        {
            label: 'Konten',
            items: [
                { href: '/admin/agenda', label: 'Agenda', icon: CalendarDays },
                { href: '/admin/agenda/import', label: 'Import WhatsApp', icon: FileUp },
                { href: '/admin/agenda/kategori', label: 'Kategori Agenda', icon: Tags },
                { href: '/admin/pengumuman', label: 'Pengumuman', icon: Megaphone },
                { href: '/admin/kegiatan', label: 'Kegiatan Sekolah', icon: Activity },
            ],
        },
        { label: 'Media', items: [{ href: '/admin/youtube', label: 'YouTube', icon: Video }] },
        {
            label: 'Display',
            items: [
                { href: '/admin/tv', label: 'TV Display', icon: Tv },
                { href: '/admin/tv/running-text', label: 'Running Text', icon: Type },
            ],
        },
    ]
    const system = [{ href: '/admin/audit-log', label: 'Audit Log', icon: ScrollText }]
    if (user.value?.is_super_admin) {
        system.unshift({ href: '/admin/pengaturan', label: 'Pengaturan', icon: Settings }, { href: '/admin/administrator', label: 'Administrator', icon: Users })
    }
    g.push({ label: 'Sistem', items: system })
    return g
})

// Item aktif = href terpanjang yang cocok dengan URL saat ini.
const activeHref = computed(() => {
    const path = page.url.split('?')[0].replace(/\/$/, '') || '/'
    let best = ''
    for (const g of groups.value) for (const i of g.items) {
        if ((path === i.href || path.startsWith(i.href + '/')) && i.href.length > best.length) best = i.href
    }
    return best
})

watch(() => page.url, () => (open.value = false))
const initials = computed(() => (sia.value.school?.name || 'SMA').split(' ').filter((w) => /^[A-Za-z]/.test(w)).map((w) => (/^\d/.test(w) ? w : w[0])).join('').slice(0, 3).toUpperCase())
const logout = () => router.post('/logout')
</script>

<template>
    <div class="sia adm">
        <div class="adm__scrim" :class="{ 'adm__scrim--open': open }" @click="open = false" />
        <aside class="adm__sidebar" :class="{ 'adm__sidebar--open': open }" aria-label="Navigasi utama">
            <Link href="/admin" class="adm__brand">
                <span class="adm__brand-mark"><img v-if="sia.school?.logo_url" :src="sia.school.logo_url" alt="" /><template v-else>{{ initials }}</template></span>
                <span>
                    <span class="adm__brand-name" style="display: block">{{ sia.school?.name }}</span>
                    <span class="adm__brand-sub">Pusat Agenda &amp; Informasi</span>
                </span>
            </Link>
            <nav class="adm__nav">
                <div v-for="g in groups" :key="g.label" class="nav-group">
                    <div class="nav-group__label">{{ g.label }}</div>
                    <Link v-for="i in g.items" :key="i.href" :href="i.href" class="nav-link" :class="{ 'nav-link--active': activeHref === i.href }">
                        <component :is="i.icon" :size="20" />{{ i.label }}
                    </Link>
                </div>
                <div class="nav-group">
                    <a class="nav-link" href="/display" target="_blank" rel="noopener"><Tv :size="20" />Buka TV Display<ExternalLink :size="14" style="margin-left: auto" /></a>
                    <a class="nav-link" href="/" target="_blank" rel="noopener"><ExternalLink :size="20" />Lihat website</a>
                </div>
            </nav>
            <div class="adm__user">
                <strong>{{ user?.name }}</strong>
                <span>{{ user?.is_super_admin ? 'Super Admin' : 'Admin Humas' }}</span>
                <div class="row" style="margin-top: 8px; gap: 16px">
                    <a href="/settings/profile">Akun saya</a>
                    <button type="button" @click="logout">Keluar</button>
                </div>
            </div>
        </aside>

        <div class="adm__main">
            <div class="adm__topbar">
                <button type="button" class="btn btn--ghost btn--icon" aria-label="Buka menu" @click="open = !open"><component :is="open ? X : Menu" :size="22" /></button>
                <strong style="color: var(--sia-stone-900)">{{ sia.school?.name }}</strong>
            </div>
            <main class="adm__content">
                <FlashMessages />
                <slot />
            </main>
        </div>
    </div>
</template>
