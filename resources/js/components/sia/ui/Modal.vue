<script setup>
import { onBeforeUnmount, watch } from 'vue'
import { X } from 'lucide-vue-next'

const props = defineProps({ open: Boolean, title: String, wide: Boolean })
const emit = defineEmits(['close'])

function onKey(e) {
    if (e.key === 'Escape') emit('close')
}
watch(
    () => props.open,
    (v) => {
        if (typeof document === 'undefined') return
        document.body.style.overflow = v ? 'hidden' : ''
        v ? window.addEventListener('keydown', onKey) : window.removeEventListener('keydown', onKey)
    },
    { immediate: true },
)
onBeforeUnmount(() => {
    document.body.style.overflow = ''
    window.removeEventListener('keydown', onKey)
})
</script>

<template>
    <Transition name="sia-modal">
        <div v-if="open" class="sia-modal-scrim" @mousedown.self="emit('close')">
            <div class="sia-modal" :class="{ 'sia-modal--wide': wide }" role="dialog" aria-modal="true" :aria-label="title">
                <div class="sia-modal__head">
                    <h2>{{ title }}</h2>
                    <button type="button" class="btn btn--ghost btn--icon btn--sm" aria-label="Tutup" @click="emit('close')">
                        <X :size="18" />
                    </button>
                </div>
                <div class="sia-modal__body"><slot /></div>
                <div v-if="$slots.footer" class="sia-modal__foot"><slot name="footer" /></div>
            </div>
        </div>
    </Transition>
</template>
