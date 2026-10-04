/**
 * AHASS Booking Servis - AJAX interactions
 *
 * - Submit booking via jQuery AJAX (no page reload)
 * - Check slot availability before submitting
 * - Fetch/refresh booking list with filters (AJAX, no reload)
 * - Update slot availability status
 */

(function ($) {
    'use strict';

    $(function () {
        var $bookingForm = $('#bookingForm');
        var $submitBtn = $('#submitBtn');
        var $btnText = $submitBtn.find('.btn-text');
        var $btnSpinner = $submitBtn.find('.btn-spinner');
        var $toastContainer = $('#toastContainer');

        // ---- Init ----
        var dateInput = $('#booking_date');
        var today = new Date();
        today.setDate(today.getDate());
        dateInput.attr('min', today.toISOString().split('T')[0]);

        loadSlotsAvailability();
        loadBookingList();

        // ---- Booking submit (AJAX) ----
        $bookingForm.on('submit', function (e) {
            e.preventDefault();

            var form = $(this);

            if (!validateForm(form)) {
                return;
            }

            setSubmitting(true);

            $.ajax({
                url: appRoutes.bookingStore,
                method: 'POST',
                data: form.serialize(),
                dataType: 'json',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function (res) {
                    if (res.success) {
                        showToast('success', res.message || 'Booking servis berhasil!', '✅');
                        form[0].reset();
                        loadSlotsAvailability();
                        loadBookingList();
                    } else {
                        showToast('error', res.message || 'Gagal booking', '✕');
                        if (res.errors) {
                            setFormErrors(res.errors);
                        }
                    }
                },
                error: function (xhr) {
                    var res = xhr.responseJSON || {};
                    if (res.success === false && res.message) {
                        showToast('error', res.message, '✕');
                    } else {
                        showToast('error', 'Terjadi kesalahan. Silakan coba lagi.', '✕');
                    }
                    if (xhr.status === 422) {
                        setFormErrors(xhr.responseJSON.errors || {});
                    }
                },
                complete: function () {
                    setSubmitting(false);
                }
            });
        });

        // ---- Cek ketersediaan slot (extrak server-side) ----
        $('#service_time, #booking_date').on('change', function () {
            var serviceTime = $('#service_time').val();
            var bookingDate = $('#booking_date').val();
            if (serviceTime) {
                checkSlotAvailability(serviceTime, bookingDate);
            }
        });

        // ---- Filter daftar booking (AJAX) ----
        $('#filterBtn').on('click', function () {
            loadBookingList();
        });

        $('#searchInput').on('keyup', function () {
            loadBookingList();
        });

        // ---- Load slot availability ----
        function loadSlotsAvailability() {
            $.ajax({
                url: appRoutes.bookingSlots,
                method: 'GET',
                dataType: 'json',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                success: function (res) {
                    if (res.success) {
                        var html = '';
                        res.slots.forEach(function (slot) {
                            var isFull = slot.usage >= 3;
                            html +=
                                '<div class="slot-item">' +
                                    '<div class="slot-info">' +
                                        '<span class="slot-time">' + slot.service_time + '</span>' +
                                        '<span class="slot-meta">' + slot.usage + ' / 3 kendaraan</span>' +
                                    '</div>' +
                                    '<span class="slot-status ' + (isFull ? 'full' : 'available') + '">' +
                                        (isFull ? 'Penuh' : 'Tersedia') +
                                    '</span>' +
                                '</div>';
                        });
                        $('#slotList').html(html);
                    }
                }
            });
        }

        // ---- Cek ketersediaan slot (satu slot) ----
        function checkSlotAvailability(serviceTime, bookingDate) {
            $.ajax({
                url: appRoutes.bookingStore + '/check-slot',
                method: 'GET',
                data: {
                    _token: appCsrfToken,
                    service_time: serviceTime,
                    booking_date: bookingDate
                },
                dataType: 'json',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                success: function (res) {
                    if (res.success) {
                        var isFull = res.is_full;
                        var $slotItem = $('#slot-' + serviceTime);
                        if ($slotItem.length) {
                            $slotItem.find('.slot-status').attr('class', isFull ? 'slot-status full' : 'slot-status available');
                            $slotItem.find('.slot-status').html(isFull ? 'Penuh' : 'Tersedia');
                            $slotItem.find('.slot-meta').html(res.used + ' / 3 kendaraan');
                        }
                    }
                }
            });
        }

        // ---- Load daftar booking ----
        function loadBookingList() {
            var date = $('#filter_date').val();
            var serviceTime = $('#filter_time').val();
            var search = $('#searchInput').val();

            $.ajax({
                url: appRoutes.bookingList,
                method: 'GET',
                data: {
                    _token: appCsrfToken,
                    date: date,
                    service_time: serviceTime,
                    search: search
                },
                dataType: 'json',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                success: function (res) {
                    if (!res.success) {
                        showToast('warning', res.message || 'Gagal mengambil data.', '⚠️');
                        return;
                    }

                    var tbody = $('#bookingTableBody');
                    var pagination = $('#pagination');
                    var stats = $('#stats');
                    var noData = $('#noData');

                    if (res.rows.length === 0) {
                        tbody.html('<tr><td colspan="8" class="no-data">Tidak ada data booking.</td></tr>');
                        pagination.hide();
                        noData.show();
                        stats.hide();
                        return;
                    }

                    noData.hide();
                    stats.show();
                    stats.text('Total: ' + res.rows.length + ' booking' + (res.pagination.total ? ' | Total data: ' + res.pagination.total : ''));

                    var html = '';
                    res.rows.forEach(function (row) {
                        html +=
                            '<tr>' +
                                '<td>' + row.no + '</td>' +
                                '<td>' + row.plate_number + '</td>' +
                                '<td>' + row.customer_name + '</td>' +
                                '<td>' + row.motorcycle_type + '</td>' +
                                '<td>' + row.service_time + '</td>' +
                                '<td>' + row.package_name + '</td>' +
                                '<td>' + row.booking_date + '</td>' +
                                '<td><span class="status-badge ' + row.status + '">' + row.status + '</span></td>' +
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
                        pagination.append('<span class="active">Halaman ' + res.pagination.current_page + ' / ' + res.pagination.last_page + '</span>');
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

        // ---- Validasi form ----
        function validateForm(form) {
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
        }

        // ---- Set error highlight ----
        function setFormErrors(errors) {
            $.each(errors, function (field, messages) {
                var $field = $('[name="' + field + '"]');
                if ($field.length) {
                    $field.closest('.form-group').addClass('has-error');
                    $field.closest('.form-group').find('.error-text').show();
                }
            });
        }

        // ---- Toast ----
        function showToast(type, message, icon) {
            var toast = $('<div class="toast ' + type + '"></div>')
                .append('<span class="icon">' + (icon || '') + '</span>')
                .append('<span>' + message + '</span>');
            $toastContainer.append(toast);
            setTimeout(function () {
                toast.addClass('hide');
                setTimeout(function () { toast.remove(); }, 350);
            }, 4000);
        }

        // ---- Submit button state ----
        function setSubmitting(submitting) {
            $submitBtn.prop('disabled', submitting);
            $btnText.toggle(!submitting);
            $btnSpinner.toggle(submitting);
        }
    });
})(jQuery);
