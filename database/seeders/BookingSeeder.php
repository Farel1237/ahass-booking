<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Package;
use Illuminate\Database\Seeder;

class BookingSeeder extends Seeder
{
    public function run(): void
    {
        $slots = [
            '08:00 - 09:00',
            '09:00 - 10:00',
            '10:00 - 11:00',
            '11:00 - 12:00',
            '13:00 - 14:00',
            '14:00 - 15:00',
            '15:00 - 16:00',
        ];

        $packageId = function (string $name) {
            return Package::where('name', $name)->value('id');
        };

        $bookings = [
            // 08:00 - 09:00: 2 kendaraan (agar tampil "2 / 3")
            ['plate_number' => 'DN 1234 XX', 'customer_name' => 'Moh Faldy', 'motorcycle_type' => 'Honda Vario 150', 'service_time' => '08:00 - 09:00', 'package_id' => $packageId('Service Ringan'), 'booking_date' => '2026-10-05'],
            ['plate_number' => 'SB 5678 YY', 'customer_name' => 'Ani Lestari', 'motorcycle_type' => 'Honda Beat 110', 'service_time' => '08:00 - 09:00', 'package_id' => $packageId('Ganti Oli'), 'booking_date' => '2026-10-05'],

            // 09:00 - 10:00: 3 kendaraan (penuh)
            ['plate_number' => 'BD 9012 ZZ', 'customer_name' => 'Budi Santoso', 'motorcycle_type' => 'Honda Super Cub', 'service_time' => '09:00 - 10:00', 'package_id' => $packageId('Service Lengkap'), 'booking_date' => '2026-10-05'],
            ['plate_number' => 'JM 3456 AB', 'customer_name' => 'Joko Widodo', 'motorcycle_type' => 'Honda XRS 150', 'service_time' => '09:00 - 10:00', 'package_id' => $packageId('Service Ringan'), 'booking_date' => '2026-10-06'],
            ['plate_number' => 'KL 7890 CD', 'customer_name' => 'Sari Dewi', 'motorcycle_type' => 'Honda Beat 110', 'service_time' => '09:00 - 10:00', 'package_id' => $packageId('Ganti Oli'), 'booking_date' => '2026-10-06'],

            // 10:00 - 11:00: 1 kendaraan
            ['plate_number' => 'MN 2468 EF', 'customer_name' => 'Ahmad Fauzi', 'motorcycle_type' => 'Honda Vario 150', 'service_time' => '10:00 - 11:00', 'package_id' => $packageId('Service CVT'), 'booking_date' => '2026-10-06'],

            // 11:00 - 12:00: 2 kendaraan
            ['plate_number' => 'PQ 1357 GH', 'customer_name' => 'Rina Wulandari', 'motorcycle_type' => 'Honda Vario 150', 'service_time' => '11:00 - 12:00', 'package_id' => $packageId('Service Lengkap'), 'booking_date' => '2026-10-07'],
            ['plate_number' => 'ST 9753 UV', 'customer_name' => 'Dedi Pratama', 'motorcycle_type' => 'Honda Beat 110', 'service_time' => '11:00 - 12:00', 'package_id' => $packageId('Service Ringan'), 'booking_date' => '2026-10-07'],

            // 13:00 - 14:00: 0 kendaraan
            // 14:00 - 15:00: 0 kendaraan
            // 15:00 - 16:00: 0 kendaraan
        ];

        foreach ($bookings as $booking) {
            Booking::create($booking);
        }
    }
}
