# Sistem Booking Servis AHASS Honda

## Prasyarat

- **PHP 8.2+** (pastikan ekstensi `pdo_mysql` aktif)
- **Composer**
- **MySQL** (mis. bawaan Laragon/XAMPP)

Cek versi:

```sh
php -v
composer -V
```

## Cara Menjalankan

> Jalankan seluruh perintah dari folder root proyek (folder yang berisi `artisan`).

### 1. Install dependency

```sh
composer install
```

### 2. Siapkan konfigurasi environment

```sh
cp .env.example .env
php artisan key:generate
```

### 3. Siapkan database MySQL

Buat database (sekali saja):

```sql
CREATE DATABASE ahass_booking CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Pastikan konfigurasi pada `.env` sesuai:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ahass_booking
DB_USERNAME=root
DB_PASSWORD=
```

Sesuaikan `DB_USERNAME` / `DB_PASSWORD` bila kredensial MySQL Anda berbeda.

> **Alternatif tanpa MySQL (opsional):** gunakan SQLite — set pada `.env`:
> `DB_CONNECTION=sqlite` dan `DB_DATABASE=database/database.sqlite`,
> lalu buat file `database/database.sqlite` (kosong).

### 4. Migrasi database + data contoh

```sh
php artisan migrate:fresh --seed
```

### 5. Jalankan server

```sh
php artisan serve
```

Aplikasi berjalan di **http://127.0.0.1:8000**.

| Halaman | URL |
|---|---|
| Root (otomatis diarahkan) | http://127.0.0.1:8000/ |
| Booking Servis | http://127.0.0.1:8000/booking |

> Untuk mengubah port: `php artisan serve --port=8080`
>
> Aplikasi ini juga bisa dijalankan lewat **Laragon/XAMPP** dengan mengarahkan
> document root ke folder `public/`.
