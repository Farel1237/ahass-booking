<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Package;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class BookingController extends Controller
{
    public const int MAX_BOOKINGS_PER_SLOT = 3;

    public const array AVAILABLE_SLOTS = [
        '08:00 - 09:00',
        '09:00 - 10:00',
        '10:00 - 11:00',
        '11:00 - 12:00',
        '13:00 - 14:00',
        '14:00 - 15:00',
        '15:00 - 16:00',
    ];

    public function showBooking(Request $request): \Illuminate\View\View
    {
        $packages = Package::orderBy('id')->get();

        // Ketersediaan slot dihitung untuk satu tanggal (default: hari ini),
        // supaya tampilan cocok dengan aturan penolakan "maks. 3 per slot per hari".
        $selectedDate = $request->input('date') ?: now()->toDateString();
        $slots = $this->getSlotsWithUsage($selectedDate);

        return view('booking.booking', compact('packages', 'slots', 'selectedDate'));
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'plate_number' => ['required', 'string', 'max:20', 'regex:/^[A-Za-z]{2}\s*\d{2,4}\s*[A-Z]{2}$/'],
            'customer_name' => ['required', 'string', 'max:100'],
            'motorcycle_type' => ['required', 'string', 'max:50'],
            'service_time' => ['required', 'string', 'max:20', 'in:' . implode(',', self::AVAILABLE_SLOTS)],
            'package_id' => ['required', 'integer', 'exists:packages,id'],
            'booking_date' => ['required', 'date', 'date_format:Y-m-d'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => '⚠️ Silakan lengkapi seluruh data.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $booking = $validator->validated();

        $isFull = $this->isSlotFull($booking['service_time'], $booking['booking_date']);

        if ($isFull) {
            return response()->json([
                'success' => false,
                'message' => "✕ Slot {$booking['service_time']} sudah penuh. Silakan pilih slot lain.",
            ], 409);
        }

        Booking::create($booking);

        return response()->json([
            'success' => true,
            'message' => 'Booking servis berhasil.',
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        // `id` berasal dari route parameter, jadi divalidasi langsung (bukan dari body).
        $validator = Validator::make(['id' => $id], [
            'id' => ['required', 'integer', 'exists:bookings,id'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => '⚠️ Data booking tidak ditemukan.',
                'errors' => $validator->errors(),
            ], 404);
        }

        $booking = Booking::findOrFail($id);
        $booking->delete();

        return response()->json([
            'success' => true,
            'message' => "Booking {$booking->plate_number} ({$booking->customer_name}) berhasil dihapus.",
        ]);
    }

    public function checkSlotAvailability(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'service_time' => ['required', 'string', 'max:20', 'in:' . implode(',', self::AVAILABLE_SLOTS)],
            'booking_date' => ['nullable', 'date', 'date_format:Y-m-d'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => '⚠️ Format data tidak valid.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        $bookingDate = $data['booking_date'] ?? null;

        return response()->json([
            'success' => true,
            'is_full' => $this->isSlotFull($data['service_time'], $bookingDate),
            'used' => $this->countBookingsInSlot($data['service_time'], $bookingDate),
            'max' => self::MAX_BOOKINGS_PER_SLOT,
        ]);
    }

    public function getList(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'date' => ['nullable', 'date', 'date_format:Y-m-d'],
            'service_time' => ['nullable', 'string', 'max:20', 'in:' . implode(',', self::AVAILABLE_SLOTS)],
            'search' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => '⚠️ Parameter filter tidak valid.',
            ], 422);
        }

        $query = Booking::with('package')
            ->orderBy('id', 'desc');

        if ($request->filled('date')) {
            $query->whereDate('booking_date', $request->input('date'));
        }

        if ($request->filled('service_time')) {
            $query->where('service_time', $request->input('service_time'));
        }

        $search = trim((string) $request->input('search'));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('plate_number', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%");
            });
        }

        $bookings = $query->paginate(10);

        // Nomor urut tabel: berurutan 1..N (bukan id database, yang bisa bolong
        // karena data terhapus). firstItem() melanjutkan penomoran antar halaman.
        $rows = $bookings->getCollection()->map(function ($booking, $index) use ($bookings) {
            return [
                'id' => $booking->id,
                'no' => $bookings->firstItem() + $index,
                'plate_number' => $booking->plate_number,
                'customer_name' => $booking->customer_name,
                'motorcycle_type' => $booking->motorcycle_type,
                'service_time' => $booking->service_time,
                'package_name' => $booking->package->name ?? '',
                'booking_date' => $booking->booking_date->format('d-m-Y'),
                'status' => $booking->status,
                'delete_url' => route('booking.destroy', $booking->id),
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Daftar booking berhasil diambil.',
            'rows' => $rows,
            'pagination' => [
                'current_page' => $bookings->currentPage(),
                'last_page' => $bookings->lastPage(),
                'per_page' => $bookings->perPage(),
                'total' => $bookings->total(),
            ],
        ]);
    }

    public function getSlots(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'date' => ['nullable', 'date', 'date_format:Y-m-d'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => '⚠️ Tanggal tidak valid.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $date = $request->filled('date') ? $request->input('date') : now()->toDateString();

        $slots = collect(self::AVAILABLE_SLOTS)->map(function (string $serviceTime) use ($date) {
            $used = $this->countBookingsInSlot($serviceTime, $date);

            return [
                'service_time' => $serviceTime,
                'usage' => $used,
                'used' => $used,
                'max' => self::MAX_BOOKINGS_PER_SLOT,
                'available' => $used < self::MAX_BOOKINGS_PER_SLOT,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Daftar slot berhasil diambil.',
            'date' => $date,
            'slots' => $slots,
        ]);
    }

    public function resetBookingStatus(): JsonResponse
    {
        $slots = collect(self::AVAILABLE_SLOTS)->map(function (string $serviceTime) {
            $dates = Booking::where('service_time', $serviceTime)
                ->distinct()
                ->pluck('booking_date')
                ->toArray();

            $dates = array_values(array_unique(array_filter($dates)));

            return [
                'service_time' => $serviceTime,
                'dates' => $dates,
                'usage' => $this->countBookingsInSlot($serviceTime),
            ];
        });

        return response()->json([
            'success' => true,
            'slots' => $slots,
        ]);
    }

    private function getSlotsWithUsage(?string $bookingDate = null): array
    {
        $date = $bookingDate ?: now()->toDateString();
        $rows = [];

        foreach (self::AVAILABLE_SLOTS as $serviceTime) {
            $used = $this->countBookingsInSlot($serviceTime, $date);
            $rows[] = [
                'service_time' => $serviceTime,
                'used' => $used,
                'max' => self::MAX_BOOKINGS_PER_SLOT,
                'available' => $used < self::MAX_BOOKINGS_PER_SLOT,
            ];
        }

        return $rows;
    }

    private function countBookingsInSlot(string $serviceTime, ?string $bookingDate = null): int
    {
        $query = Booking::where('service_time', $serviceTime);

        if (! is_null($bookingDate)) {
            $query->whereDate('booking_date', $bookingDate);
        }

        return (int) $query->count();
    }

    private function isSlotFull(string $serviceTime, ?string $bookingDate = null): bool
    {
        return $this->countBookingsInSlot($serviceTime, $bookingDate) >= self::MAX_BOOKINGS_PER_SLOT;
    }
}
