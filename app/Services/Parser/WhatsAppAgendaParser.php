<?php

namespace App\Services\Parser;

use App\Support\SiaTime;
use Illuminate\Support\Carbon;

/**
 * Lapisan 1: parser berbasis aturan untuk teks agenda dari WhatsApp.
 * Hasilnya selalu berupa draft yang harus diperiksa admin.
 */
class WhatsAppAgendaParser
{
    private const MONTHS = [
        'januari' => 1, 'jan' => 1, 'februari' => 2, 'pebruari' => 2, 'feb' => 2, 'maret' => 3, 'mar' => 3,
        'april' => 4, 'apr' => 4, 'mei' => 5, 'juni' => 6, 'jun' => 6, 'juli' => 7, 'jul' => 7,
        'agustus' => 8, 'agu' => 8, 'agt' => 8, 'ags' => 8, 'agst' => 8, 'aug' => 8,
        'september' => 9, 'sep' => 9, 'sept' => 9, 'oktober' => 10, 'okt' => 10, 'oct' => 10,
        'november' => 11, 'nopember' => 11, 'nov' => 11, 'nop' => 11, 'desember' => 12, 'des' => 12, 'dec' => 12,
    ];

    private const DAY_INDEX = ['senin' => 1, 'selasa' => 2, 'rabu' => 3, 'kamis' => 4, 'jumat' => 5, 'sabtu' => 6, 'minggu' => 7, 'ahad' => 7];

    private const TIME_RE = '/(?<pre>\b(?:pkl|pukul|jam|waktu)\b\.?\s*:?\s*)?(?<![\d.:])(?<h1>\d{1,2})\s*[.:]\s*(?<m1>\d{2})(?!\d|\.\d)(?:\s*(?:wib|wita|wit)\b)?(?:\s*(?:-|–|—|s\/d|s\.d\.?|sd|sampai|hingga)\s*(?:(?<h2>\d{1,2})\s*[.:]\s*(?<m2>\d{2})(?!\d|\.\d)|(?<open>selesai|slsai|dst|seterusnya))\s*(?:wib|wita|wit)?|\s+(?<open2>selesai|dst))?/iu';

    private const LABELS = [
        'location' => ['lokasi', 'tempat', 'ruang', 'ruangan', 'venue', 'lok'],
        'responsible' => [
            'pembina' => 'Pembina', 'pembina upacara' => 'Pembina Upacara', 'penanggung jawab' => 'Penanggung Jawab',
            'pj' => 'Penanggung Jawab', 'pic' => 'Penanggung Jawab', 'narasumber' => 'Narasumber', 'pemateri' => 'Pemateri',
            'pembicara' => 'Pembicara', 'moderator' => 'Moderator', 'ketua' => 'Ketua', 'koordinator' => 'Koordinator',
            'pimpinan' => 'Pimpinan', 'pemimpin' => 'Pemimpin', 'inspektur' => 'Inspektur Upacara', 'inspektur upacara' => 'Inspektur Upacara',
        ],
        'officers' => ['petugas', 'bertugas', 'pelaksana', 'panitia', 'piket'],
        'participants' => ['peserta', 'sasaran', 'undangan', 'hadir', 'diikuti', 'kelas', 'untuk'],
        'description' => ['materi', 'tema', 'topik', 'acara', 'agenda', 'deskripsi', 'kegiatan'],
        'notes' => ['keterangan', 'ket', 'catatan', 'note', 'info', 'informasi'],
        'time' => ['waktu', 'pukul', 'pkl', 'jam'],
    ];

