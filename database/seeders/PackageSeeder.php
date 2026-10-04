<?php

namespace Database\Seeders;

use App\Models\Package;
use Illuminate\Database\Seeder;

class PackageSeeder extends Seeder
{
    public function run(): void
    {
        $packages = [
            [
                'name' => 'Service Ringan',
                'description' => 'Pencek, oli, dan pengecekan umum untuk kendaraan dalam kondisi baik.',
                'price' => 150000,
            ],
            [
                'name' => 'Service Lengkap',
                'description' => 'Pencek intensif, oli, filter, dan perawatan menyeluruh.',
                'price' => 350000,
            ],
            [
                'name' => 'Ganti Oli',
                'description' => 'Pelayanan ganti oli dengan merek oli resmi AHASS.',
                'price' => 100000,
            ],
            [
                'name' => 'Service CVT',
                'description' => 'Pencek dan perawatan khusus untuk transmisi CVT.',
                'price' => 220000,
            ],
        ];

        foreach ($packages as $package) {
            Package::create($package);
        }
    }
}
