<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'

const props = defineProps({ texts: Array, school: Object })
const index = ref(0)
const el = ref(null)
const lane = ref(null)
let anim = null
let stopped = false

const current = computed(() => (props.texts.length ? props.texts[index.value % props.texts.length] : null))
const key = computed(() => (current.value ? current.value.id + '|' + current.value.content + '|' + current.value.speed : ''))

// Satu teks melintas penuh, lalu teks berikutnya. Kecepatan konstan (px per detik).
async function run() {
    await nextTick()
    if (stopped || !el.value || !lane.value || !current.value) return
    anim?.cancel()
    const w = el.value.offsetWidth
    const cw = lane.value.offsetWidth
    const speed = current.value.speed || 90
    anim = el.value.animate([{ transform: `translateX(${cw}px)` }, { transform: `translateX(${-w}px)` }], { duration: ((cw + w) / speed) * 1000, easing: 'linear', fill: 'forwards' })
    anim.onfinish = () => {
        index.value = (index.value + 1) % Math.max(1, props.texts.length)
        run()
    }
}

watch(key, run)
watch(() => props.texts.length, (n, o) => { if (o === 0 && n > 0) run() })
onMounted(run)
onBeforeUnmount(() => {
    stopped = true
    anim?.cancel()
})
</script>

<template>
    <footer class="tv-footer" :style="{ background: current?.background_color || 'var(--sia-navy-900)', color: current?.text_color || '#fff' }">
        <div class="tv-footer__label">Informasi Sekolah</div>
        <div ref="lane" class="tv-footer__lane">
            <div v-if="current" ref="el" class="tv-footer__text">{{ current.content }}</div>
            <div v-else class="tv-footer__text" style="transform: none; padding-left: 28px">{{ school.name }}</div>
        </div>
    </footer>
</template>