    private const CATEGORY_RULES = [
        'keagamaan' => '/sholat|shalat|salat|dzuhur|zuhur|dhuha|jum.?atan|\bdoa\b|istighosah|tadarus|pengajian|keagamaan|rohani|kultum|ibadah|natal|paskah|misa/iu',
        'rapat-dan-koordinasi' => '/rapat|koordinasi|briefing|evaluasi|musyawarah|sosialisasi|rembuk|workshop|pelatihan|bimtek|zoom/iu',
        'kepegawaian' => '/piket|\bgtk\b|\bguru\b|tendik|kepegawaian|presensi|inovasi gtk/iu',
        'akademik-dan-kbm' => '/\bkbm\b|belajar|mengajar|pembelajaran|ulangan|ujian|asesmen|\bsts\b|\bsas\b|remedial|kurikulum|try ?out|olimpiade|akademik|semester/iu',
        'kesiswaan' => '/upacara|osis|\bmpk\b|kesiswaan|siswa|\bapel\b|literasi|kokurikuler|\bp5\b|konseling|mpls|senam|makan bergizi|\bmbg\b/iu',
        'ekstrakurikuler' => '/ekstra|ekskul|latihan|pramuka|paskibra|\bpmr\b|futsal|basket|voli|pencak|tari\b|\bband\b|robotik|\bkir\b/iu',
        'sarana-dan-prasarana' => '/sarana|prasarana|sarpras|perbaikan|renovasi|kebersihan|kerja bakti|pemeliharaan|perawatan|instalasi|listrik|jaringan/iu',
        'kegiatan-eksternal' => '/dinas|kunjungan|\btamu\b|undangan|studi banding|universitas|kabupaten|provinsi|kemendikbud|kementerian|audiensi|monitoring|supervisi|akreditasi|asesor/iu',
    ];

    public function __construct(private array $categoryMap = [])
    {
    }

    public static function summarize(array $result): array
    {
        $count = ['ok' => 0, 'warn' => 0, 'missing' => 0, 'invalid' => 0];
        $states = array_column($result['statuses'] ?? [], 'state');
        foreach ($result['items'] ?? [] as $item) {
            foreach ($item['_status'] ?? [] as $s) {
                $states[] = $s['state'] ?? 'ok';
            }
        }
        foreach ($states as $state) {
            if (isset($count[$state])) {
                $count[$state]++;
            }
        }

        return $count;
    }

