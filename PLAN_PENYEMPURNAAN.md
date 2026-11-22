# RENCANA PENYEMPURNAAN SISTEM REFFO (STEP-BY-STEP)

Tujuan: menyempurnakan sistem tanpa keluar dari ruang lingkup Fase 1 dan tanpa mengubah struktur tabel yang sudah berjalan.
 
## Step 1 — Validasi Upload Lebih Ketat
- StoreOrderRequest: batasi mime type lampiran briefing (jpg,jpeg,png,webp,pdf,tif,tiff,cdr,psd,ai,zip,rar) + max 20 MB.
- FinalizeDesignRequest: validasi mime type untuk .tif/.tiff dan arsip.
- UploadPreviewRequest: validasi mime JPG/PNG + max size.
- Uji: upload file tidak valid ditolak dengan pesan jelas.

## Step 2 — Flash Message Konsisten
- Standarkan penggunaan `->with('status', ...)` / `->with('error', ...)`.
- Tampilkan flash di layout utama (success/error) dengan style Tailwind.
- Uji: semua aksi menampilkan notifikasi yang sama gaya.

## Step 3 — Seed Data Harga Produk Final
- Update ProductSeeder dengan harga realistis per kategori.
- Jangan ubah struktur tabel.
- Uji: `php artisan migrate:fresh --seed` dan cek harga di settings Owner.

## Step 4 — Pagination & Search Daftar Nota Admin
- `/admin/orders` mendukung `?q=` (instansi/nama pelanggan/no nota) + pagination 15.
- Tambah input search di view.
- Uji: pencarian dan paging berfungsi.

## Step 5 — Export/Print PDF Nota
- Tambah tombol "Cetak PDF" di ringkasan nota.
- Generate PDF rapi (DomPDF/Blade print CSS), tanpa harga pada SPK Advertising.
- Uji: PDF terbuka dan rapi.

## Step 6 — Uji End-to-End Tahap 6
- Simulasikan alur lengkap Admin → Desainer → Operator → Finishing → Advertising → Owner.
- Pastikan Wos Finishing, offcut baru, gagal cetak, dan Laba Bersih tercatat.
- Tambah test feature untuk regresi.
- Uji: `php artisan test` lulus.

## Step 7 — Review & Commit
- Jalankan Pint, view:cache, route:list.
- Commit dengan pesan jelas per step.
- Push ke GitHub.

Catatan: Patuhi AGENTS.md di setiap step.
