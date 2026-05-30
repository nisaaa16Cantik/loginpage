# 🔐 Sistem Login PHP

Aplikasi web berbasis PHP dengan fitur Login dan Register menggunakan MySQL.

---

## ✨ Fitur

- 📝 **Register** — daftar akun baru dengan validasi
- 🔐 **Login** — autentikasi user dengan password terenkripsi
- 🚀 **Dashboard** — halaman setelah login berhasil
- 🔒 **Proteksi halaman** — wajib login untuk akses dashboard
- 🚪 **Logout** — hapus session

---

## 🛠️ Teknologi

- PHP (Native)
- MySQL
- HTML & CSS
- XAMPP / Laragon

---

## 🗄️ Struktur Database

### Tabel `users`
| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | INT AUTO_INCREMENT | Primary Key |
| username | VARCHAR(100) | Unique |
| password | VARCHAR(255) | Di-hash bcrypt |
| created_at | TIMESTAMP | Otomatis |

> Tabel dibuat **otomatis** saat file pertama kali dijalankan.

---

## 🚀 Cara Instalasi

### 1. Clone atau Download Repository
```bash
git clone https://github.com/username/nama-repo.git
```

### 2. Pindahkan ke Folder XAMPP
```
C:/xampp/htdocs/tugas1/
```

### 3. Buat Database di phpMyAdmin
```sql
CREATE DATABASE nisa_database;
```

### 4. Sesuaikan Konfigurasi Database

Edit bagian ini di `login_system.php`:
```php
$DB_HOST = "localhost";
$DB_NAME = "nisa_database";
$DB_USER = "root";
$DB_PASS = "";
```

### 5. Jalankan XAMPP
Pastikan **Apache** dan **MySQL** sudah aktif (hijau).

### 6. Buka di Browser
```
http://localhost/tugas1/login_system.php
```

---

## 📖 Cara Penggunaan

1. **Daftar akun** → klik "Daftar sekarang" → isi username & password
2. **Login** → masukkan username & password
3. **Dashboard** → tampil setelah login berhasil
4. **Logout** → klik tombol Logout

---

## 📁 Struktur File

```
tugas1/
├── login_system.php   # Semua fitur dalam 1 file
└── README.md          # Dokumentasi proyek
```

---

## 👩‍💻 Dibuat oleh

**Nisa** — Tugas Pemrograman Web
