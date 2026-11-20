import { onBeforeUnmount, ref } from 'vue'

let apiPromise = null
function loadApi() {
    if (apiPromise) return apiPromise
    apiPromise = new Promise((resolve) => {
        if (window.YT?.Player) return resolve(true)
        const prev = window.onYouTubeIframeAPIReady
        window.onYouTubeIframeAPIReady = () => {
            prev?.()
            resolve(true)
        }
        const s = document.createElement('script')
        s.src = 'https://www.youtube.com/iframe_api'
        s.onerror = () => resolve(false) // offline / diblokir: TV tetap berjalan tanpa video
        document.head.appendChild(s)
        setTimeout(() => resolve(!!window.YT?.Player), 15000)
    })
    return apiPromise
}

const STATE = { ENDED: 0, PLAYING: 1, BUFFERING: 3, CUED: 5 }

/**
 * Pembungkus YouTube IFrame Player API.
 * Menyediakan: play(videoId), resolvePlaylist(id), setAudio(muted, volume) dan event
 * playing / ended / failed. Tidak menyimpan logika antrean (ada di MediaPanel).
 */
export function useYoutubePlayer(elementId, handlers) {
    const ready = ref(false)
    const available = ref(true)
    let player = null
    let watchdog = null
    let pending = null // resolver playlist yang sedang menunggu

    const clearWatch = () => clearTimeout(watchdog)

    async function init() {
        const ok = await loadApi()
        if (!ok || !window.YT?.Player) {
            available.value = false
            return false
        }
        await new Promise((resolve) => {
            player = new window.YT.Player(elementId, {
                width: '100%',
                height: '100%',
                playerVars: { autoplay: 0, controls: 0, rel: 0, modestbranding: 1, playsinline: 1, disablekb: 1, fs: 0, iv_load_policy: 3, origin: window.location.origin },
                events: {
                    onReady: () => {
                        ready.value = true
                        resolve()
                    },
                    onStateChange: (e) => {
                        if (e.data === STATE.PLAYING) {
                            clearWatch()
                            handlers.playing?.()
                        } else if (e.data === STATE.ENDED) {
                            clearWatch()
                            handlers.ended?.()
                        } else if (e.data === STATE.CUED && pending) {
                            const ids = player.getPlaylist?.() || []
                            pending(ids)
                            pending = null
                        }
                    },
                    onError: (e) => {
                        clearWatch()
                        pending?.([])
                        pending = null
                        handlers.failed?.(e.data)
                    },
                },
            })
        })
        return true
    }

    function setAudio(muted, volume) {
        if (!player || !ready.value) return
        try {
            if (muted) player.mute()
            else {
                player.unMute()
                player.setVolume(volume)
            }
        } catch (e) { /* abaikan */ }
    }

    function play(videoId, { muted, volume }) {
        if (!player || !ready.value) return handlers.failed?.('player-belum-siap')
        setAudio(muted, volume)
        player.loadVideoById(videoId)
        // Jika dalam 12 detik tidak mulai (autoplay diblokir / video bermasalah), anggap gagal dan lanjut.
        clearWatch()
        watchdog = setTimeout(() => handlers.failed?.('timeout-12s'), 12000)
    }

    function resolvePlaylist(listId) {
        return new Promise((resolve) => {
            if (!player || !ready.value) return resolve([])
            pending = resolve
            try {
                player.cuePlaylist({ listType: 'playlist', list: listId })
            } catch (e) {
                pending = null
                return resolve([])
            }
            setTimeout(() => {
                if (pending === resolve) {
                    pending = null
                    resolve([])
                }
            }, 10000)
        })
    }

    function stop() {
        clearWatch()
        try { player?.stopVideo() } catch (e) { /* abaikan */ }
    }

    onBeforeUnmount(() => {
        clearWatch()
        try { player?.destroy() } catch (e) { /* abaikan */ }
    })

    return { init, play, stop, resolvePlaylist, setAudio, ready, available }
}
