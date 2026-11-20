# Gate penerimaan dan checkpoint

**20 November 2026 · STAGING_READY untuk kandidat aplikasi dan fixture sintetis. Production NO-GO.** PHP/DB/HTTP/Chromium dan restore lokal telah diuji. Domain staging, staging acceptance, RC dan URL live belum terverifikasi. Gunakan file ini untuk mengikat keputusan berikut pada **SHA256 ZIP** yang sama. Jika source berubah, catat hash baru, analisis dampak dan ulangi pengujian terkait.

| Gate | Bukti yang harus dilampirkan | Keadaan sekarang |
|---|---|---|
| Paket → staging | SHA256 luar+dalam, rekonsiliasi, fixture, source dan instruksi | Siap untuk pemasangan terisolasi |
| Discovery dan staging aktual | Domain/IP/SSH terotorisasi, site user, root, HTTPS, FPM, engine, ruang dan backup | BLOCKED — target terverifikasi belum disediakan |
| Staging UAT | Bukti per kasus, Android/iOS fisik, izin capture/fallback, keyboard/screen reader, koneksi lambat, pemutaran media dan akses privat | NOT_RUN pada target |
| UAT_ACCEPTED | Nama penguji/owner, waktu, domain, hash, hasil dan keputusan bertanda | Menunggu |
| Hak publik dan keamanan | Verifikasi otoritas, rujukan bukti per media, review metadata, audit independen, penutupan temuan critical/high | Menunggu |
| RC_READY | Source commit yang ditelaah, tag rilis, manifest, kesetaraan kandidat–staging dan tinjauan migrasi | Belum diterbitkan |
| PRODUCTION_APPROVED | Backup dan restore target tervalidasi, RPO/RTO dan rollback, identitas domain dan DB, persetujuan pemilik spesifik hash | Menunggu |
| LIVE_VERIFIED | Waktu aktivasi, URL nyata, verifikasi eksternal HTTPS, login, galeri, akses media, audit, keputusan buka akses | Belum dijalankan |
| Operasi | Bukti job backup, uji alert, kapasitas, akun operator dan jadwal pemeriksaan | Menunggu instalasi |

**Data checkpoint berikut:** domain staging BUDAYAGO, akses site user terotorisasi melalui kanal aman atau hasil preflight operator, versi FPM/DB dan konfigurasi site yang sudah disamarkan. Setelah bukti tersedia, lanjutkan TASK 06 → 08 menggunakan paket ini. Pisahkan domain live dan staging. Jangan mengirim kata sandi atau private key di percakapan.

**Form persetujuan.** Site/domain ______; lingkungan ______; SHA256 ZIP ______; commit ______; hasil UAT ______; referensi bukti hak ______; review keamanan ______; ID backup dan restore drill ______; keputusan GO/HOLD ______; tanggal dan waktu WITA ______; pemilik ______. Pada perubahan target/hash, ulangi persetujuan terkait.
