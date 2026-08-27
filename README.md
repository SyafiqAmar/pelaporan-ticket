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
| Laravel Sanctum | Autentikasi token untuk REST API |
| Database | SQLite |
| Frontend | Vite + Tailwind CSS |

---

## Model Hak Akses

Aplikasi menggunakan tiga role: `admin`, `staff_it`, dan `user`.

| Kemampuan | admin | staff_it | user |
| --- | --- | --- | --- |
| Masuk panel `/admin` | Ya | Ya | Ya |
| Registrasi akun mandiri | - | - | Ya, lewat `/admin/register` |
| Membuat tiket | Ya | Tidak | Ya |
| Melihat tiket | Semua | Hanya yang di-assign ke dirinya | Hanya miliknya sendiri |
| Mengubah status tiket | Ya | Ya, hanya tiket yang di-assign ke dirinya | Tidak |
| Mengedit field lain (subject, description, category, priority, attachment) | Ya | Tidak, field terkunci di halaman Edit | Tidak |
| Menugaskan tiket ke staff | Ya | Tidak | Tidak |
| Menghapus tiket | Ya | Tidak | Tidak |
| Mengubah role user lain | Ya, lewat menu Users | Tidak | Tidak |

Pengguna yang tidak memiliki role sama sekali ditolak masuk panel. Perilaku ini disengaja; penanganannya dijelaskan pada bagian Troubleshooting.

Registrasi mandiri (`/admin/register`) otomatis memberi role `user` ke akun baru (`app/Filament/Auth/Register.php`, method `handleRegistration()`). Perubahan role setelahnya hanya bisa dilakukan admin lewat menu **Users** di panel, bukan oleh pengguna itu sendiri.

Akses ke menu Users diatur `app/Policies/UserPolicy.php`, permission-based sama seperti `TicketPolicy` (`ViewAny:User`, `View:User`, `Update:User`). Secara default hanya `admin` yang punya akses, lewat bypass Gate yang sama. Berbeda dari resource lain, permission untuk model `User` **belum** digenerate lewat `shield:generate` — baris permission-nya baru dibuat otomatis saat pertama kali dicentang dan disimpan di halaman `/admin/shield/roles`. Kalau suatu saat admin mencentang box "View"/"View Any" pada card User untuk role lain (misal `staff_it`) di halaman itu, role tersebut **akan** ikut mendapat akses ke menu Users — tabel Model Hak Akses di atas mendeskripsikan kondisi default hasil seeder, bukan batas yang tidak bisa diubah.

### Tiga Lapisan Penegakan

| Lapisan | Lokasi | Tanggung jawab |
| --- | --- | --- |
| Gerbang panel | `app/Models/User.php`, method `canAccessPanel()` | Menolak pengguna tanpa role sebelum panel dirender |
| Otorisasi aksi | `app/Policies/TicketPolicy.php` | Menentukan izin `view`, `create`, `update`, dan `delete` |
| Pembatas data | `app/Filament/Resources/Tickets/TicketResource.php`, method `getEloquentQuery()` | Menyaring baris: `admin` melihat semua, `staff_it` hanya tiket yang di-assign ke dirinya, `user` hanya tiket miliknya sendiri |

Policy menjawab pertanyaan "boleh atau tidak", sedangkan scoping query menjawab "terlihat atau tidak". Keduanya wajib ada. Policy saja tidak menyembunyikan baris dari tabel daftar, dan scoping saja tidak menolak akses melalui URL langsung.

Otorisasi aksi diimplementasikan menggunakan permission granular dari Filament Shield, dengan format `Aksi:Model` (contoh: `View:Ticket`, `Update:Ticket`). Role `admin` dikonfigurasi sebagai super admin (`config/filament-shield.php`, `super_admin.define_via_gate = true`) sehingga lolos semua pemeriksaan permission tanpa perlu di-assign satu per satu. Role `staff_it` dan `user` diberi permission terbatas melalui `RoleSeeder`. Permission dan policy dapat digenerate ulang lewat `php artisan shield:generate --resource=TicketResource --panel=admin`; setelah generate ulang, cek kepemilikan tiket pada method `view`, `update`, dan `delete` di `TicketPolicy` harus ditambahkan kembali secara manual, karena Shield tidak mengenal konsep kepemilikan data.

