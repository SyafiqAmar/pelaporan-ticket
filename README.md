# Pelaporan Tiket

Aplikasi pelaporan dan pengelolaan tiket internal. Karyawan melaporkan masalah melalui tiket, Admin IT menerima laporan tersebut, menugaskannya kepada penanggung jawab, dan memantau status penyelesaiannya.

Dokumen ini mencakup seluruh alur dari clone repository sampai pengujian fitur, sehingga aplikasi dapat dijalankan dan diverifikasi tanpa penjelasan tambahan.

---

## Tech Stack

| Komponen | Versi / Keterangan |
| --- | --- |
| PHP | ^8.3 |
| Laravel | ^13.17 |
| Filament | ^5.7 |
| Spatie Laravel Permission | disertakan melalui Filament Shield ^4.3 |
| Database | SQLite |
| Frontend | Vite + Tailwind CSS |

---

## Model Hak Akses

Aplikasi menggunakan dua role: `admin` dan `user`.

| Kemampuan | admin | user |
| --- | --- | --- |
| Masuk panel `/admin` | Ya | Ya |
| Membuat tiket | Ya | Ya |
| Melihat tiket milik sendiri | Ya | Ya |
| Melihat tiket milik pengguna lain | Ya | Tidak |
| Mengedit tiket | Ya | Tidak |
| Menghapus tiket | Ya | Tidak |
| Menugaskan tiket kepada penanggung jawab | Ya | Tidak |
| Mengubah status tiket | Ya | Tidak |

Pengguna yang tidak memiliki role sama sekali ditolak masuk panel. Perilaku ini disengaja; penanganannya dijelaskan pada bagian Troubleshooting.

### Tiga Lapisan Penegakan

| Lapisan | Lokasi | Tanggung jawab |
| --- | --- | --- |
| Gerbang panel | `app/Models/User.php`, method `canAccessPanel()` | Menolak pengguna tanpa role sebelum panel dirender |
| Otorisasi aksi | `app/Policies/TicketPolicy.php` | Menentukan izin `view`, `create`, `update`, dan `delete` |
| Pembatas data | `app/Filament/Resources/Tickets/TicketResource.php`, method `getEloquentQuery()` | Menyaring baris agar role `user` hanya melihat tiket miliknya |

Policy menjawab pertanyaan "boleh atau tidak", sedangkan scoping query menjawab "terlihat atau tidak". Keduanya wajib ada. Policy saja tidak menyembunyikan baris dari tabel daftar, dan scoping saja tidak menolak akses melalui URL langsung.

---

## Prasyarat

Pastikan environment memenuhi kebutuhan berikut:

```bash
php -v          # minimal 8.3
composer -V
node -v
npm -v
git --version
```

---

## Instalasi

### 1. Clone repository

```bash
git clone <REPOSITORY_URL>
cd pelaporan-tiket
```

### 2. Install dependency

```bash
composer install
npm install
```

### 3. Siapkan file environment

Windows:

```bash
copy .env.example .env
```

Linux atau macOS:

```bash
cp .env.example .env
```

### 4. Generate application key

```bash
php artisan key:generate
```

### 5. Buat file database SQLite

Windows PowerShell:

```powershell
New-Item database/database.sqlite -ItemType File
```

Linux atau macOS:

```bash
touch database/database.sqlite
```

Pastikan `.env` memuat konfigurasi berikut:

```env
DB_CONNECTION=sqlite
```

### 6. Jalankan migration

```bash
php artisan migrate
```

### 7. Seed role dan akun demo

```bash
php artisan db:seed --class=RoleSeeder
```

Langkah ini membuat role `admin` dan `user` beserta dua akun untuk pengujian. Tanpa langkah ini tidak ada akun yang dapat masuk ke panel.

### 8. Buat storage link

```bash
php artisan storage:link
```

Attachment disimpan di `storage/app/public/tickets` dan diakses melalui `public/storage`.

