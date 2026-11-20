# SCK-008 • Rekonsiliasi 20 November 2026

| Input | SHA256 seluruh ZIP | Integritas |
|---|---|---|
| BUDAYAGO_LIVING_HERITAGE_PWA_v0.2_ALPHA_20261119.zip | f49e67dad24b0370cb0849066e39ed1ed4ef2253b72f4f2ea8b034f175eb3d13 | CRC ZIP lengkap. 38/38 entri manifest Alpha cocok. |
| BUDAYAGO_SCK007_SECURITY_SUCCESSOR_REVIEW_v0.1_20261118.zip | ba26f9a85ab2e66304dde4ef07061608351cbbcd415fc3ead97491079244d756 | CRC lengkap. 23/23 entri manifest asal cocok. |
| BUDAYAGO_LIVING_HERITAGE_UI_CONCEPT_v0.2_20261119.zip | 067d46a5bf32749e0593e147b2e124ad9b43b36f8d1d5922fe3be0b747112fe2 | CRC lengkap. 5/5 entri manifest cocok. Referensi rancangan. |

Hash ZIP dihitung ulang dari byte input. Manifest internal menguji konsistensi isi. Pengesahan pihak eksternal atau tanda tangan penerbit belum tersedia.

**Perbandingan langsung.** Dari 24 berkas arsip SCK-007, 23 identik di Alpha, satu berubah, tanpa penghapusan; Alpha menambah 15 berkas. Perubahan index.php menambahkan tautan Living Heritage. Saat manifest historis SCK-007 dijalankan di salinan Alpha, satu mismatch tersebut memang muncul. Manifest aktif paket SCK-008 adalah SHA256SUMS.txt.

**Git.** Clone baru dari https://github.com/ipalalloi/budayago, branch development/sck-mvp-20261113 pada f1fbb793a06cafbe58036f7541b08bed049f3fe1. Working tree bersih sebelum pekerjaan. Main pada eb4b8f376991791c1985ee814d818332616666b5. Development memiliki empat commit unik dan tertinggal delapan dari main. Keduanya menyimpan dokumen proyek, tanpa aplikasi PHP atau skema runtime untuk disamakan dengan ZIP. Sumber aplikasi kandidat diturunkan langsung dari Alpha terverifikasi. Tiga ZIP asli tetap sumber historis. Checkout Windows dan VPS aktual berada di luar lingkungan uji ini.
