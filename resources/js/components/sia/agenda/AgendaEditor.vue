<script setup>
import { computed, ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { Plus } from 'lucide-vue-next'
import Input from '../ui/Input.vue'
import Button from '../ui/Button.vue'
import Check from '../ui/Check.vue'
import ConfirmModal from '../ui/ConfirmModal.vue'
import ActivityEditor from './ActivityEditor.vue'
import AgendaPreview from './AgendaPreview.vue'
import { AGENDA_STATUS, emptyItem, uid } from '../../../lib/sia'

const props = defineProps({
    agenda: Object,
    categories: Array,
    action: String,
    method: { type: String, default: 'post' },
    statuses: { type: Object, default: () => ({}) },
    draftOnly: Boolean, // dipakai pada hasil import: tidak ada publikasi otomatis
})

const form = useForm({
    title: props.agenda.title,
    agenda_date: props.agenda.agenda_date,
    introduction: props.agenda.introduction,
    recipients: props.agenda.recipients,
    uniform_information: props.agenda.uniform_information,
    general_notes: props.agenda.general_notes,
    status: props.agenda.status,
    show_on_website: props.agenda.show_on_website,
    show_on_tv: props.agenda.show_on_tv,
    items: (props.agenda.items || []).map((i) => ({ ...emptyItem(), ...i, schedules: i.schedules || [], _key: uid() })),
})
const original = { title: props.agenda.title, agenda_date: props.agenda.agenda_date }

const docStatus = (field) => (form[field] === original[field] ? props.statuses[field] : null)
const confirmPublish = ref(false)

function submit(status) {
    form
        .transform((d) => ({ ...d, status, items: d.items.map(({ _key, _status, ...rest }) => rest) }))
        [props.method](props.action, { preserveScroll: true, onSuccess: () => (confirmPublish.value = false) })
}

// ---- daftar kegiatan
const open = ref(form.items.length <= 1 ? 0 : -1)
function add() {
    form.items.push({ ...emptyItem(), _key: uid() })
    open.value = form.items.length - 1
}
const remove = (i) => form.items.splice(i, 1)
function duplicate(i) {
    const copy = { ...JSON.parse(JSON.stringify(form.items[i])), id: null, _key: uid(), _status: {} }
    form.items.splice(i + 1, 0, copy)
}
function move(i, dir) {
    const j = i + dir
    if (j < 0 || j >= form.items.length) return
    const arr = [...form.items]
    ;[arr[i], arr[j]] = [arr[j], arr[i]]
    form.items = arr
}

// ---- seret & lepas (hanya lewat handle)
const armed = ref(null)
const dragFrom = ref(null)
const dragOver = ref(null)
function reset() {
    armed.value = dragFrom.value = dragOver.value = null
}
function drop(i) {
    if (dragFrom.value === null || dragFrom.value === i) return reset()
    const arr = [...form.items]
    const [m] = arr.splice(dragFrom.value, 1)
    arr.splice(i, 0, m)
    form.items = arr
    reset()
}
const itemErrors = computed(() => form.errors)
const isPublished = computed(() => props.agenda.status === 'published')
</script>

<template>
    <div class="editor-grid">
        <div class="stack stack--lg">
            <section class="panel">
                <div class="panel__head"><div><h2 class="panel__title">Informasi agenda</h2></div></div>
                <div class="panel__body stack">
                    <Input v-model="form.title" label="Judul agenda" required :error="form.errors.title" :status="docStatus('title')?.state" :message="docStatus('title')?.message" />
                    <Input v-model="form.agenda_date" type="date" label="Tanggal" required :error="form.errors.agenda_date" :status="docStatus('agenda_date')?.state" :message="docStatus('agenda_date')?.message" style="max-width: 260px" />
                </div>
            </section>

            <section class="panel">
                <div class="panel__head"><div><h2 class="panel__title">Informasi umum</h2><p class="panel__desc">Ditampilkan di bagian atas agenda.</p></div></div>
                <div class="panel__body stack">
                    <Input v-model="form.uniform_information" label="Pakaian dinas" placeholder="Contoh: Khaki dengan atribut lengkap" :error="form.errors.uniform_information" />
                    <Input v-model="form.introduction" as="textarea" :rows="2" label="Pembukaan / keterangan" :error="form.errors.introduction" />
                    <Input v-model="form.recipients" as="textarea" :rows="3" label="Ditujukan kepada" hint="Satu penerima per baris." :error="form.errors.recipients" />
                    <Input v-model="form.general_notes" as="textarea" :rows="3" label="Catatan umum" :error="form.errors.general_notes" />
                </div>
            </section>

            <section class="stack">
                <div class="row row--between">
                    <div>
                        <h2 class="panel__title">Daftar kegiatan <span class="muted" style="font-weight: 400">({{ form.items.length }})</span></h2>
                        <p class="panel__desc">Urutan di sini menjadi urutan tampil di TV. Seret ikon di kiri untuk mengubahnya.</p>
                    </div>
                    <Button variant="primary" size="sm" @click="add"><Plus :size="16" />Tambah kegiatan</Button>
                </div>
                <p v-if="form.errors.items" class="alert alert--error" role="alert">{{ form.errors.items }}</p>

                <div
                    v-for="(item, i) in form.items"
                    :key="item._key"
                    :draggable="armed === i"
                    :class="{ 'activity--dragging': dragFrom === i, 'activity--over': dragOver === i && dragFrom !== null && dragFrom !== i }"
                    @dragstart="(e) => { dragFrom = i; e.dataTransfer.effectAllowed = 'move'; e.dataTransfer.setData('text/plain', String(i)) }"
                    @dragover.prevent="dragOver = i"
                    @drop.prevent="drop(i)"
                    @dragend="reset"
                >
                    <ActivityEditor
                        v-model="form.items[i]"
                        :index="i"
                        :total="form.items.length"
                        :categories="categories"
                        :errors="itemErrors"
                        :start-open="open === i || Object.keys(itemErrors).some((k) => k.startsWith(`items.${i}.`))"
                        @remove="remove(i)"
                        @duplicate="duplicate(i)"
                        @move="move(i, $event)"
                        @handle-down="armed = i"
                        @handle-up="armed = null"
                    />
                </div>

                <div v-if="!form.items.length" class="panel empty">
                    <h3>Belum ada kegiatan</h3>
                    <p>Tambahkan kegiatan pertama untuk agenda ini.</p>
                    <Button variant="primary" @click="add"><Plus :size="16" />Tambah kegiatan</Button>
                </div>
            </section>
        </div>

        <aside class="editor-side stack">
            <section class="panel">
                <div class="panel__head"><h2 class="panel__title">Penayangan</h2></div>
                <div class="panel__body stack">
                    <p class="small">Status saat ini: <strong>{{ AGENDA_STATUS[agenda.status] }}</strong></p>
                    <Check v-model="form.show_on_website" label="Tampil di website" />
                    <Check v-model="form.show_on_tv" label="Tampil di TV Display" />
                    <p v-if="draftOnly" class="alert alert--info small">Hasil import disimpan sebagai draft. Periksa lalu terbitkan dari halaman agenda.</p>
                </div>
                <div class="panel__foot" style="flex-direction: column; align-items: stretch">
                    <Button v-if="!draftOnly" variant="primary" :disabled="form.processing" @click="confirmPublish = true">{{ isPublished ? 'Simpan & terbitkan' : 'Terbitkan' }}</Button>
                    <Button :variant="draftOnly ? 'primary' : 'secondary'" :disabled="form.processing" @click="submit(draftOnly ? 'draft' : isPublished ? 'published' : agenda.status === 'archived' ? 'archived' : 'draft')">
                        {{ draftOnly ? 'Simpan sebagai draft' : isPublished ? 'Simpan perubahan' : 'Simpan draft' }}
                    </Button>
                </div>
            </section>
            <section><h3 class="field__label" style="margin-bottom: 8px">Pratinjau</h3><AgendaPreview :agenda="form" /></section>
        </aside>

        <ConfirmModal
            :open="confirmPublish"
            title="Terbitkan agenda?"
            message="Agenda akan langsung tampil di website dan TV Display sesuai pengaturan penayangan. Pastikan tanggal, waktu, dan lokasi sudah diperiksa."
            confirm-label="Terbitkan"
            :processing="form.processing"
            @close="confirmPublish = false"
            @confirm="submit('published')"
        />
    </div>
</template>