Cek kepemilikan pada `TicketPolicy` membandingkan `$ticket->user_id` (pembuat) **atau** `$ticket->assigned_to` (penanggung jawab) dengan ID pengguna yang login — bukan cuma `user_id` saja. Ini yang memungkinkan `staff_it` (bukan pembuat tiket) tetap bisa membuka tiket yang ditugaskan kepadanya.

Untuk `staff_it`, pembatasan "hanya boleh ubah status" tidak cukup ditegakkan lewat Policy saja, karena Policy `update()` adalah izin untuk satu halaman Edit secara keseluruhan, bukan per-field. Field selain `status` (subject, description, category, priority, attachment) dikunci lewat `->disabled()` di `TicketForm.php`, aktif hanya saat `$operation === 'edit'` dan role bukan `admin`. Field yang `disabled()` di Filament tidak ikut ter-submit saat form disimpan, sehingga ini benar-benar ditegakkan di server, bukan sekadar disembunyikan di tampilan.

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

### 7. Pasang dan generate Filament Shield

```bash
php artisan shield:install admin
php artisan shield:generate --resource=TicketResource --panel=admin
```

Perintah pertama mendaftarkan plugin Shield pada panel `admin`. Perintah kedua membuat baris permission untuk resource Ticket di database. Langkah ini wajib dijalankan sebelum seeding role, karena `RoleSeeder` meng-assign permission yang dihasilkan di sini.

### 8. Seed role dan akun demo

```bash
php artisan db:seed --class=RoleSeeder
```

Langkah ini membuat role `admin`, `staff_it`, dan `user`, meng-assign permission ke role `staff_it` dan `user`, serta membuat tiga akun untuk pengujian. Tanpa langkah ini tidak ada akun yang dapat masuk ke panel.

### 9. Pasang Sanctum (untuk REST API)

```bash
php artisan install:api
```

Perintah ini membuat `routes/api.php`, migration tabel `personal_access_tokens`, dan menambahkan trait `HasApiTokens` ke `User.php`. Migration ikut dijalankan otomatis lewat prompt yang muncul (pilih Yes), atau jalankan manual:

```bash
php artisan migrate
```

### 10. Buat storage link

```bash
php artisan storage:link
```

Attachment disimpan di `storage/app/public/tickets` dan diakses melalui `public/storage`.

### 11. Jalankan aplikasi

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
| staff_it | `staffit@mail.com` | `password` |
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

   Hasil yang diharapkan: respons 404 Not Found.

4. Akses halaman edit melalui `http://127.0.0.1:8000/admin/tickets/2/edit`.

   Hasil yang diharapkan: respons 404 Not Found.

Langkah 2 memverifikasi scoping query pada `TicketResource::getEloquentQuery()`. Langkah 3 dan 4 menghasilkan 404, bukan 403, karena Filament mencari record melalui query yang sudah di-scope tersebut sebelum sempat memeriksa `TicketPolicy`; tiket milik pengguna lain sudah tidak ditemukan lebih dulu. Kegagalan pada salah satu langkah tetap menandakan adanya kebocoran akses.

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

### F. Sebagai role staff_it (penanganan terbatas)

1. Login sebagai `admin@mail.com`, tugaskan salah satu tiket ke `staffit@mail.com` lewat field Assigned To, simpan.
2. Logout, login menggunakan `staffit@mail.com` dengan password `password`.
3. Buka menu Tickets.

   Hasil yang diharapkan: hanya tiket yang baru saja di-assign ke akun ini yang muncul. Tiket lain, termasuk yang dibuat oleh akun ini sendiri jika ada, tidak terlihat kecuali ikut di-assign.

