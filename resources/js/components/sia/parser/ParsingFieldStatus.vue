<script setup>
import { CheckCircle2, CircleAlert, CircleHelp, CircleX } from 'lucide-vue-next'

// Penanda validasi hasil parser. Selalu disertai teks (tidak hanya warna).
defineProps({ state: { type: String, default: 'ok' }, label: String })
const text = { ok: 'Dikenali', warn: 'Perlu diperiksa', missing: 'Belum lengkap', invalid: 'Tidak valid' }
const icon = { ok: CheckCircle2, warn: CircleHelp, missing: CircleAlert, invalid: CircleX }
</script>

<template>
    <span class="mark" :class="`mark--${state}`"><component :is="icon[state]" :size="14" />{{ label || text[state] }}</span>
</template>