    public function parse(string $raw, ?Carbon $today = null): array
    {
        $today = $today ?: SiaTime::now();
        $lines = $this->normalize($raw);
        $n = count($lines);
        $skip = array_fill(0, $n, false);

        // 1. Penerima ("Yth. ...") agar daftar penerima bernomor tidak dianggap kegiatan.
        $recipients = [];
        for ($i = 0; $i < $n; $i++) {
            if (preg_match('/^(?:yth|kepada)\b\.?\s*:?\s*(.*)$/iu', trim($lines[$i]), $m)) {
                $skip[$i] = true;
                if (trim($m[1]) !== '' && ! str_ends_with(trim($m[1]), ':')) {
                    $recipients[] = $this->cleanListLine($m[1]);
                }
                for ($j = $i + 1; $j < $n; $j++) {
                    $t = trim($lines[$j]);
                    if ($t === '' || preg_match('/^(agenda|jadwal|pakaian|hari\b|giat)/iu', $t) || $this->findDate($t)) {
                        break;
                    }
                    $recipients[] = $this->cleanListLine($t);
                    $skip[$j] = true;
                }
                break;
            }
        }

        // 2. Awal tiap kegiatan bernomor (nomor harus berurutan agar sub-daftar tidak ikut terhitung).
        $starts = [];
        $last = 0;
        for ($i = 0; $i < $n; $i++) {
            if ($skip[$i] || ! ($m = $this->itemStart($lines[$i]))) {
                continue;
            }
            $num = (int) $m[1];
            if (($last === 0 && $num <= 2) || ($last > 0 && $num > $last && $num <= $last + 3)) {
                $starts[] = $i;
                $last = $num;
            }
        }

        $headerEnd = $starts ? $starts[0] : $n;

        // 3. Potongan penutup (catatan/terima kasih) setelah kegiatan terakhir.
        $cutoff = $n;
        if ($starts) {
            for ($j = end($starts) + 1; $j < $n; $j++) {
                $prevBlank = $j > 0 && trim($lines[$j - 1]) === '';
                if ($prevBlank && preg_match('/^(demikian|terima\s*kasih|terimakasih|trims|atas perhatian|wassalam|salam\b|catatan\b|note\b|info(?:rmasi)?\b|mohon|harap|dimohon|hormat|ttd|humas|kepala sekolah\b|mbg\b|makan bergizi)/iu', trim($lines[$j]))) {
                    $cutoff = $j;
                    break;
                }
            }
        }

        // 4. Header.
        $meta = $this->parseHeader(array_slice($lines, 0, $headerEnd), $skip, $today);
        $recipientsText = $recipients ? implode("\n", $recipients) : null;

        // 5. Kegiatan.
        $items = [];
        if ($starts) {
            foreach ($starts as $k => $start) {
                $end = ($starts[$k + 1] ?? $cutoff);
                $first = $this->itemStart($lines[$start])[2];
                $block = array_merge([$first], array_slice($lines, $start + 1, $end - $start - 1));
                $items[] = $this->parseItem($block);
            }
            foreach ($meta['loose'] as $paragraph) {
                $meta['introduction'][] = implode(' ', $paragraph);
            }
        } else {
            // Tanpa nomor: tiap paragraf yang memuat waktu dianggap satu kegiatan.
            foreach ($meta['loose'] as $paragraph) {
                $joined = implode("\n", $paragraph);
                if ($this->findTimes($joined)) {
                    $items[] = $this->parseItem($paragraph);
                } elseif (! preg_match('/^(demikian|terima\s*kasih|wassalam|salam\b)/iu', $joined)) {
                    $meta['introduction'][] = implode(' ', $paragraph);
                }
            }
        }

        // 6. Catatan penutup.
        $notes = $meta['notes'];
        foreach (array_slice($lines, $cutoff) as $line) {
            $t = trim($line);
            if ($t === '' || preg_match('/^(wassalam|salam\b|hormat|terima\s*kasih|terimakasih|trims|demikian)/iu', $t)) {
                continue;
            }
            $notes[] = preg_replace('/^(catatan|note|info(?:rmasi)?)\s*:\s*/iu', '', $t);
        }

        // 7. Status tingkat agenda.
        $statuses = [];
        $statuses['agenda_date'] = $meta['date_status'];
        $statuses['title'] = $meta['title']
            ? ['state' => 'ok']
            : ['state' => 'warn', 'message' => 'Judul tidak ditemukan. Judul bawaan dipakai, ubah jika perlu.'];
        if (! $items) {
            $statuses['items'] = ['state' => 'missing', 'message' => 'Tidak ada kegiatan yang dikenali. Tambahkan kegiatan secara manual.'];
        }

        $result = [
            'greeting' => $meta['greeting'],
            'agenda' => [
                'title' => $meta['title'] ?: 'Agenda Kegiatan Sekolah',
                'agenda_date' => $meta['date'],
                'introduction' => $meta['introduction'] ? implode("\n", $meta['introduction']) : null,
                'recipients' => $recipientsText,
                'uniform_information' => $meta['uniform'],
                'general_notes' => $notes ? implode("\n", $notes) : null,
            ],
            'items' => $items,
            'statuses' => $statuses,
        ];
        $result['summary'] = self::summarize($result);

        return $result;
    }

    // ------------------------------------------------------------------ normalisasi

    private function normalize(string $raw): array
    {
        $t = str_replace(["\r\n", "\r"], "\n", $raw);
        $t = str_replace(["\u{00A0}", "\u{200E}", "\u{200F}", "\u{200B}", "\t"], [' ', '', '', '', ' '], $t);
        $t = preg_replace('/(\d)\x{FE0F}?\x{20E3}/u', '$1.', $t);
        $t = preg_replace('/(?<![\w*])\*([^*\n]+)\*(?![\w*])/u', '$1', $t);
        $t = preg_replace('/(?<![\w_])_([^_\n]+)_(?![\w_])/u', '$1', $t);
        $t = preg_replace('/(?<![\w~])~([^~\n]+)~(?![\w~])/u', '$1', $t);

        $lines = [];
        foreach (explode("\n", $t) as $line) {
            $line = preg_replace('/^[\s\p{So}\p{Sk}\x{FE0F}\x{200D}]+/u', '', $line);
            $lines[] = rtrim($line);
        }

        return $lines;
    }

    private function itemStart(string $line): ?array
    {
        if (preg_match('/^\s*\(?0*(\d{1,2})(?:[.)\]]|\s[-–])\s+(\S.*)$/u', $line, $m)) {
            return [$m[0], $m[1], $m[2]];
        }

        return null;
    }

    private function cleanListLine(string $s): string
    {
        return trim(preg_replace('/^[\s\-•*\d.)]+/u', '', $s));
    }