### 9. Jalankan aplikasi

Gunakan dua terminal terpisah:

```bash
# Terminal 1
php artisan serve
```

```bash
# Terminal 2
npm run dev
```

Panel tersedia di `http://127.0.0.1:8000/admin`.

---

## Akun Demo

| Role | Email | Password |
| --- | --- | --- |
| admin | `admin@mail.com` | `password` |
| user | `staff@mail.com` | `password` |

Akun dibuat oleh `database/seeders/RoleSeeder.php`. Kredensial ini hanya untuk lingkungan development dan harus diganti sebelum digunakan di lingkungan lain.

---

## Alur Pengujian Fitur

Jalankan tahapan berikut secara berurutan. Setiap tahap memverifikasi satu aturan hak akses.

### A. Sebagai role user (pelapor)

1. Login menggunakan `staff@mail.com` dengan password `password` pada `/admin`.
2. Buka menu Tickets. Daftar tiket masih kosong.
3. Klik New ticket, isi Subject, Description, Category, dan Priority, lalu unggah satu berkas pada field Attachment.

   Hasil yang diharapkan: field Status dan Assigned To tidak muncul pada form. Field `status` diisi otomatis dengan nilai `open` oleh aplikasi, sedangkan `assigned_to` merupakan wewenang admin.

4. Simpan tiket. Tiket muncul di daftar dengan status `open`.

   Hasil yang diharapkan: kolom Created By berisi nama pengguna yang sedang login.

5. Buka detail tiket melalui aksi View untuk memeriksa preview attachment.

   Hasil yang diharapkan: tombol Edit dan Delete tidak tersedia.

6. Catat ID tiket ini, misalnya `1`, untuk digunakan pada tahap berikutnya.

### B. Sebagai role admin (penanganan)

1. Logout, kemudian login menggunakan `admin@mail.com` dengan password `password`.
2. Buka menu Tickets.

   Hasil yang diharapkan: tiket yang dibuat oleh akun staff terlihat pada daftar.

3. Klik Edit pada tiket tersebut.

   Hasil yang diharapkan: field Assigned To dan Status tersedia pada form.

4. Tugaskan tiket kepada salah satu pengguna, ubah status menjadi `in_progress`, lalu simpan.
5. Buat satu tiket baru dari akun admin dan catat ID-nya, misalnya `2`.

### C. Pengujian kebocoran akses

Tahap ini merupakan bagian terpenting dari pengujian.

1. Logout, kemudian login kembali menggunakan `staff@mail.com`.
2. Buka menu Tickets.

   Hasil yang diharapkan: tiket dengan ID `2` milik admin tidak muncul pada daftar.

3. Akses tiket tersebut secara langsung melalui `http://127.0.0.1:8000/admin/tickets/2`.

   Hasil yang diharapkan: respons 403 Forbidden.

4. Akses halaman edit melalui `http://127.0.0.1:8000/admin/tickets/2/edit`.

   Hasil yang diharapkan: respons 403 Forbidden.

Langkah 2 memverifikasi scoping query, sedangkan langkah 3 dan 4 memverifikasi policy. Kegagalan pada salah satunya menandakan adanya kebocoran akses.

### D. Pengujian gerbang panel

1. Buat pengguna tanpa role melalui Tinker:

   ```bash
   php artisan tinker
   ```

   ```php
   \App\Models\User::create([
       'name' => 'Tanpa Role',
       'email' => 'norole@mail.com',
       'password' => 'password',
   ]);
   ```

2. Logout, kemudian coba login menggunakan `norole@mail.com`.

   Hasil yang diharapkan: login ditolak dan pengguna tidak dapat mengakses panel.

### E. Verifikasi data pada database

```bash
php artisan tinker
```

```php
\App\Models\Ticket::latest()->first()->only(['user_id', 'status']);
```

Hasil yang diharapkan: `user_id` berisi ID pembuat tiket dan `status` bernilai `open` untuk tiket yang baru dibuat.

