<script setup>
import Modal from './Modal.vue'
import Button from './Button.vue'

defineProps({
    open: Boolean,
    title: { type: String, default: 'Konfirmasi' },
    message: String,
    confirmLabel: { type: String, default: 'Lanjutkan' },
    danger: Boolean,
    processing: Boolean,
})
defineEmits(['confirm', 'close'])
</script>

<template>
    <Modal :open="open" :title="title" @close="$emit('close')">
        <p>{{ message }}</p>
        <template #footer>
            <Button variant="secondary" @click="$emit('close')">Batal</Button>
            <Button :variant="danger ? 'danger' : 'primary'" :disabled="processing" @click="$emit('confirm')">{{ confirmLabel }}</Button>
        </template>
    </Modal>
</template>