    // ------------------------------------------------------------------ header

    private function findDate(string $line): ?array
    {
        $dayRe = "(senin|selasa|rabu|kamis|jum['’]?at|sabtu|minggu|ahad)";

        if (preg_match_all('/(?:' . $dayRe . '\s*[,.]?\s*)?(\d{1,2})\s+([a-z]{3,10})\.?,?(?:\s+(\d{4}))?/iu', $line, $all, PREG_SET_ORDER)) {
            foreach ($all as $m) {
                $month = self::MONTHS[mb_strtolower($m[3])] ?? null;
                if ($month && (int) $m[2] >= 1 && (int) $m[2] <= 31) {
                    return ['text' => $m[0], 'day' => $m[1] ?: null, 'd' => (int) $m[2], 'm' => $month, 'y' => ! empty($m[4]) ? (int) $m[4] : null];
                }
            }
        }
        if (preg_match('/(?:' . $dayRe . '\s*[,.]?\s*)?(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{2,4})/iu', $line, $m)) {
            $y = (int) $m[4];

            return ['text' => $m[0], 'day' => $m[1] ?: null, 'd' => (int) $m[2], 'm' => (int) $m[3], 'y' => $y < 100 ? 2000 + $y : $y];
        }

        return null;
    }

    private function parseHeader(array $lines, array $skip, Carbon $today): array
    {
        $out = [
            'greeting' => null, 'title' => null, 'date' => null, 'date_status' => ['state' => 'missing', 'message' => 'Tanggal agenda tidak ditemukan. Isi tanggal secara manual.'],
            'uniform' => null, 'introduction' => [], 'notes' => [], 'loose' => [],
        ];
        $paragraph = [];
        $flush = function () use (&$paragraph, &$out) {
            if ($paragraph) {
                $out['loose'][] = $paragraph;
                $paragraph = [];
            }
        };

        $count = count($lines);
        for ($i = 0; $i < $count; $i++) {
            if ($skip[$i] ?? false) {
                continue;
            }
            $line = trim($lines[$i]);
            if ($line === '') {
                $flush();
                continue;
            }

            if (! $out['greeting'] && preg_match('/^(assalam|selamat\s+(pagi|siang|sore|malam)|salam\s+sejahtera|om swastiastu|shalom)/iu', $line)) {
                $out['greeting'] = $line;
                continue;
            }

            if (preg_match('/^pakaian(?:\s+dinas)?(?:\s+hari\s+ini)?\s*[:\-]?\s*(.*)$/iu', $line, $m) || preg_match('/^seragam\s*[:\-]?\s*(.*)$/iu', $line, $m)) {
                $parts = [];
                if (trim($m[1]) !== '') {
                    $parts[] = trim($m[1]);
                }
                for ($j = $i + 1; $j < $count; $j++) {
                    $t = trim($lines[$j]);
                    if ($t === '' || ($skip[$j] ?? false)) {
                        break;
                    }
                    $parts[] = $t;
                    $i = $j;
                }
                $out['uniform'] = $parts ? implode(' ', $parts) : null;
                continue;
            }

            if (preg_match('/^(catatan|note|info(?:rmasi)?)\s*:\s*(.+)$/iu', $line, $m)) {
                $out['notes'][] = $m[2];
                continue;
            }

            if (($d = $this->findDate($line)) && ! $out['date']) {
                $this->applyDate($out, $d, $today);
                $remainder = trim(str_replace($d['text'], '', $line), " \t,.:;-–");
                if ($remainder === '' || preg_match('/^(hari|tanggal)(\s*\/\s*(hari|tanggal))?$/iu', $remainder)) {
                    continue;
                }
                $line = $remainder;
            }

            if (! $out['title'] && mb_strlen($line) <= 80 && ! str_contains($line, ':') && preg_match('/agenda|jadwal|giat|kegiatan/iu', $line)) {
                $out['title'] = $this->titleCase($line);
                continue;
            }

            $paragraph[] = $line;
        }
        $flush();

        return $out;
    }

