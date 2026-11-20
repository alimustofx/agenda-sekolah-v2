<?php

namespace Database\Seeders;

use App\Models\AgendaCategory;
use App\Models\AgendaDay;
use App\Models\Announcement;
use App\Models\DisplaySetting;
use App\Models\RunningText;
use App\Models\SchoolActivity;
use App\Models\SchoolSetting;
use App\Models\User;
use App\Models\WeatherSetting;
use App\Models\YoutubeSetting;
use App\Support\SiaTime;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SiaSeeder extends Seeder
{
    public function run(): void
    {
        // Akun awal. GANTI kata sandi setelah login pertama.
        foreach ([
            ['Super Admin', 'admin@sman1turen.sch.id', 'super_admin'],
            ['Admin Humas', 'humas@sman1turen.sch.id', 'admin_humas'],
        ] as [$name, $email, $role]) {
            $user = User::firstOrNew(['email' => $email]);
            $user->forceFill(['name' => $name, 'role' => $role, 'is_active' => true, 'email_verified_at' => now()]);
            if (! $user->exists) {
                $user->password = Hash::make('ChangeMe123!');
            }
            $user->save();
        }
        $humas = User::where('email', 'humas@sman1turen.sch.id')->first();

        SchoolSetting::current()->update([
            'school_name' => 'SMA Negeri 1 Turen',
            'tagline' => 'Pusat Informasi dan Agenda Sekolah',
            'school_address' => 'Turen, Kabupaten Malang, Jawa Timur',
        ]);
        YoutubeSetting::current();
        DisplaySetting::current()->update(['weather_enabled' => true]);
        WeatherSetting::current()->update(['is_enabled' => true]);

        // Kategori bawaan (PRD FR-AGENDA-07).
        $palette = ['#286A91', '#1598AD', '#26734D', '#454B50', '#A96D1E', '#23445F', '#777A7B', '#B33B37', '#17324D'];
        $cats = [];
        foreach (['Akademik dan KBM', 'Kesiswaan', 'Keagamaan', 'Kepegawaian', 'Rapat dan Koordinasi', 'Ekstrakurikuler', 'Sarana dan Prasarana', 'Kegiatan Eksternal', 'Informasi Umum'] as $i => $name) {
            $cats[Str::slug($name)] = AgendaCategory::firstOrCreate(['slug' => Str::slug($name)], ['name' => $name, 'color' => $palette[$i], 'is_active' => true])->id;
        }

        // Agenda contoh dari PRD (Senin, 16 November 2026). Bila hari ini berbeda, salinan untuk hari ini
        // dibuat agar TV Display langsung terisi saat dicoba.
        $dates = array_unique(['2026-11-16', SiaTime::today()->format('Y-m-d')]);
        foreach ($dates as $date) {
            if (AgendaDay::whereDate('agenda_date', $date)->exists()) {
                continue;
            }
            $day = AgendaDay::create([
                'title' => 'Agenda Kegiatan Sekolah',
                'agenda_date' => $date,
                'introduction' => 'Berikut agenda kegiatan sekolah hari ini.',
                'recipients' => "Guru dan Tendik\nMahasiswa UM Mengajar\nKeamanan/Security\nOB/Office Boy",
                'uniform_information' => 'Khaki dengan atribut lengkap.',
                'general_notes' => 'Mohon hadir tepat waktu pada setiap kegiatan.',
                'status' => 'published', 'published_at' => now(), 'created_by' => $humas?->id, 'updated_by' => $humas?->id,
            ]);

            $rows = [
                ['Piket 5S GTK', 'kepegawaian', '06:00', '06:25', null, 'Gerbang Utara dan Selatan', null, null, 'GTK', null, null, null],
                ['Upacara Bendera', 'kesiswaan', '06:30', '07:15', null, 'Lapangan Sekolah', 'Pembina', 'B. Nofa Kristanti, S.Pd.', 'OSIS/MPK Kelas XI', 'Guru, Tendik, Mahasiswa UM Mengajar', 'Materi: Bangga Berkarya, Bangga Berbudaya', null],
                ['Kegiatan Belajar Mengajar', 'akademik-dan-kbm', '08:00', '15:00', null, 'Ruang Kelas X–XII', null, null, null, 'Seluruh siswa', null, 'Hari ke-79 Semester Ganjil 2026/2027.'],
                ['Presentasi Hasil Karya Inovasi GTK', 'kepegawaian', '08:00', null, 'Selesai', 'Ruang Virtual / Zoom', 'Penanggung Jawab', 'Kepala Sekolah', null, 'Guru dan Tendik', null, null],
                ['Pelaksanaan Makan Bergizi Gratis (MBG)', 'informasi-umum', '09:15', '09:45', null, 'Kantin dan Ruang Kelas', null, null, 'Tim MBG dan OSIS', 'Siswa Kelas X dan XI', null, 'Siswa diharapkan membawa tempat minum sendiri.'],
                ['Sholat Dhuha Berjamaah', 'keagamaan', '09:45', '10:15', null, 'Masjid Sekolah', null, null, 'Rohis', 'Siswa dan guru', null, null],
                ['Rapat Koordinasi Wakil Kepala Sekolah', 'rapat-dan-koordinasi', '10:30', '11:30', null, 'Ruang Rapat', 'Pimpinan', 'Kepala Sekolah', null, 'Wakil Kepala Sekolah dan Kepala Tata Usaha', null, null],
                ['Sholat Dzuhur Berjamaah', 'keagamaan', '12:00', '12:30', null, 'Masjid Sekolah', null, null, 'Rohis', 'Seluruh warga sekolah', null, null],
                ['Latihan Paskibra', 'ekstrakurikuler', '15:30', '17:00', null, 'Lapangan Sekolah', 'Pembina', 'Pembina Paskibra', null, 'Anggota Paskibra', null, null],
                ['Perawatan Jaringan Internet Sekolah', 'sarana-dan-prasarana', '13:00', '14:30', null, 'Ruang Server', null, null, 'Teknisi dan Tim Sarpras', null, null, 'Koneksi internet dapat terputus sementara.'],
            ];
            foreach ($rows as $order => $r) {
                $day->items()->create([
                    'title' => $r[0], 'category_id' => $cats[$r[1]] ?? null, 'start_time' => $r[2], 'end_time' => $r[3],
                    'time_description' => $r[4], 'location' => $r[5], 'responsible_label' => $r[6], 'responsible_person' => $r[7],
                    'officers' => $r[8], 'participants' => $r[9], 'description' => $r[10], 'additional_notes' => $r[11],
                    'priority' => $order === 1 ? 'important' : 'normal', 'display_order' => $order + 1,
                ]);
            }
        }

        if (! Announcement::exists()) {
            Announcement::create([
                'title' => 'Pelaksanaan Presentasi Hasil Karya Inovasi GTK', 'priority' => 'important', 'category' => 'Kepegawaian',
                'content' => 'Presentasi dilaksanakan secara daring melalui Zoom. Seluruh guru dan tenaga kependidikan diharapkan bergabung tepat waktu dan menyiapkan materi masing-masing.',
                'start_at' => now(SiaTime::tz())->subDay()->format('Y-m-d 00:00'), 'end_at' => now(SiaTime::tz())->addDays(7)->format('Y-m-d 23:59'),
                'created_by' => $humas?->id,
            ]);
            Announcement::create([
                'title' => 'Tata Tertib Penggunaan Lapangan Sekolah', 'priority' => 'normal', 'category' => 'Sarana dan Prasarana',
                'content' => 'Lapangan digunakan secara bergantian sesuai jadwal. Pastikan kebersihan area setelah kegiatan selesai.',
                'start_at' => now(SiaTime::tz())->subDay()->format('Y-m-d 00:00'), 'end_at' => now(SiaTime::tz())->addDays(14)->format('Y-m-d 23:59'),
                'created_by' => $humas?->id,
            ]);
        }

        if (! SchoolActivity::exists()) {
            SchoolActivity::create([
                'title' => 'Presentasi Hasil Karya Inovasi GTK', 'location' => 'Ruang Virtual / Zoom', 'responsible_person' => 'Kepala Sekolah',
                'description' => 'Presentasi karya inovasi guru dan tenaga kependidikan.',
                'start_at' => SiaTime::today()->setTime(8, 0)->format('Y-m-d H:i:s'), 'end_at' => null, 'time_description' => 'Selesai',
            ]);
        }

        if (! RunningText::exists()) {
            RunningText::create(['content' => 'Selamat datang di SMA Negeri 1 Turen. Jaga kebersihan, ketertiban, dan kedisiplinan bersama.', 'display_order' => 1]);
            RunningText::create(['content' => 'Pakaian dinas hari ini mengikuti agenda. Mohon hadir tepat waktu pada setiap kegiatan.', 'display_order' => 2]);
        }
    }
}
