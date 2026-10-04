@extends('layouts.app')

@section('content')
@php
    $totalUsed = collect($slots)->sum('used');
    $availableSlots = collect($slots)->where('available', true)->count();
    $fullSlots = collect($slots)->where('available', false)->count();
@endphp

{{-- ======================= HERO ======================= --}}
<section class="hero">
    <h1>Booking Servis AHASS</h1>
    <p>Daftarkan kendaraan Anda untuk servis di AHASS. Pilih jam servis, tanggal, dan paket perawatan.
       Setiap slot dibatasi maksimal <b>3 kendaraan</b>.</p>

    <div class="hero-stats">
        <div class="stat-chip">
            <span class="dot" style="background:#4ade80"></span>
            <span>Slot tersedia <b>{{ $availableSlots }}</b></span>
        </div>
        <div class="stat-chip">
            <span class="dot" style="background:#fb7185"></span>
            <span>Slot penuh <b>{{ $fullSlots }}</b></span>
        </div>
        <div class="stat-chip">
            <span class="dot" style="background:#60a5fa"></span>
            <span>Total booking <b>{{ $totalUsed }}</b></span>
        </div>
        <div class="stat-chip">
            <span class="dot" style="background:#fbbf24"></span>
            <span>Paket servis <b>{{ $packages->count() }}</b></span>
        </div>
    </div>
</section>