Apabila `user_id` bernilai `null`, method `handleRecordCreation()` pada `CreateTicket.php` tidak terpanggil sebagaimana mestinya.

---

## Struktur Data

### Tabel tickets

| Kolom | Tipe | Keterangan |
| --- | --- | --- |
| `id` | bigint | Primary key |
| `user_id` | foreign key ke `users.id` | Pembuat tiket, diisi oleh server |
| `assigned_to` | foreign key ke `users.id`, nullable | Penanggung jawab, ditentukan oleh admin |
| `subject` | string | Judul laporan |
| `description` | text | Uraian masalah |
| `category` | string | Kategori laporan |
| `priority` | string | Dipetakan ke enum `TicketPriority` |
| `status` | string, default `open` | Dipetakan ke enum `TicketStatus` |
| `attachment_path` | string, nullable | Satu berkas per tiket |
| `created_at`, `updated_at` | timestamp | Dikelola Eloquent |

### Enum

`app/Enums/TicketStatus.php` mendefinisikan alur status berikut:

```text
open -> in_progress -> resolved -> closed
```

`app/Enums/TicketPriority.php` mendefinisikan tingkat prioritas `low`, `medium`, dan `high`.

Kedua enum dideklarasikan pada `Ticket::casts()`, sehingga `$ticket->status` mengembalikan instance enum dan `$ticket->status->value` mengembalikan representasi string-nya.

### Relasi

| Model | Method | Foreign key |
| --- | --- | --- |
| `Ticket` | `creator()`, belongsTo User | `tickets.user_id` |
| `Ticket` | `assignee()`, belongsTo User | `tickets.assigned_to` |
| `User` | `createdTickets()`, hasMany Ticket | `tickets.user_id` |
| `User` | `assignedTickets()`, hasMany Ticket | `tickets.assigned_to` |

Nama method sengaja dibedakan karena kedua foreign key menunjuk ke tabel yang sama.

---

## Mass Assignment

Model `Ticket` menggunakan atribut `#[Fillable]` untuk membatasi field yang boleh diisi melalui form:

```php
#[Fillable([
    'subject',
    'description',
    'category',
    'priority',
    'attachment_path',
    'status',
    'assigned_to',
])]
```

Field `user_id` sengaja tidak disertakan. Apabila pengguna dapat mengirim `user_id` melalui form, pengguna tersebut berpotensi membuat tiket atas nama orang lain. Field ini diisi melalui assignment properti langsung pada `CreateTicket::handleRecordCreation()`:

```php
protected function handleRecordCreation(array $data): Model
{
    $ticket = new Ticket($data);        // hanya field fillable yang masuk
    $ticket->user_id = Auth::id();      // ditentukan oleh server
    $ticket->status  = TicketStatus::OPEN;
    $ticket->save();

    return $ticket;
}
```

Field `status` dan `assigned_to` tetap fillable karena telah dijaga dua lapis: form menyembunyikannya dari non-admin melalui `->visible()`, dan `TicketPolicy::update()` hanya mengizinkan admin melakukan pembaruan.

`#[Fillable]` bukan lapisan keamanan tunggal. Atribut ini hanya mencegah mass assignment dan tidak menentukan siapa yang berhak melakukan aksi tertentu. Penentuan hak tersebut merupakan tanggung jawab Policy.

---

## Attachment

Saat ini setiap tiket menyimpan satu berkas pada kolom `attachment_path`:

```php
FileUpload::make('attachment_path')
    ->disk('public')
    ->directory('tickets')
    ->nullable()
```

Berkas disimpan pada `storage/app/public/tickets`.

Preview ditampilkan pada halaman View Ticket melalui `resources/views/filament/tickets/previewdetail.blade.php`. Format yang dapat ditampilkan langsung oleh browser mencakup PNG, JPG, JPEG, WebP, GIF, dan PDF. Format lain disajikan sebagai tautan untuk membuka berkas.

