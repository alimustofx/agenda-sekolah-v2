// Helper & konstanta bersama untuk seluruh halaman Sistem Agenda Sekolah.
export const DAYS = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu']
export const MONTHS = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember']

export const PRIORITY = { normal: 'Normal', important: 'Penting', urgent: 'Mendesak' }
export const priorityOptions = Object.entries(PRIORITY).map(([value, label]) => ({ value, label }))
export const AGENDA_STATUS = { draft: 'Draft', published: 'Terbit', archived: 'Diarsipkan' }
export const ACTIVITY_STATUS = { scheduled: 'Terjadwal', ongoing: 'Berlangsung', completed: 'Selesai' }

export function priorityVariant(p) {
    return p === 'urgent' ? 'danger' : p === 'important' ? 'warning' : 'neutral'
}
export function agendaStatusVariant(s) {
    return s === 'published' ? 'success' : s === 'archived' ? 'neutral' : 'warning'
}

/** 'YYYY-MM-DD' -> 'Senin, 16 November 2026' (tanpa konversi zona waktu). */
export function longDate(iso) {
    if (!iso) return ''
    const [y, m, d] = iso.slice(0, 10).split('-').map(Number)
    const dt = new Date(Date.UTC(y, m - 1, d))
    return `${DAYS[dt.getUTCDay()]}, ${d} ${MONTHS[m - 1]} ${y}`
}

export const dot = (hm) => (hm ? hm.slice(0, 5).replace(':', '.') : '')

/** Label waktu dari bidang form (untuk preview). */
export function timeLabel(item) {
    const s = dot(item.start_time)
    const e = dot(item.end_time)
    if (!s) return item.time_description || 'Sepanjang hari'
    if (e) return `${s} – ${e}${item.time_description ? ` (${item.time_description})` : ''}`
    return `${s} – ${item.time_description || 'Selesai'}`
}

export function emptyItem() {
    return {
        id: null, title: '', category_id: null, start_time: null, end_time: null, time_description: null,
        location: null, description: null, responsible_label: null, responsible_person: null,
        participants: null, officers: null, additional_notes: null, priority: 'normal', schedules: [],
    }
}

export function uid() {
    return 'k' + Math.random().toString(36).slice(2, 10)
}