{{-- ======================= FORM + SLOT ======================= --}}
<div class="grid-2">
    {{-- Form booking --}}
    <div class="card" id="buat-booking">
        <div class="card-head">
            <span class="icon-badge">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
            </span>
            <div>
                <div class="card-title">Buat Booking Baru</div>
                <div class="card-sub">Isi data kendaraan dan jadwal servis</div>
            </div>
        </div>

        <form id="bookingForm" novalidate>
            @csrf

            <div class="form-group">
                <label for="plate_number">Plat Nomor <span class="text-red-500">*</span></label>
                <div class="input-wrap with-icon">
                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="3"/><path d="M7 9h4M7 12h10M7 15h6"/></svg>
                    <input type="text" id="plate_number" name="plate_number" placeholder="Contoh: DN 1234 XX" value="{{ old('plate_number') }}" autocomplete="off" required>
                </div>
                <p class="error-text">Plat nomor tidak valid. Format yang diizinkan: 2 huruf + angka, contoh DN 1234 XX.</p>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="customer_name">Nama Pelanggan <span class="text-red-500">*</span></label>
                    <div class="input-wrap with-icon">
                        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="3.5"/><path d="M5 19c0-3.3 3.1-5.5 7-5.5s7 2.2 7 5.5"/></svg>
                        <input type="text" id="customer_name" name="customer_name" placeholder="Nama lengkap" value="{{ old('customer_name') }}" autocomplete="off" required>
                    </div>
                    <p class="error-text">Nama pelanggan wajib diisi.</p>
                </div>

                <div class="form-group">
                    <label for="motorcycle_type">Tipe Motor <span class="text-red-500">*</span></label>
                    <div class="input-wrap with-icon">
                        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="5.5" cy="17" r="3"/><circle cx="18.5" cy="17" r="3"/><path d="M8.5 17h7M12 17l-3-6h4.5l3.5 6M9 11h3"/></svg>
                        <input type="text" id="motorcycle_type" name="motorcycle_type" placeholder="Contoh: Honda Vario 150" value="{{ old('motorcycle_type') }}" autocomplete="off" required>
                    </div>
                    <p class="error-text">Tipe motor wajib diisi.</p>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="service_time">Jam Servis <span class="text-red-500">*</span></label>
                    <div class="input-wrap with-icon">
                        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="8"/><path d="M12 8v4l3 2"/></svg>
                        <select id="service_time" name="service_time" required>
                            <option value="">Pilih jam servis</option>
                            @foreach($slots as $slot)
                                <option value="{{ $slot['service_time'] }}" {{ old('service_time') === $slot['service_time'] ? 'selected' : '' }}>
                                    {{ $slot['service_time'] }} ({{ $slot['used'] }}/{{ $slot['max'] }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <p class="error-text">Pilih jam servis.</p>
                </div>

                <div class="form-group">
                    <label for="booking_date">Tanggal Booking <span class="text-red-500">*</span></label>
                    <div class="input-wrap with-icon">
                        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="16" rx="3"/><path d="M3 9h18M8 3v4M16 3v4"/></svg>
                        <input type="date" id="booking_date" name="booking_date" value="{{ old('booking_date', $selectedDate) }}" required>
                    </div>
                    <p class="error-text">Tanggal booking wajib diisi.</p>
                </div>
            </div>

            <div class="form-group">
                <label for="package_id">Paket Servis <span class="text-red-500">*</span></label>
                <div class="input-wrap with-icon">
                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 8l8-4 8 4-8 4-8-4z"/><path d="M4 8v8l8 4 8-4V8"/><path d="M12 12v8"/></svg>
                    <select id="package_id" name="package_id" required>
                        <option value="">Pilih paket servis</option>
                        @foreach($packages as $package)
                            <option value="{{ $package->id }}" {{ old('package_id') == $package->id ? 'selected' : '' }}>
                                {{ $package->name }} - Rp {{ number_format($package->price, 0, ',', '.') }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <p class="error-text">Pilih paket servis.</p>
            </div>

            <button type="submit" class="btn btn-primary btn-block" id="submitBtn">
                <span class="btn-text">Booking Sekarang</span>
                <span class="btn-spinner" style="display:none">⏳ Memproses...</span>
            </button>
        </form>
    </div>

    {{-- Ketersediaan slot --}}
    <div class="card">
        <div class="card-head">
            <span class="icon-badge">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="16" rx="3"/><path d="M3 9h18M8 3v4M16 3v4"/><path d="M9 14l2 2 4-4"/></svg>
            </span>
            <div>
                <div class="card-title">Ketersediaan Slot</div>
                <div class="card-sub">Maksimal 3 kendaraan per slot <b>per hari</b></div>
            </div>
            <span class="badge gray" id="slotDateLabel" style="margin-left:auto">Tanggal: {{ \Illuminate\Support\Carbon::parse($selectedDate)->format('d-m-Y') }}</span>
        </div>

        <div class="slot-list" id="slotList">
            @foreach($slots as $slot)
                <div class="slot-item {{ $slot['available'] ? '' : 'is-full' }}" data-slot="{{ $slot['service_time'] }}">
                    <div class="slot-main">
                        <div class="slot-info">
                            <span class="slot-time">{{ $slot['service_time'] }}</span>
                            <span class="slot-meta">{{ $slot['used'] }} / {{ $slot['max'] }} kendaraan</span>
                        </div>
                        <div class="slot-bar"><span style="width: {{ (int) round($slot['used'] / max($slot['max'], 1) * 100) }}%"></span></div>
                    </div>
                    <span class="slot-status {{ $slot['available'] ? 'available' : 'full' }}">
                        {{ $slot['available'] ? 'Tersedia' : 'Penuh' }}
                    </span>
                </div>
            @endforeach
        </div>
    </div>
</div>

{{-- ======================= FILTER ======================= --}}
<div class="card">
    <div class="card-head">
        <span class="icon-badge">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h16M7 12h10M10 18h4"/></svg>
        </span>
        <div>
            <div class="card-title">Filter &amp; Pencarian</div>
            <div class="card-sub">Saring daftar booking berdasarkan tanggal, jam, atau kata kunci</div>
        </div>
    </div>

    <div class="toolbar">
        <div class="form-group mb-0">
            <label for="filter_date">Tanggal</label>
            <input type="date" id="filter_date">
        </div>
        <div class="form-group mb-0">
            <label for="filter_time">Jam Servis</label>
            <select id="filter_time">
                <option value="">Semua jam</option>
                @foreach($slots as $slot)
                    <option value="{{ $slot['service_time'] }}">{{ $slot['service_time'] }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group mb-0 search-field">
            <label for="searchInput">Cari Plat / Nama</label>
            <input type="text" id="searchInput" placeholder="Ketik plat nomor atau nama pelanggan...">
        </div>
        <div class="form-group mb-0 toolbar-action">
            <button class="btn btn-primary btn-block" id="filterBtn" type="button">Terapkan</button>
        </div>
    </div>
</div>

{{-- ======================= DAFTAR BOOKING ======================= --}}
<div class="card" id="daftar-booking">
    <div class="card-head">
        <span class="icon-badge">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/></svg>
        </span>
        <div>
            <div class="card-title">Daftar Booking</div>
            <div class="card-sub">Data booking yang tersimpan</div>
        </div>
        <span class="badge gray" style="margin-left:auto">Maks. 3 / slot / hari</span>
    </div>

    <p class="stats" id="stats"></p>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Plat Nomor</th>
                    <th>Nama Pelanggan</th>
                    <th>Tipe Motor</th>
                    <th>Jam Servis</th>
                    <th>Paket Servis</th>
                    <th>Tanggal Booking</th>
                    <th>Status</th>
                    <th class="col-action">Aksi</th>
                </tr>
            </thead>
            <tbody id="bookingTableBody">
                <tr>
                    <td colspan="9" class="no-data">Sedang memuat daftar booking...</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="pagination" id="pagination"></div>
    <p class="no-data" id="noData" style="display:none">Tidak ada data booking.</p>
</div>
@endsection

@push('scripts')
<script>
    // Endpoint & token untuk public/js/booking.js
    window.appRoutes = {
        bookingStore: @json(route('booking.store')),
        bookingCheckSlot: @json(route('booking.checkSlot')),
        bookingList: @json(route('booking.list')),
        bookingDestroyBase: @json(url('/booking')),
        bookingSlots: @json(route('booking.slots'))
    };
    window.appCsrfToken = @json(csrf_token());
</script>
@endpush