4. Buka tiket tersebut lewat Edit.

   Hasil yang diharapkan: field Assigned To tidak muncul sama sekali. Field Subject, Description, Category, Priority, dan Attachment terlihat tetapi tidak bisa diklik/diubah (terkunci). Hanya field Status yang bisa diubah.

5. Ubah Status, simpan, lalu refresh halaman untuk memastikan perubahan tersimpan.
6. Coba akses tiket yang **tidak** di-assign ke akun ini lewat URL langsung.

   Hasil yang diharapkan: respons 404 Not Found, sama seperti pengujian kebocoran akses pada Bagian C.

### G. Registrasi mandiri dan pengelolaan role lewat UI

1. Logout, buka `http://127.0.0.1:8000/admin/register`, daftar dengan email baru.
2. Setelah submit, aplikasi otomatis login dan masuk ke dashboard.

   Hasil yang diharapkan: akun baru hanya bisa melakukan hal-hal yang boleh dilakukan role `user` (lihat Bagian A). Verifikasi lewat Tinker:

   ```php
   \App\Models\User::latest()->first()->roles->pluck('name');
   ```

   Hasil yang diharapkan: `['user']`.

3. Logout, login sebagai `admin@mail.com`. Buka menu **Users** di sidebar.

   Hasil yang diharapkan: menu ini hanya terlihat untuk admin — coba login sebagai `staff@mail.com` atau `staffit@mail.com` untuk memastikan menu Users tidak muncul pada akun mereka.

4. Edit akun yang baru saja mendaftar, ubah Role menjadi **Admin IT**, simpan.
5. Logout, login kembali menggunakan akun tersebut.

   Hasil yang diharapkan: akun sekarang berperilaku seperti admin (bisa melihat semua tiket). Ini membuktikan role benar-benar **diganti** (bukan ditambah) oleh `EditUser::handleRecordUpdate()`, yang memanggil `syncRoles()`.

### H. Mengelola dan melihat FAQ

1. Login sebagai `admin@mail.com`. Buka menu **Faqs**, klik **Tambah Faq**.
2. Isi Question, lalu tulis Answer memakai toolbar Rich Editor (bold, heading, bullet list, dst), simpan.

   Hasil yang diharapkan: setelah simpan, otomatis kembali ke daftar Faqs (bukan ke halaman Edit).

3. Logout (tidak perlu login sama sekali), buka `http://127.0.0.1:8000/faq`.

   Hasil yang diharapkan: FAQ yang baru dibuat muncul, bisa diklik untuk expand, dan format dari Rich Editor (bold, list, dst) tampil benar, bukan tercetak sebagai tag HTML mentah.

4. Login sebagai `staff@mail.com` atau `staffit@mail.com`.

   Hasil yang diharapkan: menu **Faqs** tidak muncul di sidebar — akses menu ini default hanya untuk admin.

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

## FAQ

Halaman FAQ publik menampilkan daftar pertanyaan-jawaban yang dikelola admin lewat panel, dapat dilihat siapa saja tanpa login.

### Tabel faqs

| Kolom | Tipe | Keterangan |
| --- | --- | --- |
| `id` | bigint | Primary key |
| `question` | string | Pertanyaan |
| `answer` | longText | Jawaban, berisi HTML hasil Rich Editor |
| `created_at`, `updated_at` | timestamp | Dikelola Eloquent |

### Pengelolaan (khusus admin)

Menu **Faqs** di panel (`/admin/faqs`) memakai `RichEditor` Filament pada field `answer`, sehingga admin bisa memformat jawaban (bold, heading, list, tabel, dst) tanpa menulis HTML manual. Hasilnya disimpan sebagai HTML mentah pada kolom `answer`, bukan JSON — `RichEditor` bekerja seperti ini secara default, kecuali dipanggil dengan `->json()`.