    private function applyDate(array &$out, array $d, Carbon $today): void
    {
        $messages = [];
        $year = $d['y'];
        if (! $year) {
            $year = $today->year;
            $messages[] = 'Tahun tidak tertulis; tahun ' . $year . ' diasumsikan.';
        }
        if (! checkdate($d['m'], $d['d'], $year)) {
            $out['date_status'] = ['state' => 'invalid', 'message' => 'Tanggal tidak valid pada teks sumber.'];

            return;
        }
        $date = Carbon::create($year, $d['m'], $d['d'], 0, 0, 0, SiaTime::tz());
        if ($d['day']) {
            $key = self::DAY_INDEX[mb_strtolower(str_replace(["'", '’'], '', $d['day']))] ?? null;
            if ($key && $key !== $date->dayOfWeekIso) {
                $messages[] = 'Hari "' . $d['day'] . '" tidak sesuai dengan tanggalnya (' . SiaTime::dayName($date) . ').';
            }
        }
        $out['date'] = $date->format('Y-m-d');
        $out['date_status'] = $messages ? ['state' => 'warn', 'message' => implode(' ', $messages)] : ['state' => 'ok'];
    }

    // ------------------------------------------------------------------ kegiatan

    private function findTimes(string $text): array
    {
        if (preg_match_all(self::TIME_RE, $text, $m, PREG_SET_ORDER)) {
            return $m;
        }

        return [];
    }

