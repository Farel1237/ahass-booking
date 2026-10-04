<?php

use App\Http\Controllers\BookingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::redirect('/', '/booking')->name('home');
Route::get('/booking', [BookingController::class, 'showBooking'])->name('booking');
Route::post('/booking', [BookingController::class, 'store'])->name('booking.store');
Route::delete('/booking/{id}', [BookingController::class, 'destroy'])->name('booking.destroy')->whereNumber('id');
Route::get('/booking/list', [BookingController::class, 'getList'])->name('booking.list');
Route::get('/booking/slots', [BookingController::class, 'getSlots'])->name('booking.slots');
Route::get('/booking/check-slot', [BookingController::class, 'checkSlotAvailability'])->name('booking.checkSlot');
Route::get('/booking/reset-status', [BookingController::class, 'resetBookingStatus'])->name('booking.resetStatus');