---

## Struktur Project

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
│           │   ├── CreateTicket.php      handleRecordCreation: user_id dan status
│           │   ├── EditTicket.php
│           │   ├── ListTickets.php
│           │   └── ViewTicket.php
│           ├── Schemas/
│           │   ├── TicketForm.php        visibilitas field berdasarkan role
│           │   └── TicketInfolist.php
│           ├── Tables/
│           │   └── TicketsTable.php
│           └── TicketResource.php        getEloquentQuery: scoping data
│
├── Models/
│   ├── Ticket.php
│   └── User.php                          canAccessPanel dan trait HasRoles
│
└── Policies/
    └── TicketPolicy.php                  otorisasi view, create, update, delete

database/
├── migrations/
└── seeders/
    ├── DatabaseSeeder.php
    └── RoleSeeder.php                    role admin dan user beserta akun demo

resources/views/filament/tickets/
└── previewdetail.blade.php
```

---

## Troubleshooting

### Tidak dapat login meskipun akun tersedia

Akun yang dibuat melalui `php artisan make:filament-user` tidak memiliki role, sehingga ditolak oleh `canAccessPanel()`. Berikan role melalui Tinker:

```php
\App\Models\User::where('email', 'alamat@mail.com')->first()->assignRole('admin');
```

Sebagai alternatif, gunakan akun demo yang dihasilkan oleh `RoleSeeder`.

### Perubahan role tidak berpengaruh

Spatie Laravel Permission menyimpan role dan permission dalam cache:

```bash
php artisan permission:cache-reset
php artisan optimize:clear
```

### Seeder gagal dengan pesan "no such column: user"

Pastikan `RoleSeeder` menggunakan key `name`, bukan `user`:

```php
Role::firstOrCreate(['name' => 'user']);
```

### Migration gagal dijalankan

Pastikan berkas `database/database.sqlite` sudah tersedia, kemudian jalankan kembali `php artisan migrate`.

### Attachment tidak dapat dibuka

Pastikan storage link sudah dibuat:

```bash
php artisan storage:link
```

Pastikan pula berkas benar-benar tersimpan di `storage/app/public/tickets`.

### Perintah vite tidak dikenali

```bash
npm install
npm run dev
```

---

## Perintah Artisan yang Sering Digunakan

```bash
php artisan migrate:status                    # memeriksa status migration
php artisan migrate                           # menjalankan migration
php artisan migrate:fresh                     # mereset database, hanya untuk development
php artisan db:seed --class=RoleSeeder        # membuat role dan akun demo
php artisan permission:cache-reset            # membersihkan cache role dan permission
php artisan storage:link                      # membuat symlink storage
php artisan optimize:clear                    # membersihkan seluruh cache aplikasi
php artisan serve                             # menjalankan server pengembangan
npm run dev                                   # menjalankan Vite
```

Perintah `migrate:fresh` menghapus seluruh tabel beserta datanya. Setelah menjalankannya, ulangi `php artisan db:seed --class=RoleSeeder`.

---

## Catatan Pengembangan

Filament Shield telah terpasang dan konfigurasinya sudah dipublikasikan pada `config/filament-shield.php`, tetapi plugin-nya belum didaftarkan pada `AdminPanelProvider`. Role dan permission saat ini dikelola melalui `RoleSeeder` dan `TicketPolicy`, bukan melalui antarmuka. Shield akan diaktifkan apabila dibutuhkan halaman pengaturan permission bagi admin.

Rencana pengembangan berikutnya:

- Dukungan attachment multi-berkas melalui tabel terpisah `ticket_attachments`. Implementasi saat ini terbatas pada satu kolom dan satu berkas per tiket.
- Filter dan pencarian tiket berdasarkan status dan priority.
- Notifikasi ketika tiket ditugaskan atau statusnya berubah.
- Automated test untuk memverifikasi aturan hak akses.
