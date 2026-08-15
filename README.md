# Pelaporan Tiket

Aplikasi pelaporan dan pengelolaan tiket untuk mencatat masalah, menetapkan tiket kepada user tertentu, serta memantau status penyelesaian tiket.

## Tech Stack

- PHP
- Laravel
- Filament
- Filament Shield
- SQLite
- Tailwind CSS
- Vite
- Composer
- Node.js / NPM

---

## Fitur

- Membuat ticket
- Melihat daftar ticket
- Melihat detail ticket
- Mengedit ticket
- Menghapus ticket
- Menentukan priority ticket
- Menentukan status ticket
- Menentukan user yang menangani ticket
- Upload attachment
- Preview attachment
- Role dan permission menggunakan Filament Shield

---

## Requirements

Pastikan environment sudah memiliki:

- PHP
- Composer
- Node.js
- NPM
- Git

Cek instalasi:

```bash
php -v
composer -V
node -v
npm -v
git --version
```

---

# Installation

## 1. Clone Repository

```bash
git clone <REPOSITORY_URL>
```

Masuk ke folder project:

```bash
cd pelaporan-tiket
```

---

## 2. Install PHP Dependencies

```bash
composer install
```

---

## 3. Install Node Dependencies

```bash
npm install
```

---

# Environment Configuration

## 4. Buat File `.env`

Windows:

```bash
copy .env.example .env
```

Linux / macOS:

```bash
cp .env.example .env
```

---

## 5. Generate Application Key

```bash
php artisan key:generate
```

---

# Database

Project ini menggunakan SQLite.

## 6. Buat Database SQLite

Jika file database belum tersedia, buat:

```text
database/database.sqlite
```

### Windows PowerShell

```powershell
New-Item database/database.sqlite -ItemType File
```

Atau buat file `database.sqlite` secara manual di dalam folder `database`.

---

## 7. Konfigurasi Database

Pastikan `.env` menggunakan SQLite:

```env
DB_CONNECTION=sqlite
```

Pastikan database mengarah ke:

```text
database/database.sqlite
```

---

## 8. Jalankan Migration

```bash
php artisan migrate
```

Untuk melihat status migration:

```bash
php artisan migrate:status
```

---

# Storage

Attachment ticket menggunakan Laravel public storage.

## 9. Buat Storage Link

```bash
php artisan storage:link
```

File attachment disimpan di:

```text
storage/app/public/tickets
```

dan dapat diakses melalui:

```text
public/storage
```

---

# Filament

## 10. Membuat User Admin

Untuk membuat user yang dapat login ke Filament:

```bash
php artisan make:filament-user
```

Isi:

```text
Name
Email
Password
```

User tersebut dapat digunakan untuk login ke panel Filament.

---

# Filament Shield

Project ini menggunakan Filament Shield untuk mengelola role dan permission.

Jika Shield belum dikonfigurasi pada environment baru:

```bash
php artisan shield:setup
```

Model `User` harus menggunakan trait:

```php
use Spatie\Permission\Traits\HasRoles;
```

Contoh:

```php
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasRoles;

    // ...
}
```

Permission dan role kemudian dapat dikelola melalui Filament Shield.

---

# Frontend

Untuk menjalankan Vite dalam mode development:

```bash
npm run dev
```

Biarkan proses tersebut tetap berjalan selama development.

---

# Menjalankan Aplikasi

Gunakan dua terminal.

### Terminal 1

```bash
php artisan serve
```

Aplikasi akan tersedia di:

```text
http://127.0.0.1:8000
```

### Terminal 2

```bash
npm run dev
```

Panel administrasi Filament tersedia di:

```text
http://127.0.0.1:8000/admin
```

Login menggunakan user yang dibuat dengan:

```bash
php artisan make:filament-user
```

---

# Ticket

Ticket memiliki beberapa informasi utama:

- Subject
- Description
- Category
- Priority
- Status
- Creator
- Assignee
- Attachment

---

# Ticket Status

Status ticket menggunakan native PHP enum.

Status yang tersedia:

```text
open
in_progress
resolved
closed
```

Alur status:

```text
OPEN
  ↓
IN_PROGRESS
  ↓
RESOLVED
  ↓
CLOSED
```

Enum berada di:

```text
app/Enums/TicketStatus.php
```

---

# Ticket Priority

Priority ticket menggunakan native PHP enum.

Enum berada di:

```text
app/Enums/TicketPriority.php
```

---

# Ticket Relationship

Ticket memiliki dua relasi ke User.

## Creator

Relasi:

```php
creator()
```

menggunakan foreign key:

```text
tickets.user_id
```

Contoh:

