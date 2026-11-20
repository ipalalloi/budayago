# Backup, pemulihan dan operasi

Catat release aktif, vhost/root, versi konfigurasi dan skema, jumlah aset dan referensi persetujuan. Tetapkan target kehilangan data maksimum (RPO), waktu pemulihan (RTO), jadwal maintenance dan pemegang keputusan rollback. Hentikan penulisan sementara agar snapshot DB, media dan arsip izin konsisten. Simpan backup di lokasi privat terpisah dengan enkripsi dan akses operator.

Contoh backup menggunakan utilitas yang sesuai dengan engine. Isi seluruh variabel dari inventaris terverifikasi. Gunakan direktori baru.

```bash
set -euo pipefail
umask 077
mkdir "$NEW_PRIVATE_BACKUP"
"$DB_DUMP_BIN" --defaults-extra-file="$BACKUP_CNF" --single-transaction --quick "$VERIFIED_DB" > "$NEW_PRIVATE_BACKUP/database.sql"
tar -C "$PRIVATE_MEDIA_DIR" -cf "$NEW_PRIVATE_BACKUP/media.tar" .
(cd "$NEW_PRIVATE_BACKUP" && sha256sum database.sql media.tar > SHA256SUMS && sha256sum -c SHA256SUMS)
```

Simpan release, vhost/FPM, konfigurasi rahasia serta arsip privat persetujuan sesuai kontrol masing-masing. Inventaris triggers/events/routines aktual dan sertakan opsi dump yang diperlukan. Catat lokasi salinan kedua, pemilik, waktu dan versi engine.

**Drill.** Buat skema restore kosong dan folder media privat baru. Periksa server/nama skema sebelum impor. Ekstrak media ke folder baru, cocokkan hash per aset, row dan izin. Pada site staging terisolasi uji login, detail, akses stream yang diizinkan dan ditolak, withdrawal, audit dan keterkaitan DB–file. Catat waktu dan rekonsiliasi. Bukti restore yang disertakan berasal dari fixture sintetis.

**Rollback.** Jika media privat terekspos, withdrawal gagal, migrasi terhenti atau smoke kritis gagal, tutup akses publik dan penulisan. Simpan log serta keadaan terbaru. Bila skema kompatibel, kembalikan root/vhost ke release yang telah disetujui, jalankan nginx -t, reload dan segarkan OPcache. Alpha lama dapat membuka media yang kini dibatasi. Pertahankan site tertutup sampai aturan hak setara dan withdrawal terbaru tetap berlaku. Pertahankan tabel aditif serta media. Jika rekonsiliasi data diperlukan, pulihkan di skema lain dahulu. Minta otorisasi sebelum pergantian target atau tindakan destruktif.

**Aktivasi production.** Setelah UAT dan persetujuan spesifik terhadap hash, cocokkan paket dengan staging, verifikasi target, backup dan verifikasi salinan, pasang ke folder versi baru. Terapkan hanya migrasi yang dilatih. Lakukan pergantian root, uji HTTPS/login/galeri/media privat/withdrawal/audit dan catat waktu buka publik serta approver. LIVE_VERIFIED memerlukan bukti dari domain aktual.

| Pemantauan | Tindakan operator |
|---|---|
| Uptime dan TLS | Konfigurasi probe domain publik serta alert kegagalan dan masa berlaku sertifikat. Uji penerimaan alert. |
| Error dan keamanan | Tinjau 5xx, log Nginx/FPM, login gagal, penolakan unggah dan hak akses; gunakan retensi terbatas dan redaksi. |
| Storage media | Catat byte, inode, kuota, pertumbuhan dan transfer. Sesuaikan ambang peringatan dengan kapasitas dan waktu intervensi. |
| Backup | Jadwalkan menurut RPO, lakukan restore berkala dan setelah perubahan besar. Bandingkan durasi dengan RTO. |
| Integritas | Cocokkan media dengan DB secara read-only. Tinjau file yatim dan salinan turunan sebelum menghapus. Periksa malware dan metadata identitas sebelum publikasi. |
| Admin dan budaya | Tinjau administrator, kurator, kewenangan holder dan masa izin. Simpan dokumen bukti secara privat. |
| Withdrawal | Verifikasi pemohon, tarik koleksi, uji ulang galeri dan URL lama sebagai tamu, periksa audit, turunan serta retensi backup. Salinan yang telah diunduh memerlukan tindak lanjut tersendiri. |

Penjadwalan dan alert pada VPS menunggu konfigurasi dan bukti uji.
