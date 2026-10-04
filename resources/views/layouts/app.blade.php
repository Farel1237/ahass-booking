<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'AHASS') }} - Booking Servis</title>

    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <script src="{{ asset('js/jquery.min.js') }}"></script>
</head>
<body>
@include('booking.partials.navbar')

<main class="page">
    @yield('content')
</main>

<footer class="footer">
    <p>&copy; {{ date('Y') }} <b>AHASS Booking Servis</b> &middot; Sistem Booking Servis Honda</p>
</footer>

<div class="toast-container" id="toastContainer"></div>

<script src="{{ asset('js/booking.js') }}"></script>
@stack('scripts')
</body>
</html>