```php
public function creator(): BelongsTo
{
    return $this->belongsTo(User::class, 'user_id');
}
```

## Assignee

Relasi:

```php
assignee()
```

menggunakan foreign key:

```text
tickets.assigned_to
```

Contoh:

```php
public function assignee(): BelongsTo
{
    return $this->belongsTo(User::class, 'assigned_to');
}
```

---

# User Relationship

User memiliki dua relasi ke Ticket.

## Ticket yang dibuat user

```php
createdTickets()
```

menggunakan:

```text
tickets.user_id
```

## Ticket yang ditugaskan kepada user

```php
assignedTickets()
```

menggunakan:

```text
tickets.assigned_to
```

---

# Attachment

Attachment ticket menggunakan satu kolom:

```text
attachment_path
```

Project tidak menggunakan tabel `ticket_attachments`.

Upload menggunakan Filament:

```php
FileUpload::make('attachment_path')
    ->disk('public')
    ->directory('tickets')
    ->nullable()
```

File disimpan pada:

```text
storage/app/public/tickets
```

---

# Attachment Preview

Attachment dapat ditampilkan pada halaman detail ticket.

Format yang dapat dipreview langsung oleh browser:

- PNG
- JPG
- JPEG
- WebP
- GIF
- PDF

Preview attachment menggunakan halaman View Ticket.

File yang tidak dapat ditampilkan langsung oleh browser akan menyediakan opsi untuk membuka file.

---

# Mass Assignment

Model `Ticket` menggunakan `Fillable` untuk menentukan field yang boleh diisi melalui form.

Field yang dapat diisi melalui form:

```text
subject
description
category
priority
attachment_path
```

Field tertentu tidak berasal langsung dari input user.

`user_id` creator diisi secara otomatis berdasarkan user yang sedang login:

```php
$data['user_id'] = auth()->user()->id;
```

Status awal ticket ditentukan oleh aplikasi:

```php
$data['status'] = TicketStatus::OPEN;
```

---

# Struktur Project

Struktur utama aplikasi:

```text
app/
├── Enums/
│   ├── TicketPriority.php
│   └── TicketStatus.php
│
├── Filament/
│   └── Resources/
│       └── Tickets/
│           ├── Pages/
│           │   ├── CreateTicket.php
│           │   ├── EditTicket.php
│           │   ├── ListTickets.php
│           │   └── ViewTicket.php
│           │
│           ├── Schemas/
│           │   ├── TicketForm.php
│           │   └── TicketInfolist.php
│           │
│           ├── Tables/
│           │   └── TicketsTable.php
│           │
│           └── TicketResource.php
│
└── Models/
    ├── Ticket.php
    └── User.php
```

Attachment preview:

```text
resources/
└── views/
    └── filament/
        └── tickets/
            └── previewdetail.blade.php
```

---

# Git Ignore

File dan folder yang bersifat environment/local tidak disimpan di repository.

Contoh:

```gitignore
.env
/vendor/
/node_modules/
*.sqlite*
```

Database SQLite lokal tidak disimpan ke repository.

Setelah clone repository, developer harus membuat database SQLite sendiri dan menjalankan:

```bash
php artisan migrate
```

---

# Troubleshooting

## Migration gagal karena database belum ada

Pastikan file berikut tersedia:

```text
database/database.sqlite
```

Kemudian:

```bash
php artisan migrate
```

---

## Attachment tidak dapat dibuka

Pastikan storage link sudah dibuat:

```bash
php artisan storage:link
```

Pastikan file berada di:

```text
storage/app/public/tickets
```

---

## Vite tidak ditemukan

Jika muncul error seperti:

```text
'vite' is not recognized
```

jalankan:

```bash
npm install
```

Kemudian:

```bash
npm run dev
```

---

## Permission Shield bermasalah

Pastikan `User` menggunakan:

```php
use Spatie\Permission\Traits\HasRoles;
```

dan:

```php
use HasRoles;
```

Kemudian jalankan:

```bash
php artisan shield:setup
```

---

# Useful Artisan Commands

## Cek Migration

```bash
php artisan migrate:status
```

## Jalankan Migration

```bash
php artisan migrate
```

## Reset Database

> Gunakan hanya pada environment development karena akan menghapus seluruh data database.

```bash
php artisan migrate:fresh
```

## Membuat Filament User

```bash
php artisan make:filament-user
```

## Membuat Storage Link

```bash
php artisan storage:link
```

## Clear Cache

```bash
php artisan optimize:clear
```

## Menjalankan Laravel

```bash
php artisan serve
```

## Menjalankan Vite

```bash
npm run dev
```

---