Akses menu ini diatur `app/Policies/FaqPolicy.php`, permission-based mengikuti pola `TicketPolicy`/`UserPolicy` (`ViewAny:Faq`, `Create:Faq`, `Update:Faq`, `Delete:Faq`). Secara default hanya `admin` yang punya akses lewat bypass Gate; permission untuk role lain baru dibuat otomatis saat dicentang di `/admin/shield/roles`, sama seperti `UserPolicy`.

### Tampilan publik

```
http://127.0.0.1:8000/faq
```

Route ini didefinisikan langsung di `routes/web.php`, di luar panel Filament — tidak melewati middleware auth panel, sehingga bisa diakses tanpa login. View-nya (`resources/views/faq.blade.php`) menampilkan tiap FAQ sebagai elemen `<details>`/`<summary>` native HTML (accordion collapsible tanpa JavaScript), dan me-render `answer` dengan `{!! !!}` — bukan `{{ }}` — supaya tag HTML dari Rich Editor tampil terformat, bukan tercetak sebagai teks mentah.

Merender HTML tanpa sanitasi di sini aman karena kontennya **cuma bisa ditulis lewat panel admin**, yang sudah dibatasi `FaqPolicy`. Kalau suatu saat role lain diberi akses tulis FAQ lewat UI Shield, pertimbangkan menambahkan pembersih HTML (misalnya `mews/purifier`) sebelum data disimpan.

---

## REST API

Tickets dan FAQ juga tersedia lewat REST API (`/api/...`), di luar panel Filament, memakai autentikasi token (Laravel Sanctum). Otorisasinya **reuse** `TicketPolicy`/`FaqPolicy` yang sama dengan panel admin — bukan aturan akses baru yang terpisah.

### Autentikasi

```
POST /api/login
```

Body:

```json
{ "email": "admin@mail.com", "password": "password" }
```

Respons berisi `token` — dikirim di header tiap request terproteksi:

```
Authorization: Bearer <token>
```

```
POST /api/logout
```

Menghapus token yang sedang dipakai (butuh header `Authorization` di atas).

### Endpoint Tickets (semua wajib token)

| Method | URL | Keterangan |
| --- | --- | --- |
| GET | `/api/tickets` | Daftar tiket, di-scope sama seperti panel: admin semua, `staff_it` hanya yang di-assign, `user` hanya miliknya |
| POST | `/api/tickets` | Buat tiket baru (`subject`, `description`, `category`, `priority`) |
| GET | `/api/tickets/{id}` | Detail satu tiket |
| PUT | `/api/tickets/{id}` | Update tiket. Non-admin hanya boleh mengirim `status` — field lain (`subject`, `description`, `category`, `priority`, `assigned_to`) ditolak `422` (`prohibited`) kalau ikut dikirim |
| DELETE | `/api/tickets/{id}` | Hapus tiket (admin saja) |

Berbeda dari panel Filament (yang membalas **404** untuk tiket di luar jangkauan, karena query sudah di-scope duluan sebelum Policy sempat jalan), API membalas **403 Forbidden** untuk tiket yang ada tapi bukan hak akses pengguna — ini perilaku standar REST API, bukan bug.

### Endpoint FAQ

| Method | URL | Auth | Keterangan |
| --- | --- | --- | --- |
| GET | `/api/faqs` | Tidak perlu | Daftar FAQ, publik |
| GET | `/api/faqs/{id}` | Tidak perlu | Detail satu FAQ, publik |
| POST | `/api/faqs` | Token, admin | Buat FAQ baru (`question`, `answer`) |
| PUT | `/api/faqs/{id}` | Token, admin | Update FAQ |
| DELETE | `/api/faqs/{id}` | Token, admin | Hapus FAQ |

### Berkas terkait

| Berkas | Fungsi |
| --- | --- |
| `routes/api.php` | Definisi seluruh route API |
| `app/Http/Controllers/Api/AuthController.php` | Login (issue token) dan logout |
| `app/Http/Controllers/Api/TicketController.php` | CRUD tiket |
| `app/Http/Controllers/Api/FaqController.php` | CRUD FAQ |
| `app/Http/Resources/TicketResource.php`, `FaqResource.php` | Bentuk JSON response |
| `app/Models/Ticket.php`, method `scopeVisibleTo()` | Aturan scoping data, dipakai bareng oleh panel dan API |

