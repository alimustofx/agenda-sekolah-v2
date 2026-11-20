<?php

namespace App\Services\Sia;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class YoutubeService
{
    /** Mengenali URL video / playlist YouTube. Mengembalikan null bila bukan URL YouTube yang valid. */
    public function parseUrl(string $url): ?array
    {
        $parts = parse_url(trim($url));
        if (! $parts || empty($parts['host'])) {
            return null;
        }
        $host = strtolower(preg_replace('/^(www\.|m\.|music\.)/', '', $parts['host']));
        parse_str($parts['query'] ?? '', $query);
        $path = trim($parts['path'] ?? '', '/');
        $video = null;

        if ($host === 'youtu.be') {
            $video = explode('/', $path)[0] ?: null;
        } elseif (in_array($host, ['youtube.com', 'youtube-nocookie.com'], true)) {
            if ($path === 'watch') {
                $video = $query['v'] ?? null;
            } elseif (preg_match('#^(embed|shorts|live|v)/([^/?]+)#', $path, $m)) {
                $video = $m[2];
            }
        } else {
            return null;
        }

        $list = $query['list'] ?? null;
        $video = $video && preg_match('/^[A-Za-z0-9_-]{11}$/', $video) ? $video : null;
        $list = $list && preg_match('/^[A-Za-z0-9_-]{10,60}$/', $list) ? $list : null;

        if (! $video && ! $list) {
            return null;
        }

        return ['video_id' => $video, 'playlist_id' => $list];
    }

    /** Metadata lewat oEmbed (tanpa API key). Gagal tidak menghentikan proses. */
    public function metadata(string $url, ?string $videoId): array
    {
        $meta = ['status' => null, 'title' => null, 'channel' => null, 'thumbnail_url' => $videoId ? "https://i.ytimg.com/vi/{$videoId}/hqdefault.jpg" : null, 'duration' => null];

        try {
            $res = Http::timeout(6)->get('https://www.youtube.com/oembed', ['url' => $url, 'format' => 'json']);
            $meta['status'] = $res->status(); // 200 ok, 401/403 penyematan dimatikan, 404 privat/tidak ada
            if ($res->ok()) {
                $meta['title'] = $res->json('title');
                $meta['channel'] = $res->json('author_name');
                $meta['thumbnail_url'] = $res->json('thumbnail_url') ?: $meta['thumbnail_url'];
            }
        } catch (\Throwable $e) {
            report($e);
        }

        if ($videoId && ($key = config('sia.youtube.api_key'))) {
            try {
                $res = Http::timeout(6)->get('https://www.googleapis.com/youtube/v3/videos', ['part' => 'contentDetails', 'id' => $videoId, 'key' => $key]);
                $iso = $res->json('items.0.contentDetails.duration');
                if ($iso) {
                    $meta['duration'] = $this->isoToSeconds($iso);
                }
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return $meta;
    }

    /** Daftar ID video playlist. Tanpa API key hasilnya kosong dan TV membacanya lewat IFrame API. */
    public function playlistVideos(string $playlistId): array
    {
        $key = config('sia.youtube.api_key');
        if (! $key) {
            return [];
        }

        return Cache::remember('sia.yt.playlist.' . $playlistId, 6 * 3600, function () use ($playlistId, $key) {
            $ids = [];
            $page = null;
            try {
                for ($i = 0; $i < 4; $i++) {
                    $res = Http::timeout(8)->get('https://www.googleapis.com/youtube/v3/playlistItems', array_filter([
                        'part' => 'contentDetails', 'maxResults' => 50, 'playlistId' => $playlistId, 'key' => $key, 'pageToken' => $page,
                    ]));
                    if (! $res->ok()) {
                        break;
                    }
                    foreach ($res->json('items', []) as $item) {
                        if ($id = $item['contentDetails']['videoId'] ?? null) {
                            $ids[] = $id;
                        }
                    }
                    $page = $res->json('nextPageToken');
                    if (! $page) {
                        break;
                    }
                }
            } catch (\Throwable $e) {
                report($e);
            }

            return $ids;
        });
    }

    private function isoToSeconds(string $iso): ?int
    {
        if (! preg_match('/^PT(?:(\d+)H)?(?:(\d+)M)?(?:(\d+)S)?$/', $iso, $m)) {
            return null;
        }

        return ((int) ($m[1] ?? 0)) * 3600 + ((int) ($m[2] ?? 0)) * 60 + (int) ($m[3] ?? 0);
    }
}
