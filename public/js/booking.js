/**
 * AHASS Booking Servis - AJAX interactions
 * Hanya jQuery, tanpa reload halaman.
 *
 * Bergantung pada variabel global yang di-set dari Blade (booking.blade.php):
 *   window.appRoutes     -> { bookingStore, bookingCheckSlot, bookingList, bookingSlots, bookingDestroyBase }
 *   window.appCsrfToken  -> token CSRF (sama dengan meta[name=csrf-token])
 */
(function ($) {
    'use strict';

    function routes() {
        return window.appRoutes || {};
    }

    function csrfToken() {
        return window.appCsrfToken || $('meta[name="csrf-token"]').attr('content') || '';
    }

    // ---- Toast helper (global agar bisa dipakai elemen lain) ----
    window.showToast = function (type, message, icon) {
        var container = $('#toastContainer');
        var toast = $('<div class="toast ' + type + '"></div>')
            .append('<span class="icon">' + (icon || '') + '</span>')
            .append($('<span></span>').text(message));

        container.append(toast);
        setTimeout(function () {
            toast.addClass('hide');
            setTimeout(function () { toast.remove(); }, 350);
        }, 4000);
    };

    window.setFormErrors = function (errors) {
        $.each(errors || {}, function (field, messages) {
            var $field = $('[name="' + field + '"]');
            if ($field.length) {
                $field.closest('.form-group').addClass('has-error');
                $field.closest('.form-group').find('.error-text').show();
            }
        });
    };

    window.validateForm = function (form) {
        var valid = true;
        form.find('.form-group').removeClass('has-error');
        form.find('.error-text').hide();

        form.find('[required]').each(function () {
            if (!$(this).val() || $(this).val().trim() === '') {
                $(this).closest('.form-group').addClass('has-error');
                $(this).closest('.form-group').find('.error-text').show();
                valid = false;
            }
        });

        var plate = $('#plate_number').val().trim();
        if (plate !== '' && !/^[A-Za-z]{2}\s*\d{2,4}\s*[A-Z]{2}$/i.test(plate)) {
            $('#plate_number').closest('.form-group').addClass('has-error');
            $('#plate_number').closest('.form-group').find('.error-text').show();
            valid = false;
        }

        return valid;
    };

    // ---- Format ISO (Y-m-d) -> d-m-Y ----
    function formatDateId(iso) {
        if (!iso) return '-';
        var p = iso.split('-');
        if (p.length !== 3) return iso;
        return p[2] + '-' + p[1] + '-' + p[0];
    }

    // ---- Load ketersediaan slot untuk tanggal terpilih ----
    function loadSlotsAvailability(date) {
        if (typeof date === 'undefined' || date === null) {
            date = $('#booking_date').val() || '';
        }

        $.ajax({
            url: routes().bookingSlots,
            method: 'GET',
            data: { date: date },
            dataType: 'json',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function (res) {
                if (!res.success) return;

                var html = '';
                res.slots.forEach(function (slot) {
                    var usage = (typeof slot.usage !== 'undefined') ? slot.usage : (slot.used || 0);
                    var max = slot.max || 3;
                    var isFull = usage >= max;
                    html +=
                        '<div class="slot-item' + (isFull ? ' is-full' : '') + '" data-slot="' + slot.service_time + '">' +
                            '<div class="slot-main">' +
                                '<div class="slot-info">' +
                                    '<span class="slot-time">' + slot.service_time + '</span>' +
                                    '<span class="slot-meta">' + usage + ' / ' + max + ' kendaraan</span>' +
                                '</div>' +
                                '<div class="slot-bar"><span style="width: ' + Math.round(usage / max * 100) + '%"></span></div>' +
                            '</div>' +
                            '<span class="slot-status ' + (isFull ? 'full' : 'available') + '">' +
                                (isFull ? 'Penuh' : 'Tersedia') +
                            '</span>' +
                        '</div>';

                    // Sinkronkan label jumlah pada dropdown Jam Servis
                    $('#service_time option[value="' + slot.service_time + '"]')
                        .text(slot.service_time + ' (' + usage + '/' + max + ')');
                });
                $('#slotList').html(html);

                if (res.date) {
                    $('#slotDateLabel').text('Tanggal: ' + formatDateId(res.date));
                }
            }
        });
    }

    // ---- Cek 1 slot (dipanggil saat jam/tanggal berubah) ----
    function checkSlotAvailability(serviceTime, bookingDate) {
        if (!serviceTime) return;

        $.ajax({
            url: routes().bookingCheckSlot,
            method: 'GET',
            data: { service_time: serviceTime, booking_date: bookingDate },
            dataType: 'json',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function (res) {
                if (!res.success) return;

                var $item = $('.slot-item[data-slot="' + serviceTime + '"]');
                if (!$item.length) return;

                var isFull = res.is_full;
                $item.toggleClass('is-full', !!isFull);
                $item.find('.slot-status')
                    .attr('class', 'slot-status ' + (isFull ? 'full' : 'available'))
                    .html(isFull ? 'Penuh' : 'Tersedia');
                $item.find('.slot-meta').html(res.used + ' / ' + res.max + ' kendaraan');
                $item.find('.slot-bar > span').css('width', Math.round(res.used / res.max * 100) + '%');
            }
        });
    }

    // ---- Load daftar booking + filter + pagination ----
    function loadBookingList(page) {
        $.ajax({
            url: routes().bookingList,
            method: 'GET',
            data: {
                date: $('#filter_date').val(),
                service_time: $('#filter_time').val(),
                search: $('#searchInput').val(),
                page: page || 1
            },
            dataType: 'json',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function (res) {
                if (!res.success) {
                    window.showToast('warning', res.message || 'Gagal mengambil data.', '⚠️');
                    return;
                }

                var tbody = $('#bookingTableBody');
                var pagination = $('#pagination');
                var stats = $('#stats');
                var noData = $('#noData');

                if (res.rows.length === 0) {
                    tbody.html('<tr><td colspan="9" class="no-data">Tidak ada data booking.</td></tr>');
                    pagination.hide().empty();
                    noData.show();
                    stats.hide();
                    return;
                }

                noData.hide();
                stats.show();
                stats.text('Total: ' + res.rows.length + ' booking' +
                    (res.pagination.total ? ' | Total data: ' + res.pagination.total : ''));

                var html = '';
                res.rows.forEach(function (row) {
                    html +=
                        '<tr>' +
                            '<td class="col-no">' + row.no + '</td>' +
                            '<td class="col-plate">' + row.plate_number + '</td>' +
                            '<td>' + row.customer_name + '</td>' +
                            '<td>' + row.motorcycle_type + '</td>' +
                            '<td>' + row.service_time + '</td>' +
                            '<td>' + row.package_name + '</td>' +
                            '<td>' + row.booking_date + '</td>' +
                            '<td><span class="status-badge ' + row.status + '">' + row.status + '</span></td>' +
                            '<td class="col-action">' +
                                '<button type="button" class="btn btn-danger btn-xs js-delete-booking"' +
                                    ' data-id="' + row.id + '"' +
                                    ' data-url="' + (row.delete_url || (routes().bookingDestroyBase + '/' + row.id)) + '"' +
                                    ' data-plate="' + $('<div>').text(row.plate_number).html() + '"' +
                                    ' data-customer="' + $('<div>').text(row.customer_name).html() + '"' +
                                    ' title="Hapus booking ini">' +
                                    'Hapus' +
                                '</button>' +
                            '</td>' +
                        '</tr>';
                });
                tbody.html(html);

                pagination.empty();
                if (res.pagination) {
                    if (res.pagination.current_page > 1) {
                        pagination.append('<a href="#" data-page="' + (res.pagination.current_page - 1) + '">Prev</a>');
                    } else {
                        pagination.append('<span class="disabled">Prev</span>');
                    }

                    pagination.append('<span class="active">Halaman ' +
                        res.pagination.current_page + ' / ' + res.pagination.last_page + '</span>');

                    if (res.pagination.last_page > res.pagination.current_page) {
                        pagination.append('<a href="#" data-page="' + (res.pagination.current_page + 1) + '">Next</a>');
                    } else {
                        pagination.append('<span class="disabled">Next</span>');
                    }
                }
                pagination.show();
            }
        });
    }

    // ---- Hapus booking (AJAX DELETE, tanpa reload halaman) ----
    function deleteBooking($btn) {
        var id = $btn.data('id');
        var url = $btn.data('url');
        var plate = $btn.data('plate') || '';
        var customer = $btn.data('customer') || '';

        if (!confirm('Hapus booking ' + plate + ' (' + customer + ')?\nData yang dihapus tidak dapat dikembalikan.')) {
            return;
        }

        $btn.prop('disabled', true).text('Menghapus...');

        $.ajax({
            url: url,
            method: 'DELETE',
            data: { _token: csrfToken() },
            dataType: 'json',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken()
            },
            success: function (res) {
                if (res.success) {
                    window.showToast('success', res.message || 'Booking berhasil dihapus.', '🗑️');
                } else {
                    window.showToast('error', res.message || 'Gagal menghapus booking.', '✕');
                }
                loadSlotsAvailability($('#booking_date').val());
                loadBookingList(currentPage());
            },
            error: function (xhr) {
                var res = xhr.responseJSON || {};
                window.showToast('error', res.message || 'Terjadi kesalahan. Silakan coba lagi.', '✕');
            },
            complete: function () {
                $btn.prop('disabled', false).text('Hapus');
            }
        });
    }

    // Nomor halaman aktif disimpan sebagai <span class="active"> (bukan <a>),
    // jadi selector harus keduanya agar tidak selalu kembali ke halaman 1.
    function currentPage() {
        var active = $('#pagination .active');
        if (!active.length) return 1;
        var m = /Halaman\s+(\d+)/.exec(active.text());
        return m ? parseInt(m[1], 10) : 1;
    }

    window.deleteBooking = deleteBooking;
    window.loadSlotsAvailability = loadSlotsAvailability;
    window.checkSlotAvailability = checkSlotAvailability;
    window.loadBookingList = loadBookingList;

    // ---- Bootstrap ----
    $(function () {
        var today = new Date().toISOString().split('T')[0];
        $('#booking_date').attr('min', today);

        loadSlotsAvailability();
        loadBookingList();

        // Submit booking (AJAX)
        $('#bookingForm').on('submit', function (e) {
            e.preventDefault();

            var form = $(this);
            var submitBtn = $('#submitBtn');
            var btnText = submitBtn.find('.btn-text');
            var btnSpinner = submitBtn.find('.btn-spinner');

            if (!window.validateForm(form)) return;

            submitBtn.prop('disabled', true);
            btnText.hide();
            btnSpinner.show();

            $.ajax({
                url: routes().bookingStore,
                method: 'POST',
                data: form.serialize(),
                dataType: 'json',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken()
                },
                success: function (res) {
                    if (res.success) {
                        window.showToast('success', res.message || 'Booking servis berhasil!', '✅');
                        form[0].reset();
                        loadSlotsAvailability($('#booking_date').val());
                        loadBookingList();
                    } else {
                        window.showToast('error', res.message || 'Gagal booking', '✕');
                        if (res.errors) window.setFormErrors(res.errors);
                    }
                },
                error: function (xhr) {
                    var res = xhr.responseJSON || {};
                    if (res.success === false && res.message) {
                        window.showToast('error', res.message, '✕');
                    } else {
                        window.showToast('error', 'Terjadi kesalahan. Silakan coba lagi.', '✕');
                    }
                    if (xhr.status === 422) window.setFormErrors(res.errors || {});
                },
                complete: function () {
                    submitBtn.prop('disabled', false);
                    btnText.show();
                    btnSpinner.hide();
                }
            });
        });

        // Ganti tanggal -> muat ulang ketersediaan slot untuk tanggal tsb
        $('#booking_date').on('change', function () {
            loadSlotsAvailability($(this).val());
            checkSlotAvailability($('#service_time').val(), $(this).val());
        });

        // Ganti jam servis -> cek slot spesifik tsb
        $('#service_time').on('change', function () {
            checkSlotAvailability($(this).val(), $('#booking_date').val());
        });

        // Filter
        $('#filterBtn').on('click', function () { loadBookingList(); });
        $('#searchInput').on('keyup', function () { loadBookingList(); });
        $('#filter_date, #filter_time').on('change', function () { loadBookingList(); });

        // Pagination (delegated)
        $('#pagination').on('click', 'a[data-page]', function (e) {
            e.preventDefault();
            loadBookingList($(this).data('page'));
        });

        // Hapus booking (delegated, karena tbody di-render ulang oleh AJAX)
        $('#bookingTableBody').on('click', '.js-delete-booking', function (e) {
            e.preventDefault();
            deleteBooking($(this));
        });
    });
})(jQuery);
