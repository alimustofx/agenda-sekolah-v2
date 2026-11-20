<script setup>
import { computed, useAttrs, useId } from 'vue'

defineOptions({ inheritAttrs: false })
const props = defineProps({
    modelValue: { type: [String, Number], default: '' },
    label: String,
    error: String,
    hint: String,
    type: { type: String, default: 'text' },
    as: { type: String, default: 'input' },
    rows: { type: Number, default: 3 },
    required: Boolean,
    status: String, // ok | warn | missing | invalid (hasil parser)
    message: String,
})
const emit = defineEmits(['update:modelValue'])
const attrs = useAttrs()
const id = attrs.id || useId()
const inputAttrs = computed(() => {
    const { class: _c, style: _s, ...rest } = attrs
    return rest
})
</script>

<template>
    <div class="field" :class="[attrs.class, status && `field--${status}`]" :style="attrs.style">
        <label v-if="label" class="field__label" :for="id">{{ label }}<span v-if="required" aria-hidden="true"> *</span></label>
        <textarea
            v-if="as === 'textarea'"
            :id="id"
            class="input"
            :class="{ 'input--error': error }"
            :rows="rows"
            :value="modelValue ?? ''"
            v-bind="inputAttrs"
            @input="emit('update:modelValue', $event.target.value)"
        />
        <input
            v-else
            :id="id"
            class="input"
            :class="{ 'input--error': error }"
            :type="type"
            :value="modelValue ?? ''"
            v-bind="inputAttrs"
            @input="emit('update:modelValue', $event.target.value)"
        />
        <slot />
        <p v-if="message" class="field__msg">{{ message }}</p>
        <p v-if="hint && !error && !message" class="field__hint">{{ hint }}</p>
        <p v-if="error" class="field__error" role="alert">{{ error }}</p>
    </div>
</template>
