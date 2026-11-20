import { onBeforeUnmount, onMounted, ref, shallowRef } from 'vue'

/**
 * Menyimpan data TV dan memperbaruinya berkala tanpa reload halaman.
 * Gagal fetch tidak mengosongkan layar: data terakhir tetap dipakai.
 */
export function useDisplayData(initial) {
    const data = shallowRef(initial)
    const offset = ref(Date.parse(initial.server_time) - Date.now()) // selisih jam server - jam perangkat
    const stale = ref(false)
    let timer = null
    let fails = 0
    let stopped = false

    async function refresh() {
        try {
            const res = await fetch('/display/data', { headers: { Accept: 'application/json' }, cache: 'no-store' })
            if (!res.ok) throw new Error('HTTP ' + res.status)
            const json = await res.json()
            offset.value = Date.parse(json.server_time) - Date.now()
            if (json.version !== data.value.version) data.value = json
            fails = 0
            stale.value = false
        } catch (e) {
            fails++
            stale.value = fails >= 3
        } finally {
            if (!stopped) timer = setTimeout(refresh, (data.value.display.refresh_interval || 30) * 1000)
        }
    }

    onMounted(() => {
        timer = setTimeout(refresh, (data.value.display.refresh_interval || 30) * 1000)
    })
    onBeforeUnmount(() => {
        stopped = true
        clearTimeout(timer)
    })

    return { data, offset, stale }
}
