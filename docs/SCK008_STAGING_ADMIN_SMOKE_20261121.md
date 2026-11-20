# SCK-008 · Uji login administrator di staging

21 November 2026 WITA

| Pemeriksaan | Bukti operator | Hasil |
| --- | --- | --- |
| Konfigurasi staging | Pemeriksa melaporkan 12 tabel, 20 foreign key dan `publication=false`. | Lulus |
| Pembuatan administrator pertama | Perintah inisialisasi mengonfirmasi pembuatan administrator. | Lulus |
| Login dan tampilan galeri | Setelah login, tangkapan layar `heritage.php` memperlihatkan Administrasi, Rekam Budaya, Kontribusi dan Kurasi, serta Keluar. | Lulus |

Pengujian berikutnya mencakup akses ke Kelola pengguna, pembuatan akun uji `holder` dan `curator`, serta penyimpanan draf sintetis. UAT alur kurasi dan pengujian PWA di perangkat masih tertunda.
