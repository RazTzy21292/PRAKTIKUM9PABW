<?php

namespace Modules\Buku\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Buku\Models\Buku;

class BukuSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            ['Laravel untuk Pemula', 'Budi Santoso', 'Informatika Press', 2023, 5],
            ['Pemrograman Web dengan React', 'Siti Aminah', 'Graha Ilmu', 2024, 3],
            ['Basis Data Relasional', 'Ahmad Fauzi', 'Andi Offset', 2021, 7],
            ['Algoritma dan Struktur Data', 'Rina Marlina', 'Erlangga', 2020, 4],
            ['Jaringan Komputer Dasar', 'Dedi Kurniawan', 'Informatika Press', 2019, 6],
            ['Rekayasa Perangkat Lunak', 'Maya Sari', 'Graha Ilmu', 2022, 2],
        ];

        foreach ($data as [$judul, $penulis, $penerbit, $tahun, $stok]) {
            Buku::create([
                'judul' => $judul,
                'penulis' => $penulis,
                'penerbit' => $penerbit,
                'tahun_terbit' => $tahun,
                'stok' => $stok,
            ]);
        }
    }
}
