# Kemampuan dan bukti

| Status | Ruang lingkup | Bukti atau batasan |
|---|---|---|
| TESTED | Login lima peran pada fixture, draf dan retry, unggah PNG serta MP3, penolakan MIME/ukuran, keputusan holder, kurasi, publikasi, pencarian, stream, moderasi dan withdrawal | evidence/http-tests.txt, legacy-smoke.txt. Satu alur menghasilkan response HTTP dan perubahan DB yang diperiksa. |
| TESTED | Manifest dibaca Chromium, service worker aktif, tata letak galeri 320/390/768/1280 px, penyimpanan metadata pada tab luring dan pemulihan setelah tersambung | evidence/browser-tests.txt dan tests/sck008_browser.py. Form hanya dapat dibuka pertama kali saat tersambung. |
| IMPLEMENTED | File picker dan petunjuk capture foto/audio/video; stream byte-range; konfigurasi private storage; pembatasan tipe dan 12 MiB | MIME aktual browser/perangkat menentukan penerimaan. PNG/MP3 diuji. PDF, video MP4/WebM dan UX perangkat fisik menunggu UAT. |
| PROTOTYPE | Konsep museum digital, alur lapangan dan kurasi; proposal manfaat bagi komunitas | Kandidat memanfaatkan palet dan navigasi konsep. Penerimaan komunitas serta dampak pelestarian perlu penelitian terpisah. |
| PLANNED | Multimedia offline terenkripsi, upload video besar/resumable, transkripsi/OCR dan peta hubungan cerita | Draf saat ini menyimpan teks non-sensitif pada perangkat pribadi, satu draf per akun per browser. |
| BLOCKED | Staging HTTPS aktual, Android/iOS fisik, hak materi nyata, audit independen dan go-live | Lihat evidence/UAT.csv dan ops/ACCEPTANCE.md. |

**Desain terpisah multimedia luring.** Sebelum prototipe, tetapkan peran perangkat dan persetujuan sebelum rekaman, ukuran/kuota dan ruang cadangan, enkripsi serta pemisahan kunci akun, penguncian sesi, pengunggahan bertahap dengan verifikasi hash, pemulihan setelah putus dan deduplikasi. Tentukan retensi, penghapusan setelah tanda terima, prosedur perangkat hilang dan batas penghapusan pada penyimpanan flash/backup perangkat. Uji kehabisan ruang dan eviction pada Android/iOS. Implementasi saat ini tidak menulis byte media ke IndexedDB atau Cache Storage.
