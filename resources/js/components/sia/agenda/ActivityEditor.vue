<script setup>
import { computed, ref } from 'vue'
import { ArrowDown, ArrowUp, ChevronDown, ChevronUp, Copy, GripVertical, Plus, Trash2 } from 'lucide-vue-next'
import Input from '../ui/Input.vue'
import Select from '../ui/Select.vue'
import Button from '../ui/Button.vue'
import ParsingFieldStatus from '../parser/ParsingFieldStatus.vue'
import { priorityOptions, timeLabel } from '../../../lib/sia'

const props = defineProps({
    modelValue: Object,
    index: Number,
    total: Number,
    categories: Array,
    errors: { type: Object, default: () => ({}) },
    startOpen: Boolean,
})
const emit = defineEmits(['update:modelValue', 'remove', 'duplicate', 'move', 'handle-down', 'handle-up'])

const open = ref(props.startOpen)
const item = computed(() => props.modelValue)
const err = (f) => props.errors[`items.${props.index}.${f}`]
const st = (k) => item.value._status?.[k]

// Field yang diedit admin tidak lagi menampilkan penanda parser.
const STATUS_KEY = { title: 'title', start_time: 'time', end_time: 'end_time', time_description: 'end_time', category_id: 'category' }
function set(field, value) {
    const next = { ...item.value, [field]: value === '' ? null : value }
    const key = STATUS_KEY[field]
    if (key && next._status?.[key]) {
        const { [key]: _drop, ...rest } = next._status
        next._status = rest
    }
    emit('update:modelValue', next)
}
const catOptions = computed(() => props.categories.map((c) => ({ value: c.id, label: c.name })))
const worst = computed(() => {
    const order = ['invalid', 'missing', 'warn']
    const states = Object.values(item.value._status || {}).map((s) => s.state)
    return order.find((o) => states.includes(o)) || null
})
const rank = { missing: 'missing', invalid: 'invalid', warn: 'warn' }

function addSchedule() {
    set('schedules', [...(item.value.schedules || []), { start_time: null, end_time: null, time_description: null }])
}
function setSchedule(i, k, v) {
    set('schedules', item.value.schedules.map((s, n) => (n === i ? { ...s, [k]: v || null } : s)))
}
function removeSchedule(i) {
    set('schedules', item.value.schedules.filter((_, n) => n !== i))
}
</script>

<template>
    <div class="activity">
        <div class="activity__head">
            <button type="button" class="handle" aria-label="Seret untuk mengubah urutan" @mousedown="emit('handle-down')" @mouseup="emit('handle-up')" @touchstart.passive="emit('handle-down')">
                <GripVertical :size="18" />
            </button>
            <span class="activity__no">{{ String(index + 1).padStart(2, '0') }}</span>
            <button type="button" class="activity__title" :aria-expanded="open" @click="open = !open">{{ item.title || 'Kegiatan tanpa judul' }}</button>
            <ParsingFieldStatus v-if="worst" :state="rank[worst]" />
            <span class="activity__time">{{ timeLabel(item) }}</span>
            <Button variant="ghost" size="sm" class="btn--icon" aria-label="Naikkan" :disabled="index === 0" @click="emit('move', -1)"><ArrowUp :size="16" /></Button>
            <Button variant="ghost" size="sm" class="btn--icon" aria-label="Turunkan" :disabled="index === total - 1" @click="emit('move', 1)"><ArrowDown :size="16" /></Button>
            <Button variant="ghost" size="sm" class="btn--icon" aria-label="Duplikat kegiatan" @click="emit('duplicate')"><Copy :size="16" /></Button>
            <Button variant="ghost" size="sm" class="btn--icon" aria-label="Hapus kegiatan" @click="emit('remove')"><Trash2 :size="16" /></Button>
            <Button variant="ghost" size="sm" class="btn--icon" :aria-label="open ? 'Ciutkan' : 'Buka'" @click="open = !open"><component :is="open ? ChevronUp : ChevronDown" :size="16" /></Button>
        </div>

        <div v-if="open" class="activity__body stack">
            <Input :model-value="item.title" label="Judul kegiatan" required :error="err('title')" :status="st('title')?.state" :message="st('title')?.message" @update:model-value="set('title', $event)" />
            <div class="grid-2">
                <Select :model-value="item.category_id" label="Kategori" placeholder="Pilih kategori" :options="catOptions" :error="err('category_id')" :status="st('category')?.state" :message="st('category')?.message" @update:model-value="set('category_id', $event)" />
                <Select :model-value="item.priority" label="Prioritas" :options="priorityOptions" @update:model-value="set('priority', $event)" />
            </div>
            <div class="grid-3">
                <Input :model-value="item.start_time" type="time" label="Waktu mulai" :error="err('start_time')" :status="st('time')?.state" :message="st('time')?.message" @update:model-value="set('start_time', $event)" />
                <Input :model-value="item.end_time" type="time" label="Waktu selesai" :error="err('end_time')" :status="st('end_time')?.state" :message="st('end_time')?.message" @update:model-value="set('end_time', $event)" />
                <Input :model-value="item.time_description" label="Keterangan waktu" placeholder="Contoh: Selesai" :error="err('time_description')" @update:model-value="set('time_description', $event)" />
            </div>

            <fieldset v-if="item.schedules?.length" class="stack stack--sm">
                <legend>Jadwal tambahan</legend>
                <div v-for="(s, i) in item.schedules" :key="i" class="row" style="align-items: flex-end">
                    <Input :model-value="s.start_time" type="time" label="Mulai" @update:model-value="setSchedule(i, 'start_time', $event)" />
                    <Input :model-value="s.end_time" type="time" label="Selesai" @update:model-value="setSchedule(i, 'end_time', $event)" />
                    <Input :model-value="s.time_description" label="Keterangan" style="flex: 1" @update:model-value="setSchedule(i, 'time_description', $event)" />
                    <Button variant="ghost" size="sm" class="btn--icon" aria-label="Hapus jadwal" @click="removeSchedule(i)"><Trash2 :size="16" /></Button>
                </div>
            </fieldset>
            <div><Button size="sm" @click="addSchedule"><Plus :size="16" />Tambah jadwal waktu lain</Button></div>

            <Input :model-value="item.location" label="Lokasi" :error="err('location')" @update:model-value="set('location', $event)" />
            <div class="grid-2">
                <Input :model-value="item.responsible_label" label="Peran penanggung jawab" placeholder="Pembina, Narasumber, dst." @update:model-value="set('responsible_label', $event)" />
                <Input :model-value="item.responsible_person" label="Penanggung jawab" @update:model-value="set('responsible_person', $event)" />
            </div>
            <div class="grid-2">
                <Input :model-value="item.officers" as="textarea" :rows="2" label="Petugas" @update:model-value="set('officers', $event)" />
                <Input :model-value="item.participants" as="textarea" :rows="2" label="Peserta" @update:model-value="set('participants', $event)" />
            </div>
            <Input :model-value="item.description" as="textarea" label="Deskripsi" @update:model-value="set('description', $event)" />
            <Input :model-value="item.additional_notes" as="textarea" :rows="2" label="Keterangan tambahan" @update:model-value="set('additional_notes', $event)" />
        </div>
    </div>
</template>
