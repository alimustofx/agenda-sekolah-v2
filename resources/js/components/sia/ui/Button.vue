<script setup>
import { Link } from '@inertiajs/vue3'

defineProps({
    variant: { type: String, default: 'secondary' },
    size: { type: String, default: '' },
    type: { type: String, default: 'button' },
    href: { type: String, default: '' },
    disabled: Boolean,
})
</script>

<template>
    <Link v-if="href && !disabled" :href="href" :class="['btn', `btn--${variant}`, size && `btn--${size}`]">
        <slot />
    </Link>
    <button v-else :type="type" :disabled="disabled" :class="['btn', `btn--${variant}`, size && `btn--${size}`]">
        <slot />
    </button>
</template>
