# Pembaruan teknis untuk proposal dan RAB BudayaGO

**Naskah pengganti untuk bagian kemajuan.** BUDAYAGO mengembangkan perpustakaan legenda budaya Sulawesi Barat dengan koleksi sebagai pusat dokumentasi. Kandidat SCK-008 telah memperagakan pada fixture sintetis proses dari pencatatan draf, unggah foto/audio dan telaah izin per berkas hingga publikasi serta penarikan akses. Budayawan dapat mengirim tanggapan yang dimoderasi. Metadata draf dapat disimpan ketika formulir sudah terbuka dan koneksi terputus. Hasil ini memberi dasar teknis untuk pilot lapangan. Validitas narasi, penerimaan pemangku budaya, manfaat bagi pengguna dan kesiapan operasional memerlukan bukti lapangan.

**Roadmap.** Pertama verifikasi staging CloudPanel dengan source dan hash yang sama. Berikutnya jalankan UAT Android/iOS dan periksa izin nyata bersama wakil komunitas. Setelah itu putuskan RC, target biaya dan operasi produksi. Tahap studi lanjutan mencakup transkripsi, OCR manuskrip, perlindungan multimedia offline dan unggah video besar. Indikator pilot yang dapat diukur meliputi proporsi koleksi dengan bukti lengkap per media, keberhasilan penyimpanan pada jaringan lemah, waktu kurasi serta waktu penghentian akses setelah withdrawal. Nilai baseline indikator lapangan belum diukur.

**Sinkronisasi.** Matriks memakai ID CLM-001–010 dari model Track A/B v1.1 tanggal 14 November. File CLAIMS.csv mempertahankan bukti historis dan menambah delta SCK-008. CONTRACT.json memperbarui salinan kontrak. Kebijakan biaya dan narasi ini merupakan addendum siap dimasukkan ke master proposal. Paket sumber yang terbaca tidak memuat master proposal atau penawaran pemasok terverifikasi.

| Komponen RAB | Kuantitas yang harus diperoleh | Sumber harga satuan |
|---|---|---|
| VPS dan domain | Bulan site aktif, alokasi staging/production, domain per tahun | Invoice/paket hosting serta biaya perpanjangan aktual |
| Media aktif | Koleksi per bulan × rerata berkas × ukuran rerata terukur × masa retensi | Kuota terpakai dan tarif storage tambahan dari penyedia |
| Backup | Ukuran terukur DB+media × jumlah salinan/retensi dan frekuensi perubahan | Penawaran backup terpisah, termasuk biaya restore |
| Bandwidth | Putar/unduh per bulan × byte terkirim, termasuk byte-range | Statistik transfer target serta tarif kelebihan kuota |
| Kerja lapangan dan hak | Jumlah kunjungan/rekaman berizin, hari kerja, satuan honor yang disetujui | Rencana lapangan dan standar biaya resmi yang berlaku |
| Kurasi dan metadata | Koleksi × jam pemeriksaan bukti, bahasa, redaksi dan koreksi | Tarif kurator yang disetujui |
| Keamanan dan pemeliharaan | Jam admin, review independen, uji restore dan pemindaian | Penawaran/jadwal operator yang terdokumentasi |

Untuk setiap baris isi periode, satuan, volume, harga, vendor/sumber, tanggal dan total (volume × harga). Kurangi kuota yang telah termasuk paket ketika menghitung biaya tambahan. Bandingkan dua skenario menggunakan statistik pilot dan paket VPS aktual. RAB_NOMINAL berstatus BLOCKED sampai ada inventaris target serta harga. Tidak ada angka rupiah yang direka.
