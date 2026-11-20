import { computed, onBeforeUnmount, onMounted, ref } from 'vue'

const TZ = 'Asia/Jakarta'
const fmt = new Intl.DateTimeFormat('id-ID', {
    timeZone: TZ, weekday: 'long', day: 'numeric', month: 'long', year: 'numeric',
    hour: '2-digit', minute: '2-digit', second: '2-digit', hourCycle: 'h23',
})

/** Jam digital WIB yang berjalan lokal (tanpa refresh), disinkronkan dengan jam server. */
export function useClock(offset) {
    const tick = ref(Date.now())
    let timer

    onMounted(() => {
        const step = () => {
            tick.value = Date.now()
            timer = setTimeout(step, 1000 - (Date.now() % 1000) + 5)
        }
        step()
    })
    onBeforeUnmount(() => clearTimeout(timer))

    return computed(() => {
        const parts = Object.fromEntries(fmt.formatToParts(new Date(tick.value + (offset.value || 0))).map((p) => [p.type, p.value]))
        const hh = parts.hour, mm = parts.minute, ss = parts.second
        return {
            hm: `${hh}:${mm}`,
            ss,
            time: `${hh}:${mm}:${ss}`,
            date: `${parts.weekday}, ${parts.day} ${parts.month} ${parts.year}`,
            minutes: Number(hh) * 60 + Number(mm),
        }
    })
}
