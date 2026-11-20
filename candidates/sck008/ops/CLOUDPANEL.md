# Pemasangan staging BUDAYAGO SCK-008

Untuk operator CloudPanel yang memiliki akses ke site. Isi nama dan direktori dari panel aktual. Gunakan site user, skema dan koleksi sintetis khusus staging.

## 1. Rekam target

| Bukti | Verifikasi |
|---|---|
| Mesin dan site | Catat OS, versi CloudPanel dari panel, site user, domain dan PHP pool. Pastikan konfigurasi staging, direktori, DB, sesi, throttle dan media terpisah dari production. |
| DNS dan TLS | Cocokkan A serta AAAA jika tersedia dengan target. Periksa SAN, masa berlaku, redirect HTTP→HTTPS dan rantai sertifikat dari jaringan luar. |
| Akses | Uji SSH/SFTP sebagai site user. Periksa owner/group, kuota, disk dan inode. Verifikasi firewall untuk panel, SSH, HTTPS dan DB. |
| Nginx dan FPM | Catat document root efektif, vhost, aturan FastCGI, HTTPS yang diterima FPM, PHP dan ekstensi pool. PHP CLI memiliki konfigurasi tersendiri. |
| Data | Catat server/port, nama skema, engine/version, grants, ruang backup, lokasi salinan terpisah dan bukti restore. Simpan kredensial di vault. |

Buat PHP site staging lewat menu penambahan site jika diperlukan. Aktifkan TLS untuk domain staging terverifikasi. Batasi akses pilot di tingkat site/jaringan. CloudPanel menyediakan pengaturan root dan PHP per site pada Settings. [Dokumentasi CloudPanel](https://www.cloudpanel.io/docs/v2/frontend-area/settings/)

## 2. Pasang source

1. Cocokkan SHA256 ZIP dengan checkpoint. Ekstrak ke direktori release baru. Dari folder BUDAYAGO jalankan `sha256sum -c SHA256SUMS.txt`. Periksa setiap hasil.
2. Buat storage persisten untuk private-media, login-throttle, sesi, konfigurasi dan backup di luar semua web root dan direktori release. Gunakan mode 0700 pada storage privat dan owner site/FPM yang benar. Kode cukup terbaca oleh FPM.
3. Salin config.example.php menjadi config.php di tingkat yang sama dengan app/. Dapat berupa symlink ke konfigurasi persisten site. Isi DSN, user, sandi, APP_ORIGIN HTTPS, FORCE_HTTPS bernilai string true, LOGIN_RATE_LIMIT_DIR dan HERITAGE_MEDIA_DIR aktual. Gunakan mode 0600 atau grup terbatas yang sesuai. HERITAGE_PUBLICATION_ENABLED bawaan false menutup publikasi dan pembacaan publik Living Heritage. Aktifkan hanya untuk pengujian sintetis pada staging terisolasi atau setelah gate production.
4. Atur Root Directory agar hasil efektif tepat `<release>/public`. Nginx memakai index.php. Aplikasi mengalihkan / ke galeri dan tetap mengenali index.php?p=home. Simpan konfigurasi vhost lama.
5. Cek pdo_mysql, mbstring, fileinfo dan session. Aplikasi membatasi 12 MiB per file. Set upload_max_filesize=12M, post_max_size dan client_max_body_size lebih besar untuk overhead multipart. Sesuaikan memory_limit dan timeout menurut pengukuran. Atur display_errors=Off, log_errors=On, HTTPS dari terminasi tepercaya, segarkan OPcache setelah pergantian release. Nonaktifkan FastCGI/CDN caching untuk PHP, media terotorisasi dan service worker. Uji `nginx -t` sebelum reload.
6. Jalankan `bash ops/preflight.sh "$STAGING_DOMAIN" "$RELEASE/public"` dengan nilai aktual. Skrip membaca CLI, kapasitas, DNS dan HTTPS. Lengkapi bukti FPM, firewall, backup, versi panel dan quota dari target.

## 3. Basis data

Sediakan akun migrasi sementara berkewenangan DDL pada satu skema serta akun aplikasi dengan SELECT, INSERT, UPDATE, DELETE pada skema itu. Gunakan file opsi klien privat mode 0600 atau prompt. Periksa SELECT DATABASE(), CURRENT_USER(), VERSION() dengan kedua akun. Kecualikan sck_uat dan sck_ci.

| Kondisi skema | Urutan migrasi |
|---|---|
| Skema staging kosong | database/mysql.sql, database/20261119_heritage_additive.sql, database/20261120_heritage_rights.sql |
| Salinan SCK-007 dengan definisi dasar cocok | Hanya migrasi 19 dan 20 November |
| Salinan Alpha dengan tiga tabel heritage cocok | Hanya migrasi 20 November |
| Tabel parsial/berbeda atau asal tidak jelas | Bandingkan SHOW CREATE TABLE, FK dan indeks dengan fixture bersih. Susun dan uji rekonsiliasi pada klon. |

Gunakan `php ops/db_inventory.php "$STAGING_DB"` sebelum dan sesudah. Dry run berupa penerapan pada klon disposabel dari backup aktual. DDL dapat melakukan implicit commit. IF NOT EXISTS tidak membandingkan definisi tabel. Untuk satu berkas yang telah ditelaah:

```bash
mysql --defaults-extra-file="$MIGRATION_CNF" --database="$STAGING_DB" < "$REVIEWED_SQL_FILE"
```

Periksa kode keluar, inventaris akhir dan log. Bekukan penulisan legacy selama cutover. Salin media lama ke lokasi persisten dan cocokkan hash secara privat. Koleksi Alpha tanpa izin dan telaah terperinci tersembunyi dari publik. Petakan record lama dan buat pengajuan yang ditelaah ulang jika akan diterbitkan. Pertahankan jejak record lama. Hindari pengisian izin massal.

## 4. Akun awal dan uji staging

Dari release, untuk skema tanpa administrator:

```bash
read -rp 'Email administrator staging: ' ADMIN_EMAIL
read -rsp 'Sandi awal staging: ' ADMIN_PASSWORD
echo
export ADMIN_EMAIL ADMIN_PASSWORD
php bin/install-admin.php
unset ADMIN_PASSWORD ADMIN_EMAIL
```

Bootstrap menolak pembuatan jika admin sudah ada. Buat akun sintetis pengajar, holder, kurator dan siswa. Dari jaringan luar, uji /, manifest, service worker dan TLS. Pastikan /config.php, /app/, /database/, /ops/, /private_media/ dan kunci file fisik tidak menyajikan data. Cek cookie Secure/HttpOnly/SameSite, login, unggah, detail media terpilih dan privat, withdrawal melalui URL lama, logout dan audit. Lengkapi evidence/UAT.csv dengan waktu WITA, perangkat, browser, domain, SHA dan bukti yang telah disamarkan.

Uji Android Chrome dan iOS Safari terpisah. Pasang dari menu browser, buka dari ikon, izinkan/tolak kamera dan mikrofon, uji file picker, unggah foto/audio pendek, putuskan jaringan dan ulangi. Verifikasi draf dari tab terbuka serta fallback untuk pembukaan baru ketika luring. [Panduan instalasi PWA](https://developer.mozilla.org/en-US/docs/Web/Progressive_web_apps/Guides/Making_PWAs_installable)