    private function parseItem(array $block): array
    {
        // Rapikan: buang baris kosong di awal/akhir.
        while ($block && trim($block[0]) === '') {
            array_shift($block);
        }
        while ($block && trim(end($block)) === '') {
            array_pop($block);
        }
        $block = array_map('trim', $block);

        $item = [
            'title' => null, 'category_id' => null, 'start_time' => null, 'end_time' => null, 'time_description' => null,
            'location' => null, 'description' => null, 'responsible_label' => null, 'responsible_person' => null,
            'participants' => null, 'officers' => null, 'additional_notes' => null, 'priority' => 'normal', 'schedules' => [],
        ];
        $status = [];

        // --- Cari baris waktu.
        $timeIdx = null;
        $nonEmpty = 0;
        foreach ($block as $idx => $line) {
            if ($line === '') {
                continue;
            }
            $nonEmpty++;
            $matches = $this->findTimes($line);
            if ($matches && ($nonEmpty <= 3 || preg_match('/^(waktu|pukul|pkl|jam)\b/iu', $line))) {
                $timeIdx = $idx;
                break;
            }
        }

        $timeLeftover = '';
        $consumed = [];
        if ($timeIdx !== null) {
            $line = $block[$timeIdx];
            $matches = $this->findTimes($line);
            $main = array_shift($matches);
            $this->applyMainTime($item, $status, $main);
            foreach ($matches as $extra) {
                $sched = ['start_time' => null, 'end_time' => null, 'time_description' => null];
                $tmp = [];
                $this->applyMainTime($sched, $tmp, $extra);
                $item['schedules'][] = $sched;
            }
            $leftover = preg_replace(self::TIME_RE, ' ', $line);
            $leftover = preg_replace('/\(\s*\)|\[\s*\]/u', ' ', $leftover);
            $leftover = preg_replace('/^\s*(pkl|pukul|jam|waktu)\b\.?\s*:?/iu', '', $leftover);
            $timeLeftover = trim(preg_replace('/\s+/u', ' ', $leftover), " \t:;,.-–—()[]|/");
            if (mb_strlen(preg_replace('/[^\p{L}]/u', '', $timeLeftover)) < 2) {
                $timeLeftover = '';
            }
            $consumed[$timeIdx] = true;
        } else {
            $status['time'] = ['state' => 'missing', 'message' => 'Waktu kegiatan tidak ditemukan.'];
        }

        // --- Judul.
        $firstIdx = null;
        foreach ($block as $idx => $line) {
            if ($line !== '') {
                $firstIdx = $idx;
                break;
            }
        }
        $title = null;
        if ($timeIdx !== null && $timeIdx === $firstIdx) {
            if ($timeLeftover !== '' && ! $this->labelOf($timeLeftover)) {
                $title = $timeLeftover;
                $timeLeftover = '';
            } else {
                foreach ($block as $idx => $line) {
                    if ($idx <= $timeIdx || $line === '' || isset($consumed[$idx])) {
                        continue;
                    }
                    if (! $this->labelOf($line)) {
                        $title = $line;
                        $consumed[$idx] = true;
                    }
                    break;
                }
            }
        } elseif ($firstIdx !== null && ! $this->labelOf($block[$firstIdx])) {
            $title = $block[$firstIdx];
            $consumed[$firstIdx] = true;
        }
        $extraDescription = [];
        if ($timeLeftover !== '') {
            $extraDescription[] = $timeLeftover;
        }
        if ($title !== null) {
            $title = trim(preg_replace('/\s+/u', ' ', $title), " \t:;,-–—");
        }
        $item['title'] = $title ?: null;
        $status['title'] = $item['title']
            ? ['state' => 'ok']
            : ['state' => 'missing', 'message' => 'Judul kegiatan tidak ditemukan.'];

        // --- Baris tersisa: label atau teks bebas.
        $total = count($block);
        for ($idx = 0; $idx < $total; $idx++) {
            if (isset($consumed[$idx]) || $block[$idx] === '') {
                continue;
            }
            $line = preg_replace('/^[\-•*▪]\s*/u', '', $block[$idx]);

            if ($lab = $this->labelOf($line)) {
                [$label, $value] = $lab;
                if ($value === '') {
                    $parts = [];
                    for ($j = $idx + 1; $j < $total; $j++) {
                        if ($block[$j] === '' || $this->labelOf($block[$j])) {
                            break;
                        }
                        $parts[] = preg_replace('/^[\-•*▪]\s*/u', '', $block[$j]);
                        $consumed[$j] = true;
                    }
                    $value = implode(', ', $parts);
                }
                if ($value === '') {
                    continue;
                }
                $this->assignLabel($item, $label, $value, $extraDescription);
                continue;
            }

            if ($item['location'] === null && preg_match('/^(?:bertempat\s+)?di\s+(.{3,})$/iu', $line, $m)) {
                $item['location'] = trim($m[1], ' .');
                continue;
            }
            $extraDescription[] = $line;
        }

        $desc = array_filter([$item['description'], ...$extraDescription]);
        $item['description'] = $desc ? $this->tidy(implode("\n", $desc)) : null;
        foreach (['participants', 'officers', 'additional_notes', 'location', 'responsible_person'] as $f) {
            $item[$f] = $item[$f] !== null ? $this->tidy($item[$f]) : null;
        }

        // --- Kategori & prioritas.
        [$slug, $catState, $catMsg] = $this->inferCategory((string) $item['title'], implode(' ', array_filter([$item['description'], $item['officers'], $item['participants'], $item['additional_notes']])));
        $item['category_id'] = $this->categoryMap[$slug] ?? null;
        $status['category'] = $item['category_id'] === null
            ? ['state' => 'warn', 'message' => 'Kategori belum dapat ditentukan. Pilih kategori.']
            : ($catState === 'ok' ? ['state' => 'ok'] : ['state' => 'warn', 'message' => $catMsg]);

        $haystack = ($item['title'] ?? '') . ' ' . ($item['description'] ?? '') . ' ' . ($item['additional_notes'] ?? '');
        if (preg_match('/\b(urgent|mendesak|darurat)\b/iu', $haystack)) {
            $item['priority'] = 'urgent';
        } elseif (preg_match('/\b(penting|wajib|harap hadir|hadir tepat waktu)\b/iu', $haystack)) {
            $item['priority'] = 'important';
        }

        $item['_status'] = $status;

        return $item;
    }

