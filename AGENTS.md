# ATURAN KETAT PENGEMBANGAN SISTEM (WAJIB DIPATUHI)

Anda bertindak sebagai Senior Laravel Developer yang membangun aplikasi ERP Digital Printing & Advertising. Patuhi seluruh batasan berikut tanpa pengecualian:

1. STACK & PENYIMPANAN LOKAL:
   - Wajib menggunakan Laravel versi terpasang dan database MySQL (dilarang mengganti ke SQLite/PostgreSQL).
   - Seluruh file unggahan wajib disimpan di disk lokal bernama `media_sistem` (`storage/app/public/MEDIA SISTEM`). Dilarang memakai cloud storage eksternal.
   - Frontend wajib menggunakan Blade, Tailwind CSS, dan Alpine.js (dilarang memasang SPA framework seperti React/Vue).

2. BATASAN RUANG LINGKUP (ANTI OVER-ENGINEERING):
   - Kerjakan HANYA ruang lingkup pada Tahap yang sedang diminta. Dilarang melompat ke fitur Tahap lain atau menambah fitur di luar spesifikasi (seperti payment gateway, multi-cabang, atau absensi).
   - Pada Fase 1 ini, layanan Jasa Advertising (pembuatan rangka, pemasangan, papan nama) TIDAK BOLEH dihitung harga jualnya (nominal wajib Rp 0 / disembunyikan). Fungsinya hanya menerbitkan SPK kerja untuk role `advertising`.

3. ATURAN BISNIS MUTLAK:
   - Isolasi Role (RBAC): 6 role (`admin`, `desainer`, `operator_cetak`, `finishing`, `advertising`, `owner`) wajib memiliki dashboard dan rute masing-masing serta diblokir middleware jika mencoba membuka halaman divisi lain.
   - Kerahasiaan Rumus Finishing: Dropdown finishing pada halaman input order Admin HANYA menampilkan nama finishing. Dilarang menampilkan angka tambahan cm (seperti +2.5 cm atau +7 cm) pada tampilan input order.
   - Produk Satuan Tanpa Finishing: Produk kategori `satuan` (`X-Banner`, `Roll Banner`, `Poster`, `Stiker A3`, `A-Board`) tidak memakai finishing (`finishing_id = null`).
   - Pembulatan Minimal & Bebas Biaya Finishing: Cetak meteran di bawah `1 m²` dibulatkan menjadi `1 m²` HANYA pada tagihan pelanggan. Ekstra bahan finishing TIDAK dibebankan ke tagihan pelanggan, namun tetap memotong stok roll gudang (luas kotor).
   - Kunci Upload Desainer: Desainer tidak bisa mengirim order ke antrean cetak sebelum mengunggah 2 file wajib: file siap cetak (`.tiff`/`.tif`) dan file master arsip (`.cdr`/`.psd`/`.ai`/`.zip`).

4. STANDAR KODE:
   - Selalu periksa file di `database/migrations` sebelum menulis Model/Controller agar nama kolom dan relasi 100% sinkron.
   - Gunakan `DB::transaction()` untuk proses simpan transaksi order dan pemotongan stok bahan.
