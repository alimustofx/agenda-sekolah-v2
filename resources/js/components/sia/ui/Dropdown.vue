<script setup>
import { nextTick, onBeforeUnmount, onMounted, ref } from 'vue'

// Menu dirender di <body> dengan posisi tetap agar tidak terpotong oleh tabel/panel ber-overflow.
const open = ref(false)
const trigger = ref(null)
const menu = ref(null)
const pos = ref({ top: '0px', left: '0px' })

async function toggle() {
    open.value = !open.value
    if (!open.value) return
    await nextTick()
    place()
}
function place() {
    if (!trigger.value || !menu.value) return
    const r = trigger.value.getBoundingClientRect()
    const w = menu.value.offsetWidth
    const h = menu.value.offsetHeight
    const left = Math.max(8, Math.min(r.right - w, window.innerWidth - w - 8))
    const top = r.bottom + 4 + h > window.innerHeight - 8 ? Math.max(8, r.top - h - 4) : r.bottom + 4
    pos.value = { top: top + 'px', left: left + 'px' }
}
const close = () => (open.value = false)
const outside = (e) => {
    if (!trigger.value?.contains(e.target) && !menu.value?.contains(e.target)) close()
}
onMounted(() => {
    document.addEventListener('click', outside)
    window.addEventListener('scroll', close, true)
    window.addEventListener('resize', close)
})
onBeforeUnmount(() => {
    document.removeEventListener('click', outside)
    window.removeEventListener('scroll', close, true)
    window.removeEventListener('resize', close)
})
</script>

<template>
    <div class="dropdown">
        <div ref="trigger" @click="toggle"><slot name="trigger" :open="open" /></div>
        <Teleport to="body">
            <div v-if="open" class="sia dd-layer" :style="pos">
                <div ref="menu" class="dropdown__menu" role="menu" @click="close"><slot /></div>
            </div>
        </Teleport>
    </div>
</template>