    private function applyMainTime(array &$target, array &$status, array $m): void
    {
        $h1 = (int) $m['h1'];
        $m1 = (int) $m['m1'];
        if ($h1 > 23 || $m1 > 59) {
            $status['time'] = ['state' => 'invalid', 'message' => 'Waktu mulai tidak valid (' . $m['h1'] . '.' . $m['m1'] . ').'];

            return;
        }
        $target['start_time'] = sprintf('%02d:%02d', $h1, $m1);
        $status['time'] = ['state' => 'ok'];

        $h2 = $m['h2'] ?? '';
        $open = mb_strtolower($m['open'] ?? ($m['open2'] ?? ''));
        if ($h2 !== '') {
            $hh = (int) $h2;
            $mm = (int) ($m['m2'] ?? 0);
            if ($hh > 23 || $mm > 59) {
                $status['end_time'] = ['state' => 'invalid', 'message' => 'Waktu selesai tidak valid.'];

                return;
            }
            $target['end_time'] = sprintf('%02d:%02d', $hh, $mm);
            $status['end_time'] = $target['end_time'] <= $target['start_time']
                ? ['state' => 'warn', 'message' => 'Waktu selesai sama dengan atau lebih awal dari waktu mulai.']
                : ['state' => 'ok'];
        } elseif ($open !== '') {
            $target['time_description'] = in_array($open, ['dst', 'seterusnya'], true) ? 'Seterusnya' : 'Selesai';
            $status['end_time'] = ['state' => 'ok'];
        } else {
            $status['end_time'] = ['state' => 'warn', 'message' => 'Waktu selesai tidak ditemukan.'];
        }
    }

    /** Mengembalikan [labelNormal, nilai] bila baris berformat "Label: nilai". */
    private function labelOf(string $line): ?array
    {
        if (! preg_match('/^([\p{L}\/. ]{2,28}?)\s*:\s*(.*)$/u', $line, $m)) {
            return null;
        }
        $label = mb_strtolower(trim($m[1]));
        $known = array_merge(
            self::LABELS['location'], array_keys(self::LABELS['responsible']), self::LABELS['officers'],
            self::LABELS['participants'], self::LABELS['description'], self::LABELS['notes'], self::LABELS['time']
        );

        return in_array($label, $known, true) ? [$label, trim($m[2])] : null;
    }

    private function assignLabel(array &$item, string $label, string $value, array &$extraDescription): void
    {
        $value = $this->tidy($value);
        if (in_array($label, self::LABELS['time'], true)) {
            return;
        }
        if (in_array($label, self::LABELS['location'], true)) {
            $item['location'] = $item['location'] ? $item['location'] . ', ' . $value : $value;
        } elseif (isset(self::LABELS['responsible'][$label])) {
            if ($item['responsible_person'] === null) {
                $item['responsible_label'] = self::LABELS['responsible'][$label];
                $item['responsible_person'] = $value;
            } else {
                $extraDescription[] = self::LABELS['responsible'][$label] . ': ' . $value;
            }
        } elseif (in_array($label, self::LABELS['officers'], true)) {
            $item['officers'] = $item['officers'] ? $item['officers'] . '; ' . $value : $value;
        } elseif (in_array($label, self::LABELS['participants'], true)) {
            $item['participants'] = $item['participants'] ? $item['participants'] . '; ' . $value : $value;
        } elseif (in_array($label, self::LABELS['notes'], true)) {
            $item['additional_notes'] = $item['additional_notes'] ? $item['additional_notes'] . "\n" . $value : $value;
        } else {
            $extraDescription[] = ucfirst($label) . ': ' . $value;
        }
    }

    private function tidy(string $text): string
    {
        $text = preg_replace('/\bkls\.?\s*/iu', 'Kelas ', $text);
        $text = preg_replace('/\bbpk\b\.?/iu', 'Bapak', $text);
        $text = preg_replace('/,(?!\s)/u', ', ', $text);

        return trim(preg_replace('/[ ]{2,}/u', ' ', $text));
    }

    private function inferCategory(string $title, string $body): array
    {
        foreach (self::CATEGORY_RULES as $slug => $re) {
            if ($title !== '' && preg_match($re, $title)) {
                return [$slug, 'ok', null];
            }
        }
        foreach (self::CATEGORY_RULES as $slug => $re) {
            if ($body !== '' && preg_match($re, $body)) {
                return [$slug, 'warn', 'Kategori diperkirakan dari keterangan kegiatan. Periksa kembali.'];
            }
        }

        return ['informasi-umum', 'warn', 'Kategori tidak dikenali; dipilih Informasi Umum. Periksa kembali.'];
    }

    private function titleCase(string $s): string
    {
        $s = trim($s, " \t:.-");
        if (! preg_match('/\b\p{Lu}{2,}\b/u', $s) || mb_strtoupper($s) === $s) {
            return mb_convert_case(mb_strtolower($s), MB_CASE_TITLE, 'UTF-8');
        }

        return $s;
    }
}