### Testing

Koleksi Postman siap pakai: `pelaporan-tiket-api.postman_collection.json` (tinggal Import). Request **Login** otomatis menyimpan token ke variable collection setelah dijalankan, jadi request lain langsung terautentikasi.

Automated test:

```bash
php artisan test --filter=Api
```

| File | Yang diverifikasi |
| --- | --- |
| `tests/Feature/Api/AuthApiTest.php` | Login sukses/gagal, logout |
| `tests/Feature/Api/TicketApiTest.php` | Scoping per role, validasi `prohibited` untuk non-admin, akses lintas pengguna (403) |
| `tests/Feature/Api/FaqApiTest.php` | Index/show publik, create/update/delete admin only |

---

## Struktur Project

```text
app/
├── Enums/
│   ├── TicketPriority.php
│   └── TicketStatus.php
│
├── Filament/
│   ├── Auth/
│   │   └── Register.php                  handleRegistration: auto-assign role user
│   │
│   └── Resources/
│       ├── Tickets/
│       │   ├── Pages/
│       │   │   ├── CreateTicket.php      handleRecordCreation: user_id dan status
│       │   │   ├── EditTicket.php
│       │   │   ├── ListTickets.php
│       │   │   └── ViewTicket.php
│       │   ├── Schemas/
│       │   │   ├── TicketForm.php        visibilitas + disabled field per role
│       │   │   └── TicketInfolist.php
│       │   ├── Tables/
│       │   │   └── TicketsTable.php
│       │   └── TicketResource.php        getEloquentQuery: scoping 3 role
│       │
│       ├── Users/
│       │   ├── Pages/
│       │   │   ├── EditUser.php          handleRecordUpdate: syncRoles
│       │   │   └── ListUsers.php
│       │   └── UserResource.php          khusus admin, ganti role user lain
│       │
│       └── Faqs/
│           ├── Pages/
│           │   ├── CreateFaq.php         redirect ke index setelah simpan
│           │   ├── EditFaq.php
│           │   └── ListFaqs.php
│           └── FaqResource.php           form RichEditor pada field answer
│
├── Http/
│   ├── Controllers/Api/
│   │   ├── AuthController.php            login (issue token) dan logout
│   │   ├── TicketController.php          CRUD tiket, reuse TicketPolicy
│   │   └── FaqController.php             CRUD faq, index/show publik
│   └── Resources/
│       ├── TicketResource.php            bentuk JSON response tiket
│       └── FaqResource.php               bentuk JSON response faq
│
├── Models/
│   ├── Faq.php                           HasFactory
│   ├── Ticket.php                        HasFactory, casts, relasi, scopeVisibleTo
│   └── User.php                          canAccessPanel dan trait HasRoles, HasApiTokens
│
└── Policies/
    ├── FaqPolicy.php                     permission-based, default hanya admin
    ├── TicketPolicy.php                  permission Shield + cek kepemilikan tiket
    ├── UserPolicy.php                    permission-based, default hanya admin
    └── RolePolicy.php                    hasil generate shield:install

database/
├── factories/
│   ├── FaqFactory.php
│   └── TicketFactory.php
├── migrations/
└── seeders/
    ├── DatabaseSeeder.php
    └── RoleSeeder.php                    role admin, staff_it, user, assign permission, akun demo

resources/views/
├── faq.blade.php                         halaman publik /faq
└── filament/tickets/
    └── previewdetail.blade.php

routes/
├── web.php                                route publik /faq (di luar panel Filament)
└── api.php                                seluruh route REST API

tests/
├── Feature/
│   ├── TicketAuthorizationTest.php       panel access, scoping, dan policy 3 role
│   └── Api/
│       ├── AuthApiTest.php               login, logout
│       ├── TicketApiTest.php             scoping, prohibited fields, akses lintas user
│       └── FaqApiTest.php                index/show publik, CRUD admin only
└── Unit/
    └── TicketMassAssignmentTest.php      proteksi mass assignment user_id
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

### Login gagal dengan pesan "These credentials do not match our records" padahal password benar

Filament memverifikasi password dan `canAccessPanel()` dalam satu langkah (`attemptWhen()`), dan sengaja menampilkan pesan generik yang sama untuk kedua jenis kegagalan itu — supaya email yang valid tidak bisa ditebak dari luar. Kalau password sudah pasti benar, penyebabnya biasanya nama role tidak cocok persis antara yang disimpan di database dan yang dicek di `canAccessPanel()` (`app/Models/User.php`). Nama role di Spatie Permission adalah string biasa dan **case-sensitive** — `staff_IT` tidak sama dengan `staff_it`. Cek dengan Tinker:

```php
\App\Models\User::where('email', 'alamat@mail.com')->first()->roles->pluck('name');
```

Cocokkan hasilnya persis huruf besar/kecilnya dengan daftar role pada `canAccessPanel()`.

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

## Testing Otomatis

Aturan hak akses pada bagian Alur Pengujian Fitur (A-F) dituangkan sebagai automated test, sehingga dapat diverifikasi ulang dalam hitungan detik tanpa mengulang langkah manual di browser. Fitur FAQ (Bagian H) belum memiliki automated test.

```bash
php artisan test
```

| File | Yang diverifikasi |
| --- | --- |
| `tests/Feature/TicketAuthorizationTest.php` | Gerbang panel, scoping daftar tiket untuk ketiga role, akses view/edit tiket lintas pengguna, scoping berdasarkan `assigned_to` untuk `staff_it` |
| `tests/Unit/TicketMassAssignmentTest.php` | `user_id` tidak dapat diisi melalui mass assignment |

Alur registrasi (Bagian G) dan halaman Users belum memiliki automated test — masih diverifikasi manual.

`TicketAuthorizationTest` menggunakan `RefreshDatabase` sehingga berjalan pada database SQLite in-memory terpisah dan tidak menyentuh `database/database.sqlite`.

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

## Notes Pengembangan

Filament Shield aktif penuh: plugin terdaftar pada `AdminPanelProvider`, dan role/permission dapat dikelola melalui antarmuka pada `/admin/shield/roles` selain lewat `RoleSeeder`. Perlu diperhatikan: apabila permission suatu role diubah melalui antarmuka tersebut, tabel Model Hak Akses pada dokumen ini menjadi deskripsi kondisi awal hasil seeder, bukan lagi jaminan kondisi saat ini.

Attachment tetap menggunakan satu kolom `attachment_path` (satu berkas per tiket) sesuai keputusan pemberi task, bukan tabel terpisah.

Registrasi mandiri aktif di `/admin/register` (`app/Filament/Auth/Register.php`). Setelah daftar, Filament otomatis login-kan akun baru (perilaku bawaan package, bukan kustomisasi) — pada saat itu role `user` sudah ter-assign lebih dulu di dalam `handleRegistration()`, jadi tidak ada celah waktu akun baru gagal masuk panel. Perubahan role dilakukan admin lewat menu **Users**, yang mengganti (bukan menambah) role lewat `syncRoles()`.

Halaman FAQ (`/faq`) merender `answer` sebagai HTML mentah tanpa sanitasi — sengaja, karena penulisnya dibatasi `FaqPolicy` ke admin saja. Kalau nanti role lain diberi akses tulis FAQ, tambahkan pembersih HTML sebelum data disimpan.

Rencana pengembangan berikutnya:

- Filter dan pencarian tiket berdasarkan status dan priority.
- Notifikasi ketika tiket ditugaskan atau statusnya berubah.
- Automated test untuk alur registrasi, pengelolaan role lewat UserResource, dan fitur FAQ.
