# Temuan dan tindak lanjut

| Temuan | Prioritas | Tindakan dan bukti | Keadaan |
|---|---|---|---|
| Publikasi Alpha membuka seluruh berkas dan metadata nama file | Kritis | Gate record bertelaah dan grant per aset, nama asli tidak dirender publik. Media tidak dipilih 403 sesudah terbit. | Ditutup pada fixture LH03–04 |
| Admin sebelumnya dapat langsung menerbitkan dari status kurasi | Tinggi | Pemangku hak mengisi rujukan kewenangan serta izin, kurator memeriksa, admin menerbitkan; tiap aksi terlacak. | Ditutup lokal LH03 |
| Perubahan status, persetujuan dan audit sebelumnya terpisah | Tinggi | Transaksi dan lock record; referensi aset invalid membatalkan grant dan bukti parsial. | Ditutup lokal LH03 |
| Form privat dirender sebelum pengaturan status HTTP | Tinggi | Pemeriksaan peran dan detail dijalankan sebelum keluaran HTML. | Ditutup lokal LH01–02 |
| Draf Alpha menggunakan satu kunci untuk seluruh pengguna dan tidak memeriksa commit IndexedDB | Tinggi | Salinan baru diisolasi per akun, keberhasilan tampil setelah transaksi selesai, pembersihan setelah pengakuan server, kunci idempoten dan penghapusan manual. | Teruji Chromium; perangkat lapangan menunggu |
| Upload ulang dapat menduplikasi media | Sedang | Fingerprint unik per koleksi dalam transaksi dan pemeriksaan izin akuisisi. | Ditutup lokal LH02 |
| Penyediaan admin CLI yang dirujuk README hilang | Tinggi | Bootstrap CLI dengan lock, menolak pengulangan. | Dua pemanggilan diuji fixture |
| Pencarian dan jalur masuk dari beranda belum lengkap | Sedang | Galeri menjadi halaman masuk, pencarian judul/wilayah/deskripsi dan tautan layanan lama tersedia. | Teruji LH04/PWA01 |
| Gate lama UAT-25/26/28 belum memiliki bukti pada kandidat | Tinggi | Hasil jawaban 0/1, HTML siswa, POST CSRF sah sesudah withdrawal dengan 403 dan jumlah attempts tetap. | Ditutup lokal untuk build ini |
| Data Alpha terbit tanpa grant baru dan bukti akuisisi lama | Tinggi jika ada data nyata | Pembacaan publik menuntut telaah generasi baru; inventaris dan rekurasi record lama pada salinan target. | Buka untuk migrasi target |
| Pemindaian biner, sanitasi metadata dan bukti hak manusia | Tinggi untuk materi nyata | Pemeriksaan independen atas media, EXIF, PDF, penutur dan lingkup publik; media privat sampai ada bukti. | Buka untuk produksi |
| Kesesuaian VPS, HTTPS, konfigurasi pool, restore target, penerimaan pengguna | Kritis untuk go-live | SOP, skrip preflight dan checklist tersedia. Jalankan pada staging target lalu mintakan acceptance. | BLOCKED target |